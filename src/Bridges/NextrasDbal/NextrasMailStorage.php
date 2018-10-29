<?php declare(strict_types = 1);

namespace Mangoweb\MailQueue\Bridges\NextrasDbal;

use Mangoweb\Clock\Clock;
use Mangoweb\MailQueue\IdentifiedMessage;
use Mangoweb\MailQueue\IMailStorage;
use Nette\Mail\Message;
use Nette\Mail\MimePart;
use Nextras\Dbal\Connection;

class NextrasMailStorage implements IMailStorage
{
	private const RETRY_COUNT_LIMIT = 10;

	/** @var Connection */
	private $connection;

	/** @var null|int */
	private $currentMessage;

	/** @var string */
	private $tableName;


	public function __construct(Connection $connection, string $tableName = 'mails')
	{
		$this->connection = $connection;
		$this->tableName = $tableName;
	}


	public function write(Message $message): string
	{
		$recipients = array_keys($message->getHeader('To'));
		assert(count($recipients) > 0);

		$this->connection->query('INSERT INTO %table %values', $this->tableName, [
			'created_at%dts' => Clock::now(),
			'recipients%json' => $recipients,
			'subject' => $message->getSubject(),
			'body' => $message->getBody(),
			'message%blob' => serialize($message),
		]);
		return (string) $this->connection->getLastInsertedId();
	}


	public function fetchUnsent(): ?IdentifiedMessage
	{
		$this->connection->beginTransaction();

		$result = $this->connection->query('
			SELECT * 
			FROM %table 
			WHERE %and
			ORDER BY created_at ASC
			LIMIT 1 
			FOR UPDATE
		', $this->tableName, [
			'sent_at' => null,
			['failure_count < %i', self::RETRY_COUNT_LIMIT],
			['last_failed_at IS NULL OR last_failed_at < DATE_SUB(%dts, INTERVAL POW(2, failure_count - 1) MINUTE)', Clock::now()],
		]);

		assert($result !== null);
		$row = $result->fetch();

		if ($row === null) {
			$this->connection->commitTransaction();
			return null;
		}

		$this->currentMessage = $row->id;

		return new IdentifiedMessage((string) $row->id, unserialize($row->message, [
			'allowed_classes' => [
				Message::class,
				MimePart::class,
			],
		]));
	}


	public function markSent(string $id): void
	{
		assert(ctype_digit($id));
		assert($this->currentMessage === (int) $id);
		$this->connection->query('UPDATE %table SET sent_at = %dts WHERE id = %i', $this->tableName, Clock::now(), (int) $id);
		$this->connection->commitTransaction();
	}


	public function markFailed(string $id, string $failureMessage): void
	{
		assert(ctype_digit($id));
		assert($this->currentMessage === (int) $id);
		$this->connection->query('
			UPDATE %table 
			SET last_failed_at = %dts, failure_message = CONCAT(failure_message, %s), failure_count = failure_count + 1 
			WHERE id = %i
		', $this->tableName, Clock::now(), $failureMessage . "\n", (int) $id);
		$this->connection->commitTransaction();
	}
}
