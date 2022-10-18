<?php
/**
 * common.php
 *
 * @created      11.10.2022
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2022 smiley
 * @license      MIT
 */

require_once $_SERVER['GITHUB_WORKSPACE'].'/github_actions_toolkit.php';

$toolkit = new \GitHubActionsToolkit;

define('SDK_BUILD_DEPS', realpath(GITHUB_WORKSPACE_ROOT.'\\..').'\\deps');

/**
 * @param string $zipfile
 * @param string $dest
 *
 * @return bool
 */
function unzip_file($zipfile, $dest){
	echo "extracting: $zipfile to $dest\n";

	if(!is_readable($zipfile)){
		echo "zip file not readable: $zipfile\n";
		return false;
	}

	$zip = new ZipArchive;

	if($zip->open($zipfile) === false){
		echo "failed to open zip file: $zipfile\n";
		return false;
	}

	if($zip->extractTo($dest) === false){
		echo "failed to extract zip file: $zipfile to $dest\n";
		return false;
	}

	$zip->close();

	return true;
}

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
