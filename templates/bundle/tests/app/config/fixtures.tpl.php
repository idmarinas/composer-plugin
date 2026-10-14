<?php

use TPL_FULL_CLASS_NAME;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container, ContainerBuilder $builder): void {
	$namespace = (new ReflectionClass(TPL_BUNDLE_NAME::class))->getNamespaceName();

	// @formatter:off
	$container->services()
		->load($namespace.'\\Tests\\DataFixtures\\', $builder->getParameter('kernel.project_dir') . '/tests/DataFixtures')
		->public()
		->autowire()
		->autoconfigure()
	;
};
