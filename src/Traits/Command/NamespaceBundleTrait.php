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

use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NoSuspiciousCharacters;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;
use function Symfony\Component\String\u;

trait NamespaceBundleTrait
{
	/**
	 * (Namespace + Bundle Class Name) of the Bundle
	 */
	private function namespaceBundle (): string
	{
		$validation = Validation::createCallable(
			new NotBlank(allowNull: false),
			new NoSuspiciousCharacters(),
			new Callback(function (mixed $value, ExecutionContextInterface $context) {
				$value = str_replace('/', '\\', $value);
				$name = 'Idm\Bundle\Template\IdmTemplateBundle';
				if (strtolower($value) == strtolower($name)) {
					$context
						->buildViolation('The namespace "{{ value }}" not be equal to "{{ name }}".')
						->setParameter('{{ value }}', $value)
						->setParameter('{{ name }}', $name)
						->addViolation()
					;
				}

				$bundleClassName = u($value)
					->afterLast('\\')
					->toString()
				;

				$validator = Validation::createValidator();
				$validator
					->inContext($context)
					->validate($bundleClassName, [
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
						new Regex(
							pattern: '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/',
							message: 'The bundle name "{{ value }}" contains invalid characters.'
						),
						new Regex(
							pattern: '/^[a-zA-Z]+Bundle$/', message: 'The name of the bundle should be suffixed with "Bundle"'
						),
					])
				;
			}),
		);

		self::io()->note('Example:');
		self::io()->table(
			['Namespace', 'Bundle Class Name'],
			[
				['Idm\Bundle\Template', 'IdmTemplateBundle'],
				['Acme\Bundle\BlogBundle', 'AcmeBlogBundle'],
			]
		);

		return self::io()->ask(
			'Replace Full Class Name from "Idm\Bundle\Template\IdmTemplateBundle" to',
			null,
			$validation
		);
	}
}
