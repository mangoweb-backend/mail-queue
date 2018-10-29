<?php declare(strict_types = 1);

namespace Mangoweb\MailQueue;

use Nette\Mail\Message;

interface IMailStorage
{
	/**
	 * @return string ID
	 */
	public function write(Message $message): string;

	public function fetchUnsent(): ?IdentifiedMessage;

	public function markSent(string $id): void;

	public function markFailed(string $id, string $failureMessage): void;
}
