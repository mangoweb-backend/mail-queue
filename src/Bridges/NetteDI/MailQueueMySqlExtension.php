<?php declare(strict_types = 1);

namespace Mangoweb\MailQueue\Bridges\NetteDI;

use Nette\DI\CompilerExtension;
use Nextras\Migrations\Bridges\NetteDI\IMigrationGroupsProvider;
use Nextras\Migrations\Entities\Group;


class MailQueueMySqlExtension extends CompilerExtension implements IMigrationGroupsProvider
{
	public function getMigrationGroups(): array
	{
		return [
			new Group(
				'mangoweb-mailqueue-structures',
				__DIR__ . '/../NextrasMigrations/mysql/structures'
			),
		];
	}
}
