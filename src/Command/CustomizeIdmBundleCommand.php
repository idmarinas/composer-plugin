<?php
/**
 * Copyright 2024 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 15/12/2024, 22:23
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    CustomizeIdmBundleCommand.php
 * @date    10/12/2024
 * @time    18:45
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.0.0
 */

namespace Idm\Composer\Plugin\Command;

use Composer\Command\BaseCommand;
use Composer\Json\JsonManipulator;
use Idm\Composer\Plugin\BundleInfo;
use Idm\Composer\Plugin\Traits\Command\BundleNameTrait;
use Idm\Composer\Plugin\Traits\Command\NamespaceBundleTrait;
use Idm\Composer\Plugin\Traits\Command\ProgressBarTrait;
use Idm\Composer\Plugin\Traits\Command\RepositoryBundleTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use function Symfony\Component\String\u;

final class CustomizeIdmBundleCommand extends BaseCommand
{
	use LockableTrait;
	use BundleNameTrait;
	use NamespaceBundleTrait;
	use ProgressBarTrait;
	use RepositoryBundleTrait;

	protected function configure (): void
	{
		$this
			->setName('idm:customize:bundle')
			->setDescription('Customize Idm Bundle')
			->setHelp(
				<<<'EOF'
The <info>%command.name%</info> helps you to customize the IdmTemplateBundle with your own name and namespace.

<info>%command.full_name%</info>
EOF
			)
		;
	}

	protected function execute (InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);

		if (!$this->lock()) {
			$io->warning('The command is already running in another process.');

			return Command::SUCCESS;
		}

		if (!$input->isInteractive()) {
			$io->warning('Please run this command in interactive mode.');
			$io->note('If you want to change the namespace and other names for your bundle.');

			return Command::SUCCESS;
		}

		do {
			$bundleName = $this->bundleName($io);
			$namespace = $this->namespaceBundle($io);
			$repository = $this->repositoryBundle($io);

			// Information
			$io->title('Information of your Bundle');
			$io->text('<fg=blue>Bundle name:</> ' . $bundleName);
			$io->text('<fg=blue>Namespace:</> ' . $namespace);
			$io->text('<fg=blue>Repository name:</> ' . $repository);

			$answer = $io->confirm('Is this information correct?');
		} while (!$answer);

		$composer = $this->requireComposer(true, true);

		$finder = (new Finder())
			->in(dirname($composer->getConfig()->getConfigSource()->getName()))
			->ignoreDotFiles(false)
			->ignoreVCSIgnored(true)
			->ignoreUnreadableDirs()
			->exclude(
				['bundles', 'docs', '.docker']
			)
			->notName(['.editorconfig', '.gitkeep',])
			->files()
			->sortByName()
		;

		$progress = $this->getProgressBar($io);
		$progress->setMessage('Preparing files...', 'title');
		$progress->setMessage('Analyzing bundle files...');
		$progress->start($finder->count());

		$filesystem = new Filesystem();
		$bundleInfo = new BundleInfo($bundleName, $namespace, $repository);

		// Update files
		foreach ($finder as $file) {
			$progress->setMessage($file->getRelativePathname(), 'title');
			$progress->setMessage('Replacing information...');

			if ($file->isReadable() && $file->isWritable()) {
				if ('.idea' == $file->getRelativePath()) {
					$this->replaceContentIdeaOfFile($file, $bundleInfo, $filesystem);
				}
				else {
					$this->replaceContentOfFile($file, $bundleInfo, $filesystem);
				}
			}

			$progress->advance();
		}

		// Finish progress
		$progress->setMessage("<fg=green;bg=blue>\xF0\x9F\x97\xB8</> $bundleName ", 'title');
		$progress->setMessage("<fg=bright-green;bg=bright-blue>\xF0\x9F\x97\xB9</> Customized successfully ");
		$progress->finish();

