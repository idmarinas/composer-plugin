<?php
/**
 * Copyright $originalComment.match("Copyright (\d+)", 1, "-",$today.year)2026 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 13/10/2026, 17:12
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    CreateBundleCommand.php
 * @date    13/10/2026
 * @time    17:12
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.6.0
 */

namespace Idm\Composer\Plugin\Command;

use Composer\Command\BaseCommand;
use Exception;
use Idm\Composer\Plugin\BundleInfo;
use Idm\Composer\Plugin\Traits\BundleTrait;
use Idm\Composer\Plugin\Traits\Command\IdmBundle\CreateNamespaceBundleTrait;
use Idm\Composer\Plugin\Traits\Command\IdmBundle\UpdateComposerTrait;
use Idm\Composer\Plugin\Traits\CreateVendorRepositoryTrait;
use Idm\Composer\Plugin\Traits\FilesystemTrait;
use Idm\Composer\Plugin\Traits\IdeaTrait;
use Idm\Composer\Plugin\Traits\SymfonyStyleTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use function Symfony\Component\String\u;

final class CreateBundleCommand extends BaseCommand
{
	use FilesystemTrait;
	use LockableTrait;
	use SymfonyStyleTrait;
	use CreateNamespaceBundleTrait;
	use CreateVendorRepositoryTrait;
	use BundleTrait;
	use IdeaTrait;
	use UpdateComposerTrait;

	protected BundleInfo $info;

	/**
	 * @inheritDoc
	 */
	protected function configure(): void
	{
		$this
			->setName('idm:create:bundle')
			->setDescription('Create a Symfony Bundle')
			->setHelp(
				<<<'EOF'
The <info>%command.name%</info> helps you to create a Symfony Bundle in with your own name.

<info>%command.full_name%</info>
EOF
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		self::setInputOutput($input, $output);

		if (!$this->lock()) {
			self::io()->warning('The command is already running in another process.');

			return Command::SUCCESS;
		}

		if (!$input->isInteractive()) {
			self::io()->warning('Please run this command in interactive mode.');

			return Command::SUCCESS;
		}

		$this->info = $this->askBundleInfo();
		$composer = $this->requireComposer(true, true);
		$projectDir = dirname($composer->getConfig()->getConfigSource()->getName());
		$projectIdeaDir = $projectDir.'/.idea';

		$bundle = self::finder('bundle')
			->in(dirname(__DIR__, 2).'/templates/bundle')
			->ignoreDotFiles(false)
			->ignoreUnreadableDirs()
			->exclude(['idea'])
			->files()
			->sortByName()
		;

		$progress = self::getProgressBar();

		self::progressStart($progress, $bundle->count() + 2);

		if (self::filesystem()->exists($projectIdeaDir)) {
			$idea = self::finder('idea')
				->in(dirname(__DIR__, 2).'/templates/bundle/idea')
				->ignoreDotFiles(false)
				->ignoreUnreadableDirs()
				->files()
				->sortByName()
			;

			$progress->setMaxSteps($progress->getMaxSteps() + $idea->count());
			$progress->setMessage('Creating .idea files', 'title');

			$this->processFiles($idea, $projectIdeaDir, $progress);
			$this->processIdeaFiles($projectIdeaDir, $progress);
		}

		$progress->setMessage('Creating Bundle files', 'title');
		$this->processFiles($bundle, $projectDir, $progress);

		$this->updateComposerFile($composer);
		$progress->advance();

		self::progressFinish($progress, $this->info->getProjectName());

		return Command::SUCCESS;
	}

	private function processFiles(Finder $finder, string $dir, ProgressBar $progress): void
	{
		foreach ($finder->files() as $file) {
			$progress->setMessage($file->getRelativePathname());

			$filename = u($dir.'/'.$file->getRelativePathname())
				->replace('TPL_BUNDLE_NAME', $this->info->getBundleName())
				->replace('.tpl.php', '.php')
				->replaceMatches('/.tpl$/', '')
				->toString()
			;
			$content = $this->processContent($file);

			if (!self::filesystem()->exists($filename)) {
				self::filesystem()->dumpFile($filename, $content);
			}

			usleep(30000);
			$progress->advance();
		}
	}

	private function processContent(SplFileInfo $file): string
	{
		$content = $file->getContents();
		$ownerRepository = u($this->info->getRepository());
		$projectName = u($this->info->getProjectName());

		try {
			return u($content)
				->replace('TPL_BUNDLE_NAMESPACE', $this->info->getNamespace())
				->replace('TPL_BUNDLE_NAME', $this->info->getBundleName())
				->replace('TPL_OWNER_REPOSITORY', $ownerRepository->toString())
				->replace('TPL_OWNER', $this->info->getRepositoryVendor())
				->replace('TPL_REPOSITORY', $this->info->getRepositoryName())
				->replace('TPL_BUNDLE_DESCRIPTION', $this->info->getDescription())
				->replace('TPL_FULL_CLASS_NAME', $this->info->getFullClassName())
				->replace('TPL_TEMPLATE_BUNDLE', $this->info->getDockerName())
				->replace('TPL_PROJECT_NAME', $projectName->toString())
				->replace('TPL_SONAR_PROJECT_KEY', $ownerRepository->replace('/', '_')->toString())
				->replace('TPL_SONAR_PROJECT_NAME', $projectName->replace('IDMarinas', '')->trim()->toString())
				->replace('TPL_BRANCH', $this->info->getBranch())
				->toString()
			;
		} catch (Exception) {
			return $content;
		}
	}
}
