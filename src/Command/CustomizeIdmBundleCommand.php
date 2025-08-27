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

use Composer\Json\JsonManipulator;
use Idm\Composer\Plugin\AbstractInfo;
use Idm\Composer\Plugin\BundleInfo;
use Idm\Composer\Plugin\Traits\Command\CustomizeIdmBundle\NamespaceBundleTrait;
use Idm\Composer\Plugin\Traits\DefaultBranchTrait;
use Idm\Composer\Plugin\Traits\SymfonyStyleTrait;
use Idm\Composer\Plugin\Traits\VendorRepositoryTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;
use function Symfony\Component\String\u;

final class CustomizeIdmBundleCommand extends AbstractCommand
{
	use LockableTrait;
	use DefaultBranchTrait;
	use NamespaceBundleTrait;
	use VendorRepositoryTrait;
	use SymfonyStyleTrait;

	protected AbstractInfo|BundleInfo $info;

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
			$this->info = new BundleInfo($namespace);
			$repository = $this->repositoryBundle($this->info->getRepository());
			$branch = $this->defaultBranch();

			$this->info->setRepository($repository);
			$this->info->setBranch($branch);

			// Information
			self::io()->title('Information of your Bundle');
			self::io()->text('<fg=blue>Bundle name:</> ' . $this->info->getBundleName());
			self::io()->text('<fg=blue>Namespace:</> ' . $this->info->getNamespace());
			self::io()->text('<fg=blue>Repository name:</> ' . $this->info->getRepository());
			self::io()->text('<fg=blue>Branch:</> ' . $this->info->getBranch());

			$answer = self::io()->confirm('Is this information correct?');
		} while (!$answer);

		$composer = $this->requireComposer(true, true);

		$finder = self::finder('files')
			->in(dirname($composer->getConfig()->getConfigSource()->getName()))
			->ignoreDotFiles(false)
			->ignoreVCSIgnored(true)
			->ignoreUnreadableDirs()
			->exclude(['bundles', 'docs', '.docker'])
			->notName(['.editorconfig', '.gitkeep',])
			->files()
			->sortByName()
		;

		// Nothing is done if no files have been found.
		if ($finder->count() < 0) {
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
			->replace('IDMarinas Template Bundle', $this->info->getProjectName())
			->replace('Idm\Bundle\Template\IdmTemplateBundle', $this->info->geFullClassName())
			->replace('Idm\Bundle\Template', $this->info->getNamespace())
			->replace('IdmTemplateBundle', $this->info->getBundleName())
			->replaceMatches('#idmarinas/(|idm-)template-bundle#', $this->info->getRepository())
			->replace('name: template_bundle', 'name: ' . $this->info->getDockerName())
			->replace(
				"INSTANCE: 'Writerside/itb'",
				sprintf("INSTANCE: 'Writerside/%s'", $this->info->getProjectNameInitials())
			)
			->replace('SONAR_PROJECT_NAME_CHANGE_ME', u($this->info->getRepository())->replace('/', '_')->toString())
			->replaceMatches(
				'/(sonar.projectName=)(.*)/',
				'${1}' . u($this->info->getProjectName())->after(' ')->toString()
			)
			->replace('[ master ]', "[ {$this->info->getBranch()} ]")
			->toString()
		;

		switch ($file->getFilename()) {
			case 'IdmTemplateBundle.php':
				$renameFile = u($file->getPathname())->replace('IdmTemplateBundle', $this->info->getBundleName())->toString();
				break;
			case 'composer.json':
				$content = parent::processFile($file, $content);

				$manipulator = new JsonManipulator($content);
				$manipulator->addMainKey('type', 'symfony-bundle');
				$manipulator->addMainKey('keywords', ['symfony-bundle']);
				$manipulator->addSubNode('autoload', 'psr-4', [$this->info->getAutoload() => 'src/']);
				$manipulator->addSubNode('autoload-dev', 'psr-4', [
					'App\\'                       => 'app/src/',
					$this->info->getAutoloadDev() => 'tests/',
					'DataFixtures\\'              => 'fixtures/',
					'Factory\\'                   => 'factories/',
				]);

				$content = $manipulator->getContents();
				break;
			case 'README.md':
				$finder = self::finder('readme')
					->in(dirname(__DIR__, 2) . '/templates')
					->files()
					->name('readme.tpl.md')
				;

				if ($finder->hasResults()) {
					parent::processFile($file, $content);

					$file = $finder->getIterator();
					$file->rewind();
					$file = $file->current()->getContents();
					$file = u($file)
						->replace('<package-name>', $this->info->getRepository())
						->replace('<vendor>\<bundle-name>\<bundle-long-name>', $this->info->geFullClassName())
						->toString()
					;
					$content = u($content)
						->replaceMatches('/<!-- readme-template -->(?s:.)+<!-- readme-template -->/', $file)
						->toString()
					;
				}
				break;
			case 'itb.tree':
				$renameFile = u($file->getPathname())
					->replace('itb.tree', $this->info->getProjectNameInitials() . '.tree')
					->toString()
				;
				$content = u($content)
					->replace('id="itb"', sprintf('id="%s"', $this->info->getProjectNameInitials()))
					->toString()
				;
				break;
			case 'writerside.cfg':
				$content = u($content)
					->replace('src="itb.tree"', sprintf('src="%s.tree"', $this->info->getProjectNameInitials()))
					->toString()
				;
				break;
			case 'v.list':
				$content = u($content)
					->replace('name="branch" value="1.x"', sprintf('name="branch" value="%s"', $this->info->getBranch()))
					->toString()
				;
				break;
			default:
				return parent::processFile($file, $content);
		}

		return $renameFile;
	}
}
