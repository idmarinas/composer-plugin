<?php

namespace TPL_BUNDLE_NAMESPACE\Tests\Twig\Extension;

use App\Kernel;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Test\IntegrationTestCase;

/**
 * Test Twig Extensions.
 *
 */
#[Group("ignore")]
final class IntegrationTest extends IntegrationTestCase
{
	public static function getFixturesDirectory(): string
	{
		return __DIR__.'/Fixtures/';
	}

	public function getExtensions(): array
	{
		return [];
	}

	protected function getContainer(): ContainerInterface
	{
		$kernel = new Kernel('test', true);
		$kernel->addExtraConfig(dirname(__DIR__, 2).'/config/idm_advertising.php');
		$kernel->boot();

		return $kernel->getContainer();
	}
}
