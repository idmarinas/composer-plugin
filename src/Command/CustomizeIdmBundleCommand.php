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
use Idm\Composer\Plugin\Traits\Command\DefaultBranchTrait;
use Idm\Composer\Plugin\Traits\Command\NamespaceBundleTrait;
use Idm\Composer\Plugin\Traits\Command\RepositoryBundleTrait;
use Idm\Composer\Plugin\Traits\FilesystemTrait;
use Idm\Composer\Plugin\Traits\SymfonyStyleTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;
use function Symfony\Component\String\u;

final class CustomizeIdmBundleCommand extends BaseCommand
{
	use LockableTrait;
	use DefaultBranchTrait;
	use FilesystemTrait;
	use NamespaceBundleTrait;
	use RepositoryBundleTrait;
	use SymfonyStyleTrait;

	private BundleInfo $bundle;

	protected function configure (): void
	{
		$this
			->setName('idm:customize:bundle')
			->setDescription('Customize Idm Template Bundle')
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
		self::setInputOutput($input, $output);

		if (!$this->lock()) {
			self::io()->warning('The command is already running in another process.');

			return Command::SUCCESS;
		}

		if (!$input->isInteractive()) {
			self::io()->warning('Please run this command in interactive mode.');
			self::io()->note('If you want to change the namespace and other names for your bundle.');

			return Command::SUCCESS;
		}

		do {
			$namespace = $this->namespaceBundle();
			$this->bundle = new BundleInfo($namespace);
			$repository = $this->repositoryBundle($this->bundle->getRepository());
			$branch = $this->defaultBranch();

			$this->bundle->setRepository($repository);
			$this->bundle->setBranch($branch);

			// Information
			self::io()->title('Information of your Bundle');
			self::io()->text('<fg=blue>Bundle name:</> ' . $this->bundle->getBundleName());
			self::io()->text('<fg=blue>Namespace:</> ' . $this->bundle->getNamespace());
			self::io()->text('<fg=blue>Repository name:</> ' . $this->bundle->getRepository());
			self::io()->text('<fg=blue>Branch:</> ' . $this->bundle->getBranch());

			$answer = self::io()->confirm('Is this information correct?');
		} while (!$answer);

		$composer = $this->requireComposer(true, true);

		$finder = self::finder('files')
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

		// Nothing is done if no files have been found.
		if ($finder->count() < 0) {
			return Command::SUCCESS;
		}

		$progress = self::getProgressBar();
		$progress->setMessage('Preparing files...', 'title');
		$progress->setMessage('Analyzing bundle files...');
		$progress->start($finder->count());

		// Update files
		foreach ($finder as $file) {
			$progress->setMessage($file->getRelativePathname(), 'title');
			$progress->setMessage('Replacing information...');

			if ($file->isReadable() && $file->isWritable()) {
				if ('.idea' == $file->getRelativePath()) {
					$this->replaceContentIdeaOfFile($file);
				} else {
					$this->replaceContentOfFile($file);
				}
			}

			$progress->advance();
		}

		// Finish progress
		$progress->setMessage("<fg=green;bg=blue>\xF0\x9F\x97\xB8</> {$this->bundle->getBundleName()} ", 'title');
		$progress->setMessage("<fg=bright-green;bg=bright-blue>\xF0\x9F\x97\xB9</> Customized successfully ");
		$progress->finish();

		return Command::SUCCESS;
	}

	private function replaceContentIdeaOfFile (SplFileInfo $file): void
	{
		$content = $file->getContents();
		$renameFile = $this->processIdeaFile($file, $content);

		$this->saveFile($file, $renameFile, $content);
	}

	private function replaceContentOfFile (SplFileInfo $file): void
	{
		$content = $file->getContents();

		$renameFile = $this->processFile($file, $content);

		$content = u($content)
			->replaceMatches('/Copyright \d{4} (C)/', 'Copyright ' . date('Y') . ' (C)')
			->replaceMatches('#@date( +)\d{2}/\d{2}/\d{4}#', '@date$1' . date('d/m/Y'))
			->replaceMatches('/@time( +)\d{2}:\d{2}/', '@time$1' . date('H:i'))
			->replace('IDMarinas Template Bundle', $this->bundle->getProjectName())
			->replace('Idm\Bundle\Template\IdmTemplateBundle', $this->bundle->geFullClassName())
			->replace('Idm\Bundle\Template', $this->bundle->getNamespace())
			->replace('IdmTemplateBundle', $this->bundle->getBundleName())
			->replace('/idmarinas/(|idm-)template-bundle/', $this->bundle->getRepository())
			->replace('name: template_bundle', 'name: ' . $this->bundle->getDockerName())
			->replace(
				"INSTANCE: 'Writerside/itb'",
				sprintf("INSTANCE: 'Writerside/%s'", $this->bundle->getProjectNameInitials())
			)
			->replace('SONAR_PROJECT_NAME_CHANGE_ME', u($this->bundle->getRepository())->replace('/', '_')->toString())
			->replaceMatches(
				'/(sonar.projectName=)(.*)/',
				'$1' . u($this->bundle->getProjectName())->after(' ')->toString()
			)
			->toString()
		;

		$this->saveFile($file, $renameFile, $content);
	}

