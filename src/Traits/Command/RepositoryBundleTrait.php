<?php
/**
 * Copyright 2024 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 15/12/2024, 22:23
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

namespace Idm\Composer\Plugin\Traits\Command;

use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NoSuspiciousCharacters;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;

trait RepositoryBundleTrait
{
	/**
	 * Repository name
	 */
	private function repositoryBundle (): string
	{
		//
		$validation = Validation::createCallable(
			new NotBlank(allowNull: false),
			new NoSuspiciousCharacters(),
			new Regex(
				pattern: '/[[:alnum:]]+\/{1}[[:alnum:]]+/',
				message: 'The repository "{{ value }}" has to follow the username/repository-name pattern.'
			),
			new Callback(function (mixed $value, ExecutionContextInterface $context) {
				$name = 'idmarinas/idm-template-bundle';
				$nameAlt = 'idmarinas/template-bundle';
				if (strtolower($value) == $name || strtolower($value) == $nameAlt) {
					$context
						->buildViolation('The repository "{{ value }}" not be equal to "{{ name }} or {{ name_alt }}".')
						->setParameter('{{ value }}', $value)
						->setParameter('{{ name }}', $name)
						->setParameter('{{ name_alt }}', $nameAlt)
						->addViolation()
					;
				}
			}),
		);
		self::io()->note('Remember username/repository-name');

		return self::io()->ask('Replace repository from "idmarinas/template-bundle" to', null, $validation);
	}
}
