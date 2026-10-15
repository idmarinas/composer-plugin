<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use const PHP_VERSION_ID;

return static function (ContainerConfigurator $container): void {
	$config = [
		'persistence' => [
			'flush_once' => true,
		],
	];

	// Foundry only deprecates the unset value from PHP 8.4 on, and refuses to
	// enable it below that, so the option must stay absent on older runtimes.
	if (PHP_VERSION_ID >= 80400) {
		$config['enable_auto_refresh_with_lazy_objects'] = true;
	}

	$container->extension('zenstruck_foundry', $config);
};
