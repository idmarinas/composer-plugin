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

final class BundleInfo extends AbstractInfo
{
	private readonly string $fullClassName;

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

	public function getProjectName (string $name = ''): string
	{
		return parent::getProjectName($this->getBundleName());
	}

	public function getProjectNameInitials (): string
	{
		$array = u($this->getProjectName())
			->lower()
			->split(' ')
		;

		$array = array_map(fn($value) => $value->slice(0, 1)->toString(), $array);

		return implode('', $array);
	}

	public function geFullClassName (): string
	{
		return $this->fullClassName;
	}

	public function getAutoload (): string
	{
		return $this->getNamespace() . '\\';
	}

	public function getAutoloadDev (): string
	{
		return $this->getAutoload() . 'Tests\\';
	}
}
