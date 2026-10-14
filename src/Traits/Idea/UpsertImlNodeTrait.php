<?php
/**
 * Copyright $originalComment.match("Copyright (\d+)", 1, "-",$today.year)2026 (C) IDMarinas - All Rights Reserved
 *
 * Last modified by "IDMarinas" on 14/10/2026, 18:57
 *
 * @project IDMarinas Composer Plugin
 * @see     https://github.com/idmarinas/composer-plugin
 *
 * @file    upsertImlNodeTrait.php
 * @date    14/10/2026
 * @time    18:57
 *
 * @author  Iván Diaz Marinas (IDMarinas)
 * @license BSD 3-Clause License
 *
 * @since   1.6.0
 */

namespace Idm\Composer\Plugin\Traits\Idea;

use DOMDocument;
use DOMNode;
use DOMXPath;

trait UpsertImlNodeTrait
{
	private function upsertImlNode(
		DOMDocument $dom,
		DOMXPath    $xpath,
		DOMNode     $parent,
		string      $tag,
		array       $attributes
	): void {
		$nodes = $xpath->query(sprintf('%s[@url="%s"]', $tag, $attributes['url']), $parent);
		$node = $nodes->length > 0 ? $nodes->item(0) : $parent->appendChild($dom->createElement($tag));

		foreach ($attributes as $name => $value) {
			$node->setAttribute($name, $value);
		}
	}
}
