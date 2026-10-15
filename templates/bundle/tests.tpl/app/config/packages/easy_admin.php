<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
	$container->extension('framework', [
		'form'   => true,
		'assets' => true,
		'router' => [
			'enabled' => true,
			'utf8'    => true,
		],
	]);
};
