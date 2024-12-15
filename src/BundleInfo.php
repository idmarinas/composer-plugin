<?php
/**
 * Copyright 2024 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 15/12/2024, 22:23
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    BundleInfo.php
 * @date    13/12/2024
 * @time    22:29
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.0.0
 */

namespace Idm\Composer\Plugin;

use function Symfony\Component\String\u;

final readonly class BundleInfo
{
	public function __construct (
		private string $bundleName,
		private string $namespace,
		private string $repository
	) {}

	public function getBundleName (): string
	{
		return $this->bundleName;
	}

	public function getNamespace (): string
	{
		return str_replace('/', '\\', $this->namespace);
	}

	public function getRepository (): string
	{
		return $this->repository;
	}

	public function getProjectName (): string
	{
		return u($this->bundleName)
			->snake()
			->replace('_', ' ')
			->title(true)
			->replace('Idm', 'IDMarinas')
			->toString()
		;
	}

	public function getBundleClassName (): string
	{
		return $this->getNamespace() . '\\' . $this->getBundleName();
	}

	public function getGithubUrl (): string
	{
		return 'https://github.com/' . $this->getRepository();
	}

	public function getAutoload (): string
	{
		return $this->getNamespace() . '\\';
	}

	public function getAutoloadDev (): string
	{
		return $this->getAutoload() . 'Tests\\';
	}

	public function getTestSuite (): string
	{
		return $this->getProjectName() . ' Test Suite';
	}
}
