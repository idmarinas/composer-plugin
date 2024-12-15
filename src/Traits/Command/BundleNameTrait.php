<?php
/**
 * Copyright 2024 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 15/12/2024, 22:23
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    BundleNameTrait.php
 * @date    13/12/2024
 * @time    22:06
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.0.0
 */

namespace Idm\Composer\Plugin\Traits\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NoSuspiciousCharacters;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;

trait BundleNameTrait
{

	/**
	 * Select name of Bundle
	 */
	private function bundleName (SymfonyStyle $io): string
	{
		$validation = Validation::createCallable(
			new NotBlank(allowNull: false),
			new NoSuspiciousCharacters(),
			new Regex(
				pattern: '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/',
				message: 'The bundle name "{{ value }}" contains invalid characters.'
			),
			new Regex(pattern: '/^[a-zA-Z]+Bundle$/', message: 'The name of the bundle should be suffixed with "Bundle"'),
			new Callback(function (mixed $value, ExecutionContextInterface $context) {
				$name = 'IdmTemplateBundle';
				if (strtolower($value) == strtolower($name)) {
					$context
						->buildViolation('The bundle name "{{ value }}" not be equal to "{{ name }}".')
						->setParameter('{{ value }}', $value)
						->setParameter('{{ name }}', $name)
						->addViolation()
					;
				}
			}),
		);

		return $io->ask('Replace name of Bundle from "IdmTemplateBundle" to', null, $validation);
	}
}
