<?php declare(strict_types = 1);

namespace MangowebTests\MailQueue\Inc;

use Mangoweb\MailQueue\IdentifiedMessage;
use Mangoweb\MailQueue\IMailStorage;
use Nette\Mail\Message;


class TestMailStorage implements IMailStorage
{
	/** @var int */
	public $id = 0;

	/** @var IdentifiedMessage[] */
	public $messages = [];

	/** @var int[] */
	public $sent = [];

	/** @var int[] */
	public $failed = [];


	public function write(Message $message): string
	{
		$id = ++$this->id;
		$this->messages[$id] = new IdentifiedMessage((string) $id, $message);
		return (string) $id;
	}


	public function fetchUnsent(): ?IdentifiedMessage
	{
		return array_shift($this->messages) ?: null;
	}


	public function markSent(string $id): void
	{
		$this->sent[] = (int) $id;
	}


	public function markFailed(string $id, string $failureMessage): void
	{
		$this->failed[] = (int) $id;
	}
}
