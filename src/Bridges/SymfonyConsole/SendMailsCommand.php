<?php declare(strict_types = 1);

namespace Mangoweb\MailQueue\Bridges\SymfonyConsole;

use Mangoweb\CliWorker\WorkerCommand;
use Mangoweb\MailQueue\MailSender;
use Mangoweb\MailQueue\MailSenderException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;


class SendMailsCommand extends WorkerCommand
{
	/** @var string */
	protected static $defaultName = 'mango:mail-queue:send-mails';

	/** @var int */
	protected $defaultSleepTime = 3;

	/** @var MailSender */
	private $mailSender;


	public function __construct(MailSender $mailSender, LoggerInterface $logger)
	{
		parent::__construct($logger);
		$this->mailSender = $mailSender;
	}


	protected function processSingleJob(InputInterface $input): bool
	{
		try {
			$id = $this->mailSender->sendOne();
			if ($id !== null) {
				$this->logger->debug('Queued mail was successfully sent.', ['messageId' => $id]);
				return true;

			} else {
				return false;
			}

		} catch (MailSenderException $e) {
			$this->logger->error('Queued mail sending has failed.', ['exception' => $e, 'messageId' => $e->getMessageId()]);
			return true;
		}
	}
}
