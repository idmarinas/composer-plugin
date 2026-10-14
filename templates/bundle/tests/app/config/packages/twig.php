<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
	$container->extension('twig', [
		'default_path'      => dirname(__DIR__, 2).'/templates',
		'file_name_pattern' => '*.twig',
	]);
};
