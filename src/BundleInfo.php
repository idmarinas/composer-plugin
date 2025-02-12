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

use ReflectionClass;
use function Symfony\Component\String\u;

final readonly class BundleInfo
{
	private ReflectionClass $reflection;

	public function __construct (
		private string $namespace,
		private string $repository,
		private string $branch,
	) {
		$this->reflection = new ReflectionClass(str_replace('/', '\\', $this->namespace));
	}

	public function getBundleName (): string
	{
		return $this->reflection->getShortName();
	}

	public function getNamespace (): string
	{
		return $this->reflection->getNamespaceName();
	}

	public function getRepository (): string
	{
		return $this->repository;
	}

	public function getRepositoryVendor (): string
	{
		return u($this->repository)->before('/')->toString();
	}

	public function getRepositoryName (): string
	{
		return u($this->repository)->after('/')->toString();
	}

	public function getBranch (): string
	{
		return $this->branch;
	}

	public function getProjectName (): string
	{
		return u($this->getBundleName())
			->snake()
			->replace('_', ' ')
			->title(true)
			->replace('Idm', 'IDMarinas')
			->toString()
		;
	}

	public function getDockerName (): string
	{
		return u($this->getRepositoryName())
			->replace('-', '_')
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
