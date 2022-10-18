<?php
/**
 * common.php
 *
 * @created      11.10.2022
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2022 smiley
 * @license      MIT
 */

require_once $_SERVER['GITHUB_WORKSPACE'].'/.github/github_actions_toolkit.php';

$toolkit = new \GitHubActionsToolkit;

define('SDK_BUILD_DEPS', realpath($toolkit->getWorkspaceRoot().'\\..').'\\deps');
define('ACTION_TOOLKIT_TMP', $toolkit->getActionTmp());

/**
 * fetch one of the dependency lists from php.net
 *
 * @see https://windows.php.net/downloads/php-sdk/deps/series/
 * @see https://windows.php.net/downloads/pecl/deps/packages.txt
 */
function fetch_deplist(string $package_txt):array{
	global $toolkit;

	$deplist =  $toolkit->fetchFromURL($package_txt);

	if(empty($deplist)){
		throw new RuntimeException('invalid package list http response');
	}

	return array_map('trim', explode("\n", trim($deplist)));
}
