<?php declare(strict_types = 1);

namespace Mangoweb\MailQueue;

use Nette\Mail\IMailer;
use Nette\Mail\SendException;

class MailSender
{
	/** @var IMailStorage */
	private $mailStorage;

	/** @var IMailer */
	private $mailer;


	public function __construct(IMailStorage $mailStorage, IMailer $mailer)
	{
		$this->mailStorage = $mailStorage;
		$this->mailer = $mailer;
	}


	/**
	 * @throws MailSenderException
	 */
	public function sendOne(): ?string
	{
		try {
			$message = $this->mailStorage->fetchUnsent();
			if (!$message) {
				return null;
			}
			$this->mailer->send($message->getMessage());
			$this->mailStorage->markSent($message->getId());

			return $message->getId();
		} catch (SendException $e) {
			$this->mailStorage->markFailed($message->getId(), $e->getMessage());
			throw new MailSenderException($message, $e);
		}
	}
}