	private function processFile (SplFileInfo $file, string &$content): string
	{
		$renameFile = '';

		switch ($file->getFilename()) {
			case 'IdmTemplateBundle.php':
				$renameFile = u($file->getPathname())->replace('IdmTemplateBundle', $this->bundle->getBundleName())->toString();
				break;
			case 'composer.json':
				$manipulator = new JsonManipulator($content);
				$manipulator->addMainKey('type', 'symfony-bundle');
				$manipulator->addMainKey('name', $this->bundle->getRepository());
				$manipulator->addMainKey('homepage', $this->bundle->getGithubUrl());
				$manipulator->addMainKey('keywords', ['symfony-bundle']);
				$manipulator->addSubNode('support', 'issues', $this->bundle->getGithubUrl() . '/issues');
				$manipulator->addSubNode('autoload', 'psr-4', [$this->bundle->getAutoload() => 'src/']);
				$manipulator->addSubNode('autoload-dev', 'psr-4', [
					'App\\'                         => 'app/src/',
					$this->bundle->getAutoloadDev() => 'tests/',
					'DataFixtures\\'                => 'fixtures/',
					'Factory\\'                     => 'factories/',
				]);
				$manipulator->addConfigSetting('allow-plugins.idmarinas/composer-plugin', false);

				$content = $manipulator->getContents();
				break;
			case 'README.md':
				$finder = self::finder('readme')
					->in(dirname(__DIR__, 2) . '/templates')
					->files()
					->name('readme.tpl.md')
				;

				if ($finder->hasResults()) {
					$file = $finder->getIterator();
					$file->rewind();
					$file = $file->current()->getContents();
					$file = u($file)
						->replace('<package-name>', $this->bundle->getRepository())
						->replace('<vendor>\<bundle-name>\<bundle-long-name>', $this->bundle->geFullClassName())
						->toString()
					;
					$content = u($content)
						->replaceMatches('/<!-- readme-template -->(?s:.)+<!-- readme-template -->/', $file)
						->replace('idmarinas/REPOSITORY_NAME_CHANGE_ME', $this->bundle->getRepository())
						->replace('BRANCH_MASTER', $this->bundle->getBranch())
						->replace('master', $this->bundle->getBranch())
						->toString()
					;
				}
				break;

			case 'itb.tree':
				$renameFile = u($file->getPathname())
					->replace('itb.tree', $this->bundle->getProjectNameInitials() . '.tree')
					->toString()
				;
				$content = u($content)
					->replace('id="itb"', sprintf('id="%s"', $this->bundle->getProjectNameInitials()))
					->toString()
				;
				break;
			case 'writerside.cfg':
				$content = u($content)
					->replace('src="itb.tree"', sprintf('src="%s.tree"', $this->bundle->getProjectNameInitials()))
					->toString()
				;
				break;

			case 'v.list':
				$content = u($content)
					->replace('name="branch" value="1.x"', sprintf('name="branch" value="%s"', $this->bundle->getBranch()))
					->toString()
				;
				break;
		}

		return $renameFile;
	}

	private function processIdeaFile (SplFileInfo $file, string &$content): string
	{
		$renameFile = '';

		switch ($file->getFilename()) {
			case '.name':
				$content = u($content)->replace('IDMarinas Template Bundle', $this->bundle->getProjectName())->toString();
				break;
			case 'IDMarinas Template Bundle.iml':
				$renameFile = u($file->getPathname())
					->replace('IDMarinas Template Bundle', $this->bundle->getProjectName())
					->toString()
				;
				break;
			case 'Default.xml':
				if (u($file->getPathname())->containsAny('copyright')) {
					$content = u($content)
						->replaceMatches('#(https://github\.com/idmarinas/(|idm-)template-bundle)#', $this->bundle->getGithubUrl())
						->toString()
					;
				}
				break;
			case 'modules.xml':
				$content = u($content)
					->replace('IDMarinas Template Bundle.iml', $this->bundle->getProjectName() . '.iml')
					->toString()
				;
				break;
		}

		return $renameFile;
	}

	private function saveFile (SplFileInfo $file, string $renameFile, string $content): void
	{
		self::filesystem()->dumpFile($file->getPathname(), $content);

		if (!empty($renameFile)) {
			self::filesystem()->rename($file->getPathname(), $renameFile, true);
		}
	}
}
