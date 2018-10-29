<?php declare(strict_types = 1);

namespace Mangoweb\MailQueue;

use Nette\Mail\Message;

class IdentifiedMessage
{
	/** @var string */
	private $id;

	/** @var Message */
	private $message;


	public function __construct(string $id, Message $message)
	{
		$this->id = $id;
		$this->message = $message;
	}


	public function getId(): string
	{
		return $this->id;
	}


	public function getMessage(): Message
	{
		return $this->message;
	}
}
