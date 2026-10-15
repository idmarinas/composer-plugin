<?php

use Symfony\Bundle\FrameworkBundle\Controller\TemplateController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
	//$routes->import('routes/web_profiler.php');
	//$routes->import('security.route_loader.logout', 'service')->methods(['GET']);

	$routes
		->add('app_home', '/')
		->methods(['GET'])
		->controller(TemplateController::class)
		->defaults(['template' => '@IdmTemplate/home.html.twig'])
	;
};
