<?php
/**
 * Copyright 2025 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 26/08/2025, 16:15
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    AbstractCommand.php
 * @date    26/08/2025
 * @time    16:15
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.4.0
 */

namespace Idm\Composer\Plugin\Command;

use Composer\Command\BaseCommand;
use Composer\Json\JsonManipulator;
use Idm\Composer\Plugin\AbstractInfo;
use Idm\Composer\Plugin\Traits\FilesystemTrait;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use function Symfony\Component\String\u;

abstract class AbstractCommand extends BaseCommand
{
	use FilesystemTrait;

	protected AbstractInfo $info;

	protected function processFiles (Finder $finder, ProgressBar $progress): void
	{
		foreach ($finder as $file) {
			$progress->setMessage($file->getRelativePathname(), 'title');
			$progress->setMessage('Replacing information...');

			if ($file->isReadable() && $file->isWritable()) {
				if (u($file->getRelativePath())->startsWith('.idea')) {
					$this->replaceContentIdeaOfFile($file);
				} else {
					$this->replaceContentOfFile($file);
				}
			}

			$progress->advance();
		}
	}

	final protected function replaceContentIdeaOfFile (SplFileInfo $file): void
	{
		$content = $file->getContents();
		$renameFile = $this->processIdeaFile($file, $content);

		$this->saveFile($file, $renameFile, $content);
	}

	final protected function replaceContentOfFile (SplFileInfo $file): void
	{
		$content = $file->getContents();

		$renameFile = $this->processFile($file, $content);

		$this->saveFile($file, $renameFile, $content);
	}

	protected function processIdeaFile (SplFileInfo $file, string &$content): string
	{
		$renameFile = '';

		switch ($file->getFilename()) {
			case 'IDMarinas Template Bundle.iml':
			case 'IDMarinas Template Symfony.iml':
				$renameFile = u($file->getPathname())
					->replaceMatches('IDMarinas Template (Symfony|Bundle)', $this->info->getProjectName())
					->toString()
				;
				break;
			case 'Default.xml':
				if (u($file->getPathname())->containsAny('copyright')) {
					$content = u($content)
						->replaceMatches(
							'#(https://github\.com/idmarinas/(|idm-)template-(symfony|bundle))#',
							$this->info->getGithubUrl()
						)
						->toString()
					;
				}
				break;
			case 'modules.xml':
				$content = u($content)
					->replaceMatches('IDMarinas Template (Symfony|Bundle).iml', $this->info->getProjectName() . '.iml')
					->toString()
				;
				break;
			default:
				$content = u($content)
					->replaceMatches('IDMarinas Template (Symfony|Bundle)', $this->info->getProjectName())
					->toString()
				;
				break;
		}

		return $renameFile;
	}

	protected function processFile (SplFileInfo $file, string &$content): string
	{
		$renameFile = '';

		switch ($file->getFilename()) {
			case 'composer.json':
				$manipulator = new JsonManipulator($content);
				$manipulator->addMainKey('name', $this->info->getRepository());
				$manipulator->addMainKey('homepage', $this->info->getGithubUrl());
				$manipulator->addSubNode('support', 'issues', $this->info->getGithubUrl() . '/issues');
				$manipulator->addConfigSetting('allow-plugins.idmarinas/composer-plugin', false);

				$content = $manipulator->getContents();
				break;
			case 'README.md':
				$content = u($content)
					->replace('idmarinas/REPOSITORY_NAME_CHANGE_ME', $this->info->getRepository())
					->replace('BRANCH_MASTER', $this->info->getBranch())
					->replace('master', $this->info->getBranch())
					->toString()
				;
				break;
		}

		return $renameFile;
	}

	final protected function saveFile (SplFileInfo $file, string $renameFile, string $content): void
	{
		self::filesystem()->dumpFile($file->getPathname(), $content);

		if (!empty($renameFile)) {
			self::filesystem()->rename($file->getPathname(), $renameFile, true);
		}
	}
}
