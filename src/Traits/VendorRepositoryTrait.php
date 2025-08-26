<?php
/**
 * Copyright 2024-2025 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 19/06/2025, 17:46
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    RepositoryBundleTrait.php
 * @date    13/12/2024
 * @time    22:07
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.0.0
 */

namespace Idm\Composer\Plugin\Traits;

use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NoSuspiciousCharacters;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;

trait VendorRepositoryTrait
{

	/** @var string[] */
	private static array $invalid = [];

	/**
	 * Repository name for Bundle
	 */
	protected function repositoryBundle (?string $default = null): string
	{
		self::$invalid = [
			'idmarinas/template-bundle',
			'idmarinas/idm-template-bundle',
		];

		return $this->vendorRepository($default);
	}

	/**
	 * Repository name for App
	 */
	protected function repositoryApp (?string $default = null): string
	{
		self::$invalid = [
			'idmarinas/template-symfony',
			'idmarinas/idm-template-symfony',
		];

		return $this->vendorRepository($default);
	}

	private function vendorRepository (?string $default = null): string
	{
		$validation = Validation::createCallable(
			new NotBlank(allowNull: false),
			new NoSuspiciousCharacters(),
			new Regex(
				pattern: '/[[:alnum:]]+\/{1}[[:alnum:]]+/',
				message: 'The repository "{{ value }}" has to follow the owner/repository pattern.'
			),
			new Callback(function (mixed $value, ExecutionContextInterface $context) {
				foreach (self::$invalid as $invalid) {
					if (strtolower($value) == strtolower($invalid)) {
						$context
							->buildViolation('The repository "{{ value }}" not be equal to "{{ name }}".')
							->setParameter('{{ value }}', $value)
							->setParameter('{{ name }}', implode(' or ', self::$invalid))
							->addViolation()
						;
					}
				}
			}),
		);

		self::io()->note('Remember owner/repository');

		return self::io()->ask(sprintf('Replace repository from "%s" to', self::$invalid[0]), $default, $validation);
	}
}
