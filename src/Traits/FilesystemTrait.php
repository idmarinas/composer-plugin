<?php
/**
 * Copyright 2025 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 12/02/2025, 16:21
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    FilesystemTrait.php
 * @date    12/02/2025
 * @time    16:22
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.2.0
 */

namespace Idm\Composer\Plugin\Traits;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

trait FilesystemTrait
{
	private static array $finder = [];

	protected static function filesystem (): Filesystem
	{
		return new Filesystem();
	}

	protected static function finder (null|int|string $instance = null, bool $removeBefore = false): Finder
	{
		if (null === $instance) {
			return new Finder();
		}

		if ($removeBefore) {
			unset(self::$finder[$instance]);
		}

		if (isset(self::$finder[$instance])) {
			return self::$finder[$instance];
		}

		return self::$finder[$instance] = new Finder();
	}
}
