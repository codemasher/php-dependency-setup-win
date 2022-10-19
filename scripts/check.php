<?php
/**
 * check.php
 *
 * @created      14.10.2022
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2022 smiley
 * @license      MIT
 */

require_once $_SERVER['GITHUB_WORKSPACE'].'/.github/github_actions_toolkit.php';

$toolkit = new \GitHubActionsToolkit;

define('ACTION_TOOLKIT_TMP', $toolkit->getActionTmp());

$args = getopt('', ['version:', 'vs:', 'arch:', 'ignore_vs:']);

if(empty($args)){
	throw new InvalidArgumentException('invalid arguments');
}

// brrr
foreach($args as $k => $v){
	${strtolower($k)} = trim($v);
}

$deps_json = ACTION_TOOLKIT_TMP.'\\deps.json';

if(!file_exists($deps_json) || !is_file($deps_json) || !is_readable($deps_json)){
	throw new InvalidArgumentException('cannot read deps.json');
}

$deps = json_decode(file_get_contents($deps_json));

if(empty($deps)){
	throw new InvalidArgumentException('dependency input error/no dependencies given');
}

$ignore_vs = $ignore_vs !== 'false';


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

// check core dependencies first
$baseurl  = 'https://windows.php.net/downloads/php-sdk/deps';
$deplist  = fetch_deplist("$baseurl/series/packages-$version-$vs-$arch-staging.txt");
$download = [];

foreach($deps as $dep){
	foreach($deplist as $dep_available){
		if(strpos($dep_available, $dep) === 0){
			$download[$dep] = ['url' => "$baseurl/$vs/$arch/$dep_available", 'filename' => $dep_available];
		}
	}
}

$diff = array_diff($deps, array_keys($download));

// didn't catch all? try PECL
if(!empty($diff)){
	$baseurl = 'https://windows.php.net/downloads/pecl/deps';
	$deplist = fetch_deplist($baseurl.'/packages.txt');

	foreach($diff as $dep){
		$dep_versions = [];

		foreach($deplist as $dep_available){

			if(strpos(strtolower($dep_available), $dep) === 0 && strpos($dep_available, '-'.$arch.'.zip') > 0){

				if(!$ignore_vs && strpos($dep_available, '-'.$vs) === false){
					continue;
				}

				$dep_versions[] = $dep_available;
			}
		}

		// hoping for the best tbh
		sort($dep_versions, SORT_NATURAL);
		$count = count($dep_versions);

		if($count > 0){
			$file           = $dep_versions[$count - 1];
			$download[$dep] = ['url' => $baseurl.'/'.$file, 'filename' => $file];
		}
	}
}

$diff = array_diff($deps, array_keys($download));

/*
// still not complete? try winlibs? https://github.com/winlibs
if(count($diff) > 0){
	// @todo ...
}

$diff = array_diff($deps, array_keys($download));
*/

// oop!
if(!empty($diff)){
	throw new RuntimeException('could not fetch the following libraries: '.implode(', ', $diff));
}

$deps_download = ACTION_TOOLKIT_TMP.'\\deps_download.json';
file_put_contents($deps_download, json_encode($download));

$out_vars = [
	'cachekey'      => sha1(implode(' ', array_column($download, 'filename'))),
	'deps_download' => $deps_download,
	'deps'          => realpath($toolkit->getWorkspaceRoot().'\\..').'\\deps',
];

$toolkit->outputVars($out_vars);

exit(0);
