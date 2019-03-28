<?php declare(strict_types = 1);

namespace Mangoweb\MailQueue\Bridges\NetteDI;

use Mangoweb\MailQueue\Bridges\NextrasDbal\NextrasMailStorage;
use Mangoweb\MailQueue\Bridges\SymfonyConsole\SendMailsCommand;
use Mangoweb\MailQueue\MailSender;
use Mangoweb\MailQueue\QueueMailer;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\Mail\IMailer;
use Nextras\Dbal\Connection;


class MailQueueExtension extends CompilerExtension
{
	/** @var array */
	public $defaults = [
		'storage' => null,
		'registerCommand' => false,
	];


	public function __construct()
	{
		$this->defaults['storage'] = class_exists(Connection::class) ? 'nextras' : null;
		$this->defaults['registerCommand'] = PHP_SAPI === 'cli';
	}


	public function loadConfiguration()
	{
		parent::loadConfiguration();

		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('queueMailer'))
			->setClass(QueueMailer::class)
			->setAutowired('self');

		$builder->addDefinition($this->prefix('mailSender'))
			->setClass(MailSender::class);

		$config = $this->validateConfig($this->defaults);

		if ($config['registerCommand']) {
			$builder->addDefinition($this->prefix('sendMailCommand'))
				->setClass(SendMailsCommand::class);
		}

		assert($config['storage'] !== null);
		$storageDefinition = $builder->addDefinition($this->prefix('storage'));

		if ($config['storage'] === 'nextras') {
			$storageDefinition->setClass(NextrasMailStorage::class);

		} else {
			Compiler::loadDefinition($storageDefinition, $config['storage']);
		}
	}


	public function beforeCompile()
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		$innerMailer = $builder->getDefinitionByType(IMailer::class);
		$autoWired = $innerMailer->getAutowired();

		if ($autoWired === true || (is_array($autoWired) && in_array(IMailer::class, $autoWired, true))) {
			$innerMailer->setAutowired($innerMailer->getType() === IMailer::class ? false : 'self');
		}

		$builder->getDefinition($this->prefix('mailSender'))
			->setArguments(['mailer' => $innerMailer]);

		$builder->getDefinition($this->prefix('queueMailer'))
			->setAutowired(IMailer::class);
	}
}
