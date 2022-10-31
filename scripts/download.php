<?php
/**
 * deps.php
 *
 * @created      11.10.2022
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2022 smiley
 * @license      MIT
 */

#require_once '../vendor/codemasher/php-github-actions-toolkit/src/vendor/autoload.php'; // local
require_once $_SERVER['GITHUB_WORKSPACE'].'/.github/github_actions_toolkit.php';

$toolkit = new \GitHubActionsToolkit;

define('ACTION_TOOLKIT_TMP', $toolkit->getActionTmp());

$deps_download = ACTION_TOOLKIT_TMP.DIRECTORY_SEPARATOR.'deps_download.json';

if(!file_exists($deps_download) || !is_file($deps_download) || !is_readable($deps_download)){
	throw new InvalidArgumentException('cannot read deps_download.json');
}

$download = json_decode(file_get_contents($deps_download));

if(empty($download)){
	throw new InvalidArgumentException('dependency input error/no dependencies given');
}

$downloaded = [];
foreach($download as $dep => $dl){

	if(!$toolkit->downloadFile($dl->url, ACTION_TOOLKIT_TMP)){
		throw new RuntimeException('download error: '.$dl->url);
	}

	echo "downloaded: $dl->url\n";
	$downloaded[$dep] = $dl->filename;
}

$deps      = realpath($toolkit->getWorkspaceRoot().DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR.'deps';
$extracted = [];

foreach($downloaded as $file){

	if(!$toolkit->unzip(ACTION_TOOLKIT_TMP.DIRECTORY_SEPARATOR.$file, $deps)){
		continue;
	}

	$extracted[] = $file;
}

$diff = array_diff($downloaded, $extracted);

if(!empty($diff)){
	throw new RuntimeException('could not extract the following libraries: '.implode(', ', $diff));
}

// we made it!
exit(0);
