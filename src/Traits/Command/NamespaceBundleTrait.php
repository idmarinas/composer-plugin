<?php
/**
 * Copyright 2024 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 15/12/2024, 22:23
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    NamespaceBundleTrait.php
 * @date    13/12/2024
 * @time    22:07
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
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;

trait NamespaceBundleTrait
{

	/**
	 * Namespace for Bundle
	 */
	private function namespaceBundle (SymfonyStyle $io): string
	{
		$validation = Validation::createCallable(
			new NotBlank(allowNull: false),
			new NoSuspiciousCharacters(),
			new Callback(function (mixed $value, ExecutionContextInterface $context) {
				$name = 'Idm\Bundle\Template';
				if (strtolower($value) == strtolower($name)) {
					$context
						->buildViolation('The namespace "{{ value }}" not be equal to "{{ name }}".')
						->setParameter('{{ value }}', $value)
						->setParameter('{{ name }}', $name)
						->addViolation()
					;
				}
			}),
		);

		return $io->ask('Replace namespace from "Idm\Bundle\Template" to', null, $validation);
	}
}
