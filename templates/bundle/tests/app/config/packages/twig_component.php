<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
	$container->extension('twig_component', [
		'anonymous_template_directory' => dirname(__DIR__, 2).'/components/',
	]);
};
