<?php
/**
 * Copyright 2025 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 26/08/2025, 13:46
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    AppInfo.php
 * @date    26/08/2025
 * @time    13:46
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.4.0
 */

namespace Idm\Composer\Plugin;

final class AppInfo extends AbstractInfo
{
	public function __construct (string $repository)
	{
		$this->setRepository($repository);
	}

	public function getProjectName (string $name = ''): string
	{
		return parent::getProjectName($this->repositoryName);
	}
}
