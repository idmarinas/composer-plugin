<?php
/**
 * Copyright $originalComment.match("Copyright (\d+)", 1, "-",$today.year)2026 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 14/10/2026, 18:58
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    ModuleImlTrait.php
 * @date    14/10/2026
 * @time    18:58
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.6.0
 */

namespace Idm\Composer\Plugin\Traits\Idea;

use DOMDocument;
use DOMNode;
use DOMXPath;

trait ModuleImlTrait
{
	private function updateImlModule(string $dir, $progress): void
	{
		$finder = self::finder('iml')->in($dir)->depth(0)->files()->name('*.iml');

		if ($finder->count() <= 0) {
			return;
		}

		$file = $finder->getIterator();
		$file->rewind();
		$file = $file->current();

		$progress->setMessage($file->getRelativePathname());

		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->load($file->getPathname());

		$xpath = new DOMXPath($dom);
		$content = $xpath->query('//component[@name="NewModuleRootManager"]/content')->item(0);

		if ($content instanceof DOMNode) {
			$this->upsertImlNode($dom, $xpath, $content, 'sourceFolder', [
				'url'           => 'file://$MODULE_DIR$/src',
				'isTestSource'  => 'false',
				'packagePrefix' => $this->info->getAutoload(),
			]);
			$this->upsertImlNode($dom, $xpath, $content, 'sourceFolder', [
				'url'           => 'file://$MODULE_DIR$/tests',
				'isTestSource'  => 'true',
				'packagePrefix' => $this->info->getAutoloadDev(),
			]);
			$this->upsertImlNode($dom, $xpath, $content, 'sourceFolder', [
				'url'           => 'file://$MODULE_DIR$/tests/app/src',
				'isTestSource'  => 'true',
				'packagePrefix' => 'App\\',
			]);

			foreach ([
				'.phpunit.cache',
				'docs/.data',
				'docs/.nuxt',
				'docs/node_modules',
				'build',
				'vendor',
				'var',
			] as $exclude) {
				$this->upsertImlNode($dom, $xpath, $content, 'excludeFolder', [
					'url' => 'file://$MODULE_DIR$/'.$exclude,
				]);
			}

			$dom->save($file->getPathname());
		}
	}
}
