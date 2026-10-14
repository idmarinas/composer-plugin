<?php
/**
 * Copyright $originalComment.match("Copyright (\d+)", 1, "-",$today.year)2026 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 14/10/2026, 17:48
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    CreateNamespaceBundleTrait.php
 * @date    14/10/2026
 * @time    19:12
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.6.0
 */

namespace Idm\Composer\Plugin\Traits\Command\IdmBundle;

use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NoSuspiciousCharacters;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;
use function Symfony\Component\String\u;

trait CreateNamespaceBundleTrait
{
	/**
	 * (Namespace + Bundle Class Name) of the Bundle
	 */
	private function namespaceBundle(): string
	{
		$validation = Validation::createCallable(
			new NotBlank(allowNull: false),
			new NoSuspiciousCharacters(),
			new Callback(function (mixed $value, ExecutionContextInterface $context) {
				$value = str_replace('/', '\\', $value);
				$bundleClassName = u($value)
					->afterLast('\\')
					->toString()
				;

				$validator = Validation::createValidator();
				$validator
					->inContext($context)
					->validate($bundleClassName, [
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
				['Idm\Bundle\TemplateBundle', 'IdmTemplateBundle'],
				['Acme\Bundle\BlogBundle', 'AcmeBlogBundle'],
			]
		);

		// todo eliminar el default
		return self::io()->ask(
			'Full Class Name for your bundle',
			'Idm\Bundle\MediaBundle\IdmMediaBundle',
			$validation
		);
	}
}
