<?php declare(strict_types = 1);

namespace MangowebTests\MailQueue\Cases\Integration;

use Mangoweb\MailQueue\Bridges\SymfonyConsole\SendMailsCommand;
use Mangoweb\MailQueue\MailSenderException;
use Mangoweb\MailQueue\QueueMailer;
use Mangoweb\MailTester\MailTester;
use Mangoweb\MailTester\TestMailer;
use Mangoweb\Tester\Infrastructure\Container\IAppContainerHook;
use Mangoweb\Tester\Infrastructure\TestCase;
use Mangoweb\Tester\LogTester\LogTester;
use MangowebTests\MailQueue\Inc\MailQueueContainerHook;
use MangowebTests\MailQueue\Inc\TestMailStorage;
use Nette\DI\Container;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Nette\Mail\SmtpException;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tester\Assert;

$containerFactory = require __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class SendMailsCommandTest extends TestCase
{
	/** @var MailTester */
	private $mailTester;

	/** @var LogTester */
	private $logTester;


	public function __construct(MailTester $mailTester, LogTester $logTester)
	{
		$this->mailTester = $mailTester;
		$this->logTester = $logTester;
	}


	public function testExecute(IMailer $mailer, TestMailer $testMailer, TestMailStorage $mailStorage, SendMailsCommand $command)
	{
		assert($mailer instanceof QueueMailer);

		$message1 = $this->createMailMessage();
		$message2 = $this->createMailMessage();
		$message3 = $this->createMailMessage();
		$mailer->send($message1);
		$mailer->send($message2);
		$mailer->send($message3);
		Assert::count(3, $mailStorage->messages);


		$sendException = new SmtpException('mailer failed :(');
		$testMailer->exceptions = [false, $sendException, false];

		$input = new ArrayInput([]);
		$output = new BufferedOutput();
		$command->run($input, $output);

		Assert::same('', $output->fetch());

		Assert::same([1, 3], $mailStorage->sent);
		Assert::same([2], $mailStorage->failed);

		$message = $this->mailTester->consumeSingle();
		$message->assertSubject('Hello world 1');

		$message = $this->mailTester->consumeSingle();
		$message->assertSubject('Hello world 3');

		$this->logTester->consumeOne(LogLevel::DEBUG, 'Queued mail was successfully sent.');
		$logEntry = $this->logTester->consumeOne(LogLevel::ERROR, 'Queued mail sending has failed.');
		Assert::type(MailSenderException::class, $logEntry->context['exception']);
		$this->logTester->consumeOne(LogLevel::DEBUG, 'Queued mail was successfully sent.');
		$this->logTester->consumeOne(LogLevel::INFO, 'mango:mail-queue:send-mails: finished');
	}


	protected function createMailMessage(): Message
	{
		static $counter = 0;
		$i = ++$counter;
		$message = new Message();
		$message->addTo('john@doe.com', 'John');
		$message->setFrom('jack@example.org', 'Jack');
		$message->setSubject('Hello world ' . $i);
		$message->setHtmlBody('This is body');

		return $message;
	}


	protected static function getContainerHook(Container $testContainer): ?IAppContainerHook
	{
		return new MailQueueContainerHook([
			'storage' => TestMailStorage::class,
		]);
	}
}


SendMailsCommandTest::run($containerFactory);
