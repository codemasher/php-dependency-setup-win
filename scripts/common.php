<?php
/**
 * common.php
 *
 * @created      11.10.2022
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2022 smiley
 * @license      MIT
 */

if(PHP_MAJOR_VERSION < 7){
	throw new RuntimeException('PHP 7+ required!');
}

if(!extension_loaded('curl')){
	throw new RuntimeException('cURL extension not installed!');
}

if(!extension_loaded('openssl')){
	throw new RuntimeException('OpenSSL extension not installed!');
}


define('ACTION_ROOT', $_SERVER['GITHUB_ACTION_PATH'] ?? realpath(__DIR__.'\\..'));
define('WORKSPACE_ROOT', $_SERVER['GITHUB_WORKSPACE'] ?? ACTION_ROOT);
define('ACTION_DOWNLOADS', WORKSPACE_ROOT.'\\.github\\gh-action-downloads');
define('SDK_BUILD_DEPS', realpath(WORKSPACE_ROOT.'\\..').'\\deps');

if(!file_exists(ACTION_DOWNLOADS)){
	mkdir(ACTION_DOWNLOADS);
}

#print_r([ACTION_ROOT, ACTION_DOWNLOADS, SDK_BUILD_DEPS]);

/**
 * @param string $url
 *
 * @return string|null
 */
function download_file($url){
	echo "trying to fetch: $url\n";

	// cURL is nice and all but for some reason on the GitHub runner curl_getinfo() reports weird values
/*
		$fh = fopen($dest, 'wb');
		$ch = curl_init();
		$options = [
			CURLOPT_URL       => $url,
			CURLOPT_USERAGENT => $ua,
			CURLOPT_FILE      => $fh,
			CURLOPT_HEADER    => false,
			CURLOPT_TIMEOUT   => 5,
		];

		curl_setopt_array($ch, $options);
		curl_exec($ch);

		$info = curl_getinfo($ch);
		echo curl_error($ch)."\n";
		curl_close($ch);
		fclose($fh);

		// GitHub what's a HTTP_CODE 0???
		if($info['http_code'] !== 200){
			print_r($info);
		}
*/

	// we need to pass a user agent, otherwise php.net blocks the request
	$ua       = 'chillerlanHttpInterface/5.0 +https://github.com/chillerlan/php-httpinterface';
	$context  = stream_context_create(['http' => ['user_agent' => $ua, 'timeout' => 5]]);
	$response = file_get_contents($url, false, $context);
	$headers  = get_headers($url, true);

	if($response === false || strpos($headers[0], '200 OK') === false){
		echo "failed to download: $url\n";
		return null;
	}

	return $response;
}

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
	$deplist =  download_file($package_txt);

	if(empty($deplist)){
		throw new RuntimeException('invalid package list http response');
	}

	return array_map('trim', explode("\n", trim($deplist)));
}