		return Command::SUCCESS;
	}

	private function replaceContentIdeaOfFile (SplFileInfo $file, BundleInfo $bundleInfo, Filesystem $filesystem): void
	{
		$content = $file->getContents();
		$renameFile = $this->processIdeaFile($file, $content, $bundleInfo);

		$this->saveFile($file, $renameFile, $content, $filesystem);
	}

	private function replaceContentOfFile (SplFileInfo $file, BundleInfo $bundleInfo, Filesystem $filesystem): void
	{
		$content = $file->getContents();

		$content = u($content)
			->replaceMatches('/Copyright \d+ (C)/', 'Copyright ' . date('Y') . ' (C)')
			->replaceMatches('/@date +\d{2}\/\d{2}\/\d{2}/', '@date    ' . date('d/m/Y'))
			->replaceMatches('/@time +\d{2}:\d{2}/', '@time    ' . date('H:i'))
			->replace('use Idm\Bundle\Template\IdmTemplateBundle;', 'use ' . $bundleInfo->getBundleClassName() . ';')
			->replace('new IdmTemplateBundle();', 'new ' . $bundleInfo->getBundleName() . '();')
			->replaceMatches('/(use|namespace) (Idm\\\Bundle\\\Template)(;|\\\)/', function ($match) use ($bundleInfo) {
				return sprintf('%s %s%s', $match[1], $bundleInfo->getNamespace(), $match[3]);
			})
			->replace('Idm\Bundle\Template\\', $bundleInfo->getNamespace() . '\\')
			->toString()
		;

		$renameFile = $this->processFile($file, $content, $bundleInfo);

		$this->saveFile($file, $renameFile, $content, $filesystem);
	}

	private function processFile (SplFileInfo $file, string &$content, BundleInfo $bundleInfo): string
	{
		$renameFile = '';

		switch ($file->getFilename()) {
			case 'IdmTemplateBundle.php':
				$renameFile = str_replace('IdmTemplateBundle', $bundleInfo->getBundleName(), $file->getPathname());
				$content = u($content)->replace('class IdmTemplateBundle', 'class ' . $bundleInfo->getBundleName())->toString();
				break;
			case 'composer.json':
				$manipulator = new JsonManipulator($content);
				$manipulator->addMainKey('type', 'symfony-bundle');
				$manipulator->addMainKey('name', $bundleInfo->getRepository());
				$manipulator->addMainKey('homepage', $bundleInfo->getGithubUrl());
				$manipulator->addMainKey('keywords', ['symfony-bundle']);
				$manipulator->addSubNode('support', 'issues', $bundleInfo->getGithubUrl() . '/issues');
				$manipulator->addSubNode('autoload', 'psr-4', [$bundleInfo->getAutoload() => 'src/']);
				$manipulator->addSubNode('autoload-dev', 'psr-4', [$bundleInfo->getAutoloadDev() => 'tests/']);
				$manipulator->addConfigSetting('allow-plugins.idmarinas/composer-plugin', false);

				$content = $manipulator->getContents();
				break;
			case 'phpunit.xml.dist':
			case 'phpunit.xml':
				$content = u($content)
					->replace('IDMarinas Template Bundle Test Suite', $bundleInfo->getTestSuite())
					->toString()
				;
				break;
			case '.gitignore':
				$content = u($content)->replaceMatches(
					'/###(<|>) idmarinas\/template-bundle ###/',
					fn($match) => sprintf('###%s %s ###', $match[1], $bundleInfo->getRepository())
				);
				break;
			case 'README.md':
				$finder = (new Finder())
					->in(dirname(__DIR__, 2) . '/templates')
					->files()
					->name('readme.tpl.md')
				;

				if ($finder->hasResults()) {
					$file = $finder->getIterator()->current()->getContents();
					$file = u($file)
						->replace('<package-name>', $bundleInfo->getRepository())
						->replace('<vendor>\<bundle-name>\<bundle-long-name>', $bundleInfo->getBundleClassName())
						->toString()
					;
					$content = u($content)
						->replaceMatches(
							'/<!-- readme-template -->.+<!-- readme-template -->/',
							fn($match) => $file
						)
						->replace('idmarinas/template-bundle', $bundleInfo->getRepository())
						->replace('idmarinas/REPOSITORY_NAME_CHANGE_ME', $bundleInfo->getRepository())
						->replace('# IDMarinas Template Bundle', '# ' . $bundleInfo->getProjectName())
					;
				}
				break;
		}

		return $renameFile;
	}

	private function processIdeaFile (SplFileInfo $file, string &$content, BundleInfo $bundleInfo): string
	{
		$renameFile = '';

		switch ($file->getFilename()) {
			case '.name':
				$content = u($content)->replace('IDMarinas Template Bundle', $bundleInfo->getProjectName())->toString();
				break;
			case 'IDMarinas Template Bundle.iml':
				$renameFile = u($file->getPathname())
					->replace('IDMarinas Template Bundle', $bundleInfo->getProjectName())
					->toString()
				;
				break;
			case 'Default.xml':
				if (u($file->getPathname())->containsAny('copyright')) {
					$content = u($content)
						->replace('https://github.com/idmarinas/idm-template-bundle', $bundleInfo->getGithubUrl())
						->replaceMatches('/Copyright \d+ (C)/', 'Copyright ' . date('Y') . ' (C)')
						->replaceMatches('/@date +\d{2}\/\d{2}\/\d{2}/', '@date    ' . date('d/m/Y'))
						->replaceMatches('/@time +\d{2}:\d{2}/', '@time    ' . date('H:i'))
						->replaceMatches('/@project +IDMarinas Template Bundle/', '@project ' . $bundleInfo->getProjectName())
					;
				}
				break;
			case 'modules.xml':
				$content = u($content)->replace('IDMarinas Template Bundle.iml', $bundleInfo->getProjectName() . '.iml');
				break;
		}

		return $renameFile;
	}

	private function saveFile (SplFileInfo $file, string $renameFile, string $content, Filesystem $filesystem): void
	{
		$filesystem->dumpFile($file->getPathname(), $content);

		if (!empty($renameFile)) {
			$filesystem->rename($file->getPathname(), $renameFile, true);
		}
	}
}
