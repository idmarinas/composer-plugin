<?php
/**
 * Copyright 2024 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 15/12/2024, 22:23
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    Plugin.php
 * @date    10/12/2024
 * @time    18:34
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.0.0
 */

namespace Idm\Composer\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Idm\Composer\Plugin\Command\CustomizeIdmBundleCommand;

class Plugin implements PluginInterface, Capable, CommandProvider
{
	public function getCapabilities (): array
	{
		return [
			CommandProvider::class => self::class,
		];
	}

	public function getCommands (): array
	{
		return [
			new CustomizeIdmBundleCommand(),
		];
	}

	/**
	 * @inheritDoc
	 */
	public function activate (Composer $composer, IOInterface $io): void {}

	/**
	 * @inheritDoc
	 */
	public function deactivate (Composer $composer, IOInterface $io): void {}

	/**
	 * @inheritDoc
	 */
	public function uninstall (Composer $composer, IOInterface $io): void {}
}
