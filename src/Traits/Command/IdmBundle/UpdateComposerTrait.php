<?php
/**
 * Copyright $originalComment.match("Copyright (\d+)", 1, "-",$today.year)2026 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 14/10/2026, 20:16
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    UpdateComposerTrait.php
 * @date    14/10/2026
 * @time    20:16
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.6.0
 */

namespace Idm\Composer\Plugin\Traits\Command\IdmBundle;

use Composer\Composer;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;

trait UpdateComposerTrait
{
	private const COMPOSER_KEY_ORDER = [
		'name',
		'description',
		'version',
		'type',
		'keywords',
		'homepage',
		'readme',
		'time',
		'license',
		'authors',
		'support',
		'funding',
		'require',
		'require-dev',
		'conflict',
		'replace',
		'provide',
		'suggest',
		'autoload',
		'autoload-dev',
		'minimum-stability',
		'prefer-stable',
		'repositories',
		'scripts',
		'config',
		'extra',
		'bin',
		'archive',
		'abandoned',
	];

	private function updateComposerFile(Composer $composer): void
	{
		$filename = $composer->getConfig()->getConfigSource()->getName();
		$content = file_get_contents($filename);

		$autoloadDev = [
			'App\\'                       => 'tests/app/src/',
			$this->info->getAutoloadDev() => 'tests/',
		];
		$funding = [
			[
				'type' => 'paypal',
				'url'  => 'https://www.paypal.me/idmarinas',
			],
			[
				'type' => 'github-sponsors',
				'url'  => 'https://github.com/sponsors/idmarinas',
			],
		];

		$manipulator = new JsonManipulator($content);
		$manipulator->addMainKey('name', $this->info->getRepository());
		$manipulator->addMainKey('description', $this->info->getDescription());
		$manipulator->addMainKey('type', 'symfony-bundle');
		$manipulator->addMainKey('license', 'BSD-3-Clause');
		$manipulator->addMainKey('minimum-stability', 'stable');
		$manipulator->addMainKey('homepage', $this->info->getGithubUrl());
		$manipulator->addMainKey('prefer-stable', true);
		$manipulator->addMainKey('funding', $funding);
		$manipulator->addMainKey('support', ['issues' => $this->info->getGithubUrl().'/issues']);
		$manipulator->addMainKey('authors', [
			[
				'name'  => 'Iván Diaz Marinas (IDMarinas)',
				'email' => '35842929+idmarinas@users.noreply.github.com',
				'role'  => 'Author, Developer, Maintainer',
			],
		]);

		$manipulator->addSubNode('autoload', 'psr-4', [$this->info->getAutoload() => 'src/']);
		$manipulator->addSubNode('autoload-dev', 'psr-4', $autoloadDev);

		$manipulator->addListItem('keywords', 'symfony-bundle');
		$manipulator->addListItem('keywords', $this->info->getRepositoryName());

		// Scripts
		$manipulator->addSubNode('scripts', 'fixtures:load', [
			'php bin/console doctrine:schema:drop --force --ansi',
			'php bin/console doctrine:schema:update --force --ansi',
			'php bin/console doctrine:fixtures:load -n --ansi',
		]);
		$manipulator->addSubNode('scripts', 'dev:fixtures:load', '@composer fixtures:load -- --env=dev');
		$manipulator->addSubNode('scripts', 'test:fixtures:load', '@composer fixtures:load -- --env=test');

		// Extra
		$manipulator->addSubNode('extra', 'thanks', [
			'name' => $this->info->getRepository(),
			'url'  => $this->info->getGithubUrl(),
		]);

		// Config
		$manipulator->addConfigSetting('allow-plugins', [
			'composer/package-versions-deprecated' => true,
			'idmarinas/composer-plugin'            => true,
			'symfony/flex'                         => true,
			'symfony/runtime'                      => true,
		]);
		$manipulator->addConfigSetting('optimize-autoloader', false);
		$manipulator->addConfigSetting('preferred-install', ['*' => 'dist']);
		$manipulator->addConfigSetting('sort-packages', true);

		// Require packages
		$manipulator->addLink('require', 'php', '^8.3');
		$manipulator->addLink('require', 'symfony/framework-bundle', '^6.4 | ^7.1 | ^8.0');
		$manipulator->addLink('require', 'symfony/config', '^6.4 | ^7.1 | ^8.0');
		$manipulator->addLink('require', 'symfony/dependency-injection', '^6.4 | ^7.1 | ^8.0', true);

		// Require-dev packages
		$manipulator->addLink('require-dev', 'dama/doctrine-test-bundle', '^8.2');
		$manipulator->addLink('require-dev', 'doctrine/doctrine-fixtures-bundle', '^3.0 | ^4.0');
		$manipulator->addLink('require-dev', 'idmarinas/composer-plugin', '^1.0');
		$manipulator->addLink('require-dev', 'php-mock/php-mock-phpunit', '^2.12');
		$manipulator->addLink('require-dev', 'phpunit/phpunit', '^12.5');
		$manipulator->addLink('require-dev', 'rector/rector', '^2.0');
		$manipulator->addLink('require-dev', 'symfony/browser-kit', '^6.4 | ^7.1 | ^8.0');
		$manipulator->addLink('require-dev', 'symfony/console', '^6.4 | ^7.1 | ^8.0');
		$manipulator->addLink('require-dev', 'symfony/css-selector', '^6.4 | ^7.1 | ^8.0');
		$manipulator->addLink('require-dev', 'symfony/maker-bundle', '^1.61');
		$manipulator->addLink('require-dev', 'symfony/runtime', '^6.4 | ^7.1 | ^8.0');
		$manipulator->addLink('require-dev', 'symfony/twig-bundle', '^6.4 | ^7.1 | ^8.0');
		$manipulator->addLink('require-dev', 'zenstruck/foundry', '^2.3', true);

		$data = JsonFile::parseJson($manipulator->getContents());
		$data['keywords'] = array_values(array_unique($data['keywords']));

		// Sort psr-4 namespaces (Composer doesn't expose an API for this)
		foreach (['autoload', 'autoload-dev'] as $block) {
			if (isset($data[$block]['psr-4']) && is_array($data[$block]['psr-4'])) {
				uksort($data[$block]['psr-4'], static fn($a, $b) => strnatcasecmp($a, $b));
			}
		}

		// Reorder main keys in canonical order, keep the rest at the end
		$order = [];
		foreach (self::COMPOSER_KEY_ORDER as $key) {
			if (array_key_exists($key, $data)) {
				$order[] = $key;
			}
		}
		foreach (array_keys($data) as $key) {
			if (!in_array($key, $order, true)) {
				$order[] = $key;
			}

			$manipulator->removeMainKey($key);
		}

		foreach ($order as $key) {
			$manipulator->addMainKey($key, $data[$key]);
		}

		self::filesystem()->dumpFile($filename, $manipulator->getContents());
	}
}
