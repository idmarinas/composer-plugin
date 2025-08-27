<?php
/**
 * Copyright 2025 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 27/08/2025, 12:16
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    TitleTrait.php
 * @date    27/08/2025
 * @time    12:16
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.4.0
 */

namespace Idm\Composer\Plugin\Traits\Command\CustomizeIdmApp;

use Symfony\Component\Validator\Constraints\NoSuspiciousCharacters;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;

trait ProjectNameTrait
{
	public function projectNameForApp (?string $default = null): string
	{
		$validation = Validation::createCallable(
			new NotBlank(allowNull: false),
			new NoSuspiciousCharacters(),
		);

		return self::io()->ask('Project name', $default, $validation);
	}
}
