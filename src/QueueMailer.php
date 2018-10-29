<?php declare(strict_types = 1);

namespace Mangoweb\MailQueue;

use Nette\Mail\IMailer;
use Nette\Mail\Message;


class QueueMailer implements IMailer
{
	/** @var IMailStorage */
	private $mailStorage;


	public function __construct(IMailStorage $mailStorage)
	{
		$this->mailStorage = $mailStorage;
	}


	public function send(Message $mail): void
	{
		$this->mailStorage->write($mail);
	}
}
