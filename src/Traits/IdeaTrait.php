<?php
/**
 * Copyright $originalComment.match("Copyright (\d+)", 1, "-",$today.year)2026 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 14/10/2026, 19:03
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    IdeaTrait.php
 * @date    14/10/2026
 * @time    19:03
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.6.0
 */

namespace Idm\Composer\Plugin\Traits;

use Idm\Composer\Plugin\Traits\Idea\ModuleImlTrait;
use Idm\Composer\Plugin\Traits\Idea\UpsertImlNodeTrait;
use Symfony\Component\Console\Helper\ProgressBar;

trait IdeaTrait
{
	use UpsertImlNodeTrait;
	use ModuleImlTrait;

	private function processIdeaFiles(string $dir, ProgressBar $progress): void
	{
		$progress->setMaxSteps($progress->getMaxSteps() + 1);

		// Module file
		$this->updateImlModule($dir, $progress);
		usleep(30000);
		$progress->advance();
	}
}
