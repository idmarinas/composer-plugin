<?php
/**
 * Copyright 2024 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 15/12/2024, 22:23
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    ProgressBarTrait.php
 * @date    13/12/2024
 * @time    22:11
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.0.0
 */

namespace Idm\Composer\Plugin\Traits\Command;

use Symfony\Component\Console\Style\SymfonyStyle;

trait ProgressBarTrait
{
	private function getProgressBar (SymfonyStyle $io)
	{
		$progress = $io->createProgressBar();
		$progress->setFormat(
			" <fg=blue;bg=blue> %title:-37s% </> \n" .
			" <fg=bright-blue;bg=bright-blue> %message:-37s% </>\n" .
			" %current%/%max% %bar% %percent:3s%%\n" .
			" \xE2\x8F\xB3 %remaining:-5s% %memory:29s%\n"
		);
		$progress->setBarCharacter('<bg=green> </>');
		$progress->setEmptyBarCharacter('<bg=red> </>');
		$progress->setProgressCharacter('<bg=yellow> </>');

		return $progress;
	}
}
