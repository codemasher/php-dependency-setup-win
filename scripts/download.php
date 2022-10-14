<?php
/**
 * deps.php
 *
 * PHP 7.0 compatible, just in case someone manages to run it with something else
 * than the php shipped with the GH actions runner...
 *
 * (we have cURL and OpenSSL, so all good!)
 * @see https://github.com/actions/runner-images/blob/main/images/win/scripts/Installers/Install-PHP.ps1
 *
 * c:\tools\php\php.exe scripts/deps.php --version 8.2 --vs vs16 --arch x64 --deps "liblzma libzip zlib "
 *
 * @created      11.10.2022
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2022 smiley
 * @license      MIT
 */

require_once __DIR__.'/common.php';

$deps_download = ACTION_DOWNLOADS.'\\deps_download.json';

if(!file_exists($deps_download) || !is_file($deps_download) || !is_readable($deps_download)){
	throw new InvalidArgumentException('cannot read deps_download.json');
}

$download = json_decode(file_get_contents($deps_download));

if(empty($download)){
	throw new InvalidArgumentException('dependency input error/no dependencies given');
}

$downloaded = [];
foreach($download as $dep => $dl){
	// IDGAF
	$data = download_file($dl->url);

	if(empty($data)){
		throw new RuntimeException('download error: '.$dl->url);
	}

	file_put_contents(ACTION_DOWNLOADS.'\\'.$dl->filename, $data);

	echo "downloaded: $dl->url\n";
	$downloaded[$dep] = $dl->filename;
}

$extracted = [];

foreach($downloaded as $file){

	if(!unzip_file(ACTION_DOWNLOADS.'\\'.$file, SDK_BUILD_DEPS)){
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
