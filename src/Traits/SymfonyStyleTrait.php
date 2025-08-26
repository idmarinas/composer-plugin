<?php
/**
 * Copyright 2025 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 12/02/2025, 16:21
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    SymfonyStyleTrait.php
 * @date    12/02/2025
 * @time    16:22
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.2.0
 */

namespace Idm\Composer\Plugin\Traits;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

trait SymfonyStyleTrait
{
	private static SymfonyStyle    $symfonyStyle;
	private static InputInterface  $input;
	private static OutputInterface $output;

	public static function io (): SymfonyStyle
	{
		return self::getSymfonyStyle();
	}

	public static function setInputOutput (InputInterface $input, OutputInterface $output): void
	{
		self::$input = $input;
		self::$output = $output;
	}

	public static function getProgressBar (int $int = 0): ProgressBar
	{
		$progress = self::io()->createProgressBar($int);
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

	public static function progressStart (ProgressBar $progress, int $start): void
	{
		$progress->setMessage('Preparing files...', 'title');
		$progress->setMessage('Analyzing files...');
		$progress->start($start);
	}

	public static function progressFinish (ProgressBar $progress, string $name): void
	{
		$progress->setMessage("<fg=green;bg=blue>\xF0\x9F\x97\xB8</> {$name} ", 'title');
		$progress->setMessage("<fg=bright-green;bg=bright-blue>\xF0\x9F\x97\xB9</> Customized successfully ");
		$progress->finish();
	}

	private static function getSymfonyStyle (): SymfonyStyle
	{
		if (!isset(self::$symfonyStyle) || !self::$symfonyStyle instanceof SymfonyStyle) {
			self::$symfonyStyle = new SymfonyStyle(self::$input, self::$output);
		}

		return self::$symfonyStyle;
	}
}
