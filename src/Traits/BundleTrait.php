<?php
/**
 * Copyright $originalComment.match("Copyright (\d+)", 1, "-",$today.year)2026 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 13/10/2026, 19:05
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    BundleTrait.php
 * @date    13/10/2026
 * @time    19:05
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.6.0
 */

namespace Idm\Composer\Plugin\Traits;

use Idm\Composer\Plugin\BundleInfo;

trait BundleTrait
{
	use DefaultBranchTrait;

	protected function askBundleInfo(): BundleInfo
	{
		do {
			$namespace = $this->namespaceBundle();
			$info = new BundleInfo($namespace);
			$repository = $this->repositoryBundle($info->getRepository());
			$branch = $this->defaultBranch();

			$info->setRepository($repository);
			$info->setBranch($branch);

			// Information
			self::io()->title('Information of your Bundle');
			self::io()->text('<fg=blue>Bundle name:</> '.$info->getBundleName());
			self::io()->text('<fg=blue>Namespace:</> '.$info->getNamespace());
			self::io()->text('<fg=blue>Repository name:</> '.$info->getRepository());
			self::io()->text('<fg=blue>Branch:</> '.$info->getBranch());

			$answer = self::io()->confirm('Is this information correct?');
		} while (!$answer);

		return $info;
	}
}
