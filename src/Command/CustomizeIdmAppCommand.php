<?php
/**
 * Copyright 2025 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 26/08/2025, 13:39
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    CustomizeIdmAppCommand.php
 * @date    26/08/2025
 * @time    13:39
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.4.0
 */

declare(strict_types=1);

namespace Idm\Composer\Plugin\Command;

use Composer\Json\JsonManipulator;
use Idm\Composer\Plugin\AbstractInfo;
use Idm\Composer\Plugin\AppInfo;
use Idm\Composer\Plugin\Traits\Command\CustomizeIdmBundle\DefaultBranchTrait;
use Idm\Composer\Plugin\Traits\SymfonyStyleTrait;
use Idm\Composer\Plugin\Traits\VendorRepositoryTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;
use function Symfony\Component\String\u;

final class CustomizeIdmAppCommand extends AbstractCommand
{
	use DefaultBranchTrait;
	use LockableTrait;
	use VendorRepositoryTrait;
	use SymfonyStyleTrait;

	protected AbstractInfo|AppInfo $info;

	/**
	 * @inheritDoc
	 */
	protected function configure (): void
	{
		$this
			->setName('idm:customize:app')
			->setDescription('Customize Idm Template Symfony')
			->setHelp(
				<<<'EOF'
The <info>%command.name%</info> helps you to customize the IdmTemplateSymfony with your own name.

<info>%command.full_name%</info>
EOF
			)
		;
	}

	protected function execute (InputInterface $input, OutputInterface $output): int
	{
		self::setInputOutput($input, $output);

		if (!$this->lock()) {
			self::io()->warning('The command is already running in another process.');

			return Command::SUCCESS;
		}

		if (!$input->isInteractive()) {
			self::io()->warning('Please run this command in interactive mode.');
			self::io()->note('If you want to change the owner/repository for your App.');

			return Command::SUCCESS;
		}

		do {
			$repository = $this->repositoryApp();
			$this->info = new AppInfo($repository);
			$branch = $this->defaultBranch();

			$this->info->setBranch($branch);

			// Information
			self::io()->title('Information of your App');
			self::io()->text('<fg=blue>Project name:</> ' . $this->info->getProjectName());
			self::io()->text('<fg=blue>Repository name:</> ' . $this->info->getRepository());
			self::io()->text('<fg=blue>Branch name:</> ' . $this->info->getBranch());

			$answer = self::io()->confirm('Is this information correct?');
		} while (!$answer);

		$composer = $this->requireComposer(true, true);

		$finder = self::finder('files_app')
			->in(dirname($composer->getConfig()->getConfigSource()->getName()))
			->ignoreDotFiles(false)
			->ignoreVCSIgnored(true)
			->ignoreUnreadableDirs()
			->exclude(['bundles', 'docs', '.docker'])
			->notName(['.editorconfig', '.gitkeep'])
			->files()
			->sortByName()
		;

		// Nothing is done if no files have been found.
		if (!$finder->hasResults()) {
			return Command::SUCCESS;
		}

		$progress = self::getProgressBar();
		self::progressStart($progress, $finder->count());

		// Update files
		$this->processFiles($finder, $progress);

		// Finish progress
		self::progressFinish($progress, $this->info->getProjectName());

		return Command::SUCCESS;
	}

	protected function processFile (SplFileInfo $file, string &$content): string
	{
		$renameFile = '';

		$content = u($content)
			->replaceMatches('/Copyright \d{4} (C)/', 'Copyright ' . date('Y') . ' (C)')
			->replaceMatches('#@date( +)\d{2}/\d{2}/\d{4}#', '@date${1}' . date('d/m/Y'))
			->replaceMatches('/@time( +)\d{2}:\d{2}/', '@time${1}' . date('H:i'))
			->replace('IDMarinas Template Symfony', $this->info->getProjectName())
			->replaceMatches('#idmarinas/(|idm-)template-symfony#', $this->info->getRepository())
			->replace('name: template_symfony', 'name: ' . $this->info->getDockerName())
			->toString()
		;

		switch ($file->getFilename()) {
			case 'composer.json':
				parent::processFile($file, $content);

				$manipulator = new JsonManipulator($content);
				$manipulator->addMainKey('type', 'project');
				$manipulator->removeMainKey('keywords');

				$content = $manipulator->getContents();
				break;
			case 'README.md':
				parent::processFile($file, $content);

				$content = u($content)
					->replaceMatches('/<!-- readme-template -->(?s:.)+<!-- readme-template -->/', '> Here go your content')
					->toString()
				;
				break;
			default:
				return parent::processFile($file, $content);
		}

		return $renameFile;
	}
}
