<?php declare(strict_types = 1);

namespace Mangoweb\MailQueue;

use Mangoweb\ExceptionResponsibility\ResponsibilityThirdParty;
use Nette\Mail\SendException;

class MailSenderException extends \RuntimeException implements ResponsibilityThirdParty
{
	/** @var IdentifiedMessage */
	private $identifiedMessage;


	public function __construct(IdentifiedMessage $identifiedMessage, SendException $previous)
	{
		parent::__construct($previous->getMessage(), 0, $previous);
		$this->identifiedMessage = $identifiedMessage;
	}


	public function getMessageId(): string
	{
		return $this->identifiedMessage->getId();
	}


	public function getIdentifiedMessage(): IdentifiedMessage
	{
		return $this->identifiedMessage;
	}
}
