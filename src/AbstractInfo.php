<?php
/**
 * Copyright 2025 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 26/08/2025, 15:53
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    AbstractInfo.php
 * @date    26/08/2025
 * @time    15:53
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.4.0
 */

namespace Idm\Composer\Plugin;

use function Symfony\Component\String\u;

abstract class AbstractInfo
{
	protected string $repositoryVendor;
	protected string $repositoryName;
	protected string $branch;

	public function getRepository (): string
	{
		return sprintf('%s/%s', $this->getRepositoryVendor(), $this->getRepositoryName());
	}

	public function setRepository (string $repository): self
	{
		$this->repositoryVendor = u($repository)->before('/')->toString();
		$this->repositoryName = u($repository)->afterLast('/')->toString();

		return $this;
	}

	public function getRepositoryVendor (): string
	{
		return $this->repositoryVendor;
	}

	public function getRepositoryName (): string
	{
		return $this->repositoryName;
	}

	public function getBranch (): string
	{
		return $this->branch;
	}

	public function setBranch (string $branch): self
	{
		$this->branch = $branch;

		return $this;
	}

	public function getProjectName (string $name): string
	{
		return u($name)
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

	public function getGithubUrl (): string
	{
		return 'https://github.com/' . $this->getRepository();
	}

}
