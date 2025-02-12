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

final class BundleInfo
{
	private readonly string $fullClassName;
	private string          $branch;
	private string          $repositoryVendor;
	private string          $repositoryName;

	public function __construct (string $namespace)
	{
		$this->fullClassName = str_replace('/', '\\', $namespace);
		$this->repositoryVendor = u($this->fullClassName)->before('\\')->replace('Idm', 'idmarinas')->toString();
		$this->repositoryName = u($this->fullClassName)
			->afterLast('\\')->snake()
			->replaceMatches('(_|idm_)', '-')->trim('-')
			->toString()
		;
	}

	public function getBundleName (): string
	{
		return u($this->fullClassName)->afterLast('\\')->toString();
	}

	public function getNamespace (): string
	{
		return u($this->fullClassName)->beforeLast('\\')->toString();
	}

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

	public function getProjectNameInitials (): string
	{
		$array = u($this->getProjectName())
			->lower()
			->split(' ')
		;

		$array = array_map(fn($value) => $value[0], $array);

		return implode('', $array);
	}

	public function getDockerName (): string
	{
		return u($this->getRepositoryName())
			->replace('-', '_')
			->toString()
		;
	}

	public function geFullClassName (): string
	{
		return $this->fullClassName;
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
