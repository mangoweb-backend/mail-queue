<?php declare(strict_types = 1);

namespace MangowebTests\MailQueue\Cases\Integration;

use Mangoweb\Clock\Clock;
use Mangoweb\Clock\ClockMock;
use Mangoweb\MailQueue\Bridges\NextrasDbal\NextrasMailStorage;
use Mangoweb\MailQueue\IdentifiedMessage;
use Mangoweb\Tester\DatabaseCreator\Bridges\InfrastructureNextrasDbal\NextrasDbalHook;
use Mangoweb\Tester\Infrastructure\Container\AppContainerHookList;
use Mangoweb\Tester\Infrastructure\Container\IAppContainerHook;
use Mangoweb\Tester\Infrastructure\TestCase;
use MangowebTests\MailQueue\Inc\MailQueueContainerHook;
use Nette\DI\Container;
use Nette\Mail\Message;
use Nette\Utils\Json;
use Nextras\Dbal\Connection;
use Tester\Assert;


$containerFactory = require __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class NextrasMailStorageTest extends TestCase
{
	public function testWrite(NextrasMailStorage $mailStorage, Connection $connection)
	{
		$message = $this->createMailMessage();

		$id = $mailStorage->write($message);

		$result = $connection->query('SELECT * FROM mails WHERE id = %i', $id);
		assert($result !== null);
		$row = $result->fetch();

		Assert::notSame(null, $row);
		assert($row !== null);

		Assert::same(['john@doe.com'], Json::decode($row->recipients));
		Assert::same('Hello world', $row->subject);
		Assert::same('This is body', $row->body);
		Assert::same(serialize($message), $row->message);
		Assert::equal(Clock::now()->getTimestamp(), $row->created_at->getTimestamp());
		Assert::null($row->sent_at);
		Assert::null($row->last_failed_at);
		Assert::same(0, $row->failure_count);
	}


	public function testFetch(NextrasMailStorage $mailStorage)
	{
		$message = $this->createMailMessage();
		$id = $mailStorage->write($message);
		$messageToSent = $mailStorage->fetchUnsent();

		Assert::type(IdentifiedMessage::class, $messageToSent);
		assert($messageToSent !== null);
		Assert::same($id, $messageToSent->getId());
		Assert::equal($message, $messageToSent->getMessage());
	}


	public function testMarkSent(NextrasMailStorage $mailStorage)
	{
		$message = $this->createMailMessage();
		$mailStorage->write($message);

		$identifiedMessage = $mailStorage->fetchUnsent();

		Assert::type(IdentifiedMessage::class, $identifiedMessage);
		assert($identifiedMessage !== null);

		$mailStorage->markSent($identifiedMessage->getId());
		$identifiedMessage = $mailStorage->fetchUnsent();

		Assert::null($identifiedMessage);
	}


	public function testFailed(NextrasMailStorage $mailStorage)
	{
		$message = $this->createMailMessage();
		$mailStorage->write($message);

		$identifiedMessage = $mailStorage->fetchUnsent();

		Assert::type(IdentifiedMessage::class, $identifiedMessage);
		assert($identifiedMessage !== null);

		$mailStorage->markFailed($identifiedMessage->getId(), 'Failed');
		$identifiedMessage = $mailStorage->fetchUnsent();

		Assert::null($identifiedMessage);

		ClockMock::addSeconds(60);
		$identifiedMessage = $mailStorage->fetchUnsent();
		Assert::type(IdentifiedMessage::class, $identifiedMessage);

		$mailStorage->markFailed($identifiedMessage->getId(), 'Failed');

		ClockMock::addSeconds(60);
		$identifiedMessage = $mailStorage->fetchUnsent();
		// interval grows exponentially
		Assert::null($identifiedMessage);

		ClockMock::addSeconds(60);
		$identifiedMessage = $mailStorage->fetchUnsent();
		Assert::type(IdentifiedMessage::class, $identifiedMessage);
	}


	protected function createMailMessage(): Message
	{
		$message = new Message();
		$message->addTo('john@doe.com', 'John');
		$message->setFrom('jack@example.org', 'Jack');
		$message->setSubject('Hello world');
		$message->setHtmlBody('This is body');

		return $message;
	}


	protected static function getContainerHook(Container $testContainer): ?IAppContainerHook
	{
		return new AppContainerHookList([
			new NextrasDbalHook(),
			new MailQueueContainerHook([
				'storage' => NextrasMailStorage::class,
			]),
		]);
	}
}


NextrasMailStorageTest::run($containerFactory);
