<?php declare(strict_types = 1);

use Mangoweb\Tester\Infrastructure\InfrastructureConfigurator;


require __DIR__ . '/../vendor/autoload.php';

$configurator = new InfrastructureConfigurator(__DIR__ . '/../temp');
$configurator->setupTester();

$configurator->addParameters([
	'packageRootDir' => __DIR__ . '/../',
	'configDir' => __DIR__ . '/config/',
]);

$configurator->addConfig(__DIR__ . '/config/infrastructure.neon');
$configurator->addConfig(__DIR__ . '/config/local.neon');

return $configurator->getContainerFactory();
