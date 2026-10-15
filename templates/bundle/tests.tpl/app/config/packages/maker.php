<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Idm\Bundle\Template\IdmTemplateBundle;
use ReflectionClass;

return static function (ContainerConfigurator $container): void {
	$container->extension('maker', [
		'root_namespace' => (new ReflectionClass(IdmTemplateBundle::class))->getNamespaceName(),
	]);
};
