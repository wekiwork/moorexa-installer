<?php

// check system requirements
$requirements = [

	'PHP' => '7.2',
	'functions' => [
		'curl_init',
		'openssl_encrypt'
	]
];


// message to screen
function screen_display($message, string $type = '')
{
	// reset color
	$reset = "\033[0m";

	// get type
	$color = $type == 'error' ? "\033[31m" : ($type == 'success' ? "\033[32m" : '');

	// print message
	fwrite(STDOUT,  $color . $message . $reset . "\n");
}

// convert to readable size
function convertToReadableSize($size, &$sbase=null)
{
    $base = log($size) / log(1024);
    $suffix = array("Byte", "KB", "MB", "GB", "TB");
    $f_base = floor($base);
    $convert = round(pow(1024, $base - floor($base)), 1) . $suffix[$f_base];

    $sbase = strtolower($suffix[$f_base]);

    if ($convert > 0) return $convert;

    return 0 . 'KB';
}

// read line
function readInput()
{
    if (PHP_OS == "WINNT") return trim(stream_get_line(STDIN, 1024));
    
    // not windows
    return trim(readline());
}


// do check now
foreach ($requirements as $target => $val) :

	// check for PHP
	switch ($target):

		// check php version
		case 'PHP':
			// check current php version
			if (phpversion() < floatval($val)) return screen_display('Your PHP Version is less than the required "'.$val.'" version', 'error');
		break;

		// check for functions
		case 'functions':
			// check the functions
			foreach ($val as $func) :
				// does function exists
				if (!function_exists($func)) return screen_display('Missing Required function ' . $func . ', installation could not be complete.', 'error');
			endforeach;
		break;

	endswitch;

endforeach;

// get version from the user
$version = 'master';

// ask user 
fwrite(STDOUT, PHP_EOL . 'What version of moorexa should we install? (Hit Enter to install the latest) : ');

// read input
$input = readInput();

// assign version
$version = $input != '' ? $input : $version;

// get working directory
$workingDirectory = $_SERVER['PWD'];

// get the home directory
$homeDirectory = $_SERVER['HOME'];

// create directory
if (!is_dir($homeDirectory . '/moorexa')) mkdir($homeDirectory . '/moorexa');

// create a new file here
$moorexaFile = $homeDirectory . '/moorexa/moorexa';

// put content inside a new file
file_put_contents($moorexaFile, file_get_contents('https://raw.githubusercontent.com/wekiwork/moorexa-installer/'.$version.'/moorexa'));

// create path
screen_display('Checking if PATH has been registered.', 'success');

// get the os
$os = preg_replace('/[s]+/', '', php_uname('s'));

// path file
$pathFile = $homeDirectory . '/moorexa/'.$os.'_path.d';

// path format for different os
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') :

	// window profile
	$profile = 'pathman /au c:\\' . $homeDirectory . '\\moorexa';

else:

	// profile name
	$profileName = '.bashrc';

	// check if this file exists
	if (file_exists($homeDirectory . '/.bash_profile')) $profileName = '.bash_profile';

	// get the profile name
	$profile = 'sudo echo "alias moorexa=\"php '.$homeDirectory.'/moorexa/moorexa"\" >> ~/' . $profileName . ';source ~/' . $profileName;

endif;

// check if path has been created
if (file_exists($pathFile)) return screen_display('PATH Added previously. Installation would not continue.', 'error');

// download from github function
function download_from_github(string $link, string $fileName, string $version = 'master')
{
	// @var bool $installed
	$installed = false;

	// @var int $rand
	$rand = mt_rand(1, 100);

	// get the home directory
	$homeDirectory = $GLOBALS['homeDirectory'];

	// get a fake user agent
    $agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:'.$rand.'.0) Gecko/20100101 Firefox/'.$rand.'.0';

	// get the master branch
	if ($version === 'master') :

		$endpoint = 'https://github.com/'.$link.'/archive/master.zip';

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);

        $content = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        $getError = trim($content);

        if (strtolower($getError) == 'not found') :
        
            screen_display(strtolower($getError) . ' error returned from github server. Please check repo name or tag name used.', 'error');
        
        else:
        
            screen_display(convertToReadableSize(strlen($content)). ' downloaded from @https://github.com/'.$link);

            // sleep
            sleep(1);


            // caching begins
            screen_display('Caching master branch, please wait for the next process..', 'success');
        
            $destination = $homeDirectory . '/moorexa/storage/'.$fileName.'.zip';
            $fh = fopen($destination, 'wb');
            fwrite($fh, $content);
            fclose($fh);

            sleep(1);

            // good
            $installed = true;

        endif;

	else:

		// search for version
		// get version
        $endpoint = 'https://api.github.com/repos/'.$link.'/releases';

        // send text to screen
        screen_display('Connecting to our official github repo. Will attempt to download release for ' . $version);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        $content = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        screen_display('Getting response from repo @'.$link);

        sleep(1);

        if ($err) :
        
            screen_display('error: '. $err, 'error');

        else:

        	$json = json_decode($content);

        	// get the message
        	$canContinue = (is_object($json) && isset($json->message)) ? (strtolower($json->message) == 'not found' ? false : true) : true;

        	if ($canContinue) :

	            foreach ($json as $release) :
	                
	                if ($release->tag_name == $version) break;

	                $tag = doubleval($release->tag_name);
	                $equal = strpos($version, '=');

	                // remove ^
	                $version = preg_replace('/[^0-9.]/', '', $version);
	                $version = doubleval($version);

	                // check if $tag is greater than $version
	                if ($tag > $version) :
	                
	                    $version = $release->tag_name;
	                    break;

	                elseif ($equal !== false) :
	                
	                    if ($tag >= $version) :
	                    
	                        $version = $release->tag_name;
	                        break;
	                 	endif;

	                else:
	                
	                    $version = null;
	                endif;
	            
	           	endforeach;

	            $message = (is_object($json) && isset($json->message)) ? strtolower($json->message) : '';
	            $error = true;

	            if ($version !== null) :
	            
	                if ($message == '') :
	                
	                    // success
	                    $error = false;
	                    $endpoint = 'https://github.com/'.$link.'/archive/'.$version.'.zip';
	                    screen_display('trying to fetch archive with @'.$endpoint, 'success');
	                    sleep(1);

	                    $ch = curl_init($endpoint);
	                    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
	                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	                    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
	                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
	                        'If-Modified-Since: Thu, 05 Jul 2012 15:31:30 GMT'
	                    ]);

	                    $content = curl_exec($ch);
	                    $err = curl_error($ch);
	                    curl_close($ch);

	                    screen_display(convertToReadableSize(strlen($content)). ' downloaded from @https://github.com/'.$link);

	                    if ($err) :
	                    
	                        screen_display('error: '. $err, 'error');
	                    
	                    else:
	                    
	                        // caching begins
				            screen_display('Caching version '.$version.', please wait for the next process..', 'success');
	        
	                        $destination = $homeDirectory . '/moorexa/storage/'.$fileName.'-'.$version.'.zip';
	                        $fh = fopen($destination, 'wb');
	                        fwrite($fh, $content);
	                        fclose($fh);

	                        sleep(1);

	                        // installed 
	                        $installed = true;

	                    endif;

	                endif;

	            endif;

	            // error 
	            if ($error) : screen_display(strtolower($message) . ' error returned from github server. Please check repo name or tag name used.', 'error'); endif;

	        else:

	        	screen_display($json->message .' error was returned from github server. Please check repo name or tag name used.', 'error');

	        endif;

        endif;

	endif;

	// return bool
	return $installed;
}

sleep(1);

// create storage folder
if (!is_dir($homeDirectory . '/moorexa/storage/')) mkdir($homeDirectory . '/moorexa/storage/');

// repo to download
$repos = [
	'wekiwork/moorexa-core' => 'moorexaCore',
	'wekiwork/moorexa-micro-service' => 'moorexaMicroService',
	'wekiwork/moorexa-frontend' => 'moorexaFrontEnd',
	'wekiwork/moorexa-source' => 'moorexaSource',
	'wekiwork/moorexa-packagers' => 'moorexaPackager'
];

// completed
$completed = 0;

// download repos
foreach ($repos as $link => $fileName) :

	if (download_from_github($link, $fileName, $version)) :

		// all good
		screen_display('Package ' . $link . '['.$version.'] downloaded successfully', 'success');

		// sleep
		sleep(1);

		// increment
		$completed++;

	endif;

endforeach;

// are we good
if ($completed == count($repos)) :

	// adding to system paths
	screen_display('Adding moorexa to your system paths', 'success');

	// add profile
	pclose(popen($profile, "w"));

	// add path file
	file_put_contents($pathFile, $profile);

	// delete this installer file
	unlink(__DIR__ . '/installer.php');

	// all done!
	screen_display('All done. You can enter "moorexa" on your terminal or cmd to see a list of options avaliable to you. Thank you for installing moorexa.

You may have to restart your terminal or try any of this commands to update your system paths.' . "\n" .
"
[Mac] > source ~/.bash_profile
[Ubuntu] > source ~/.bashrc

Or just go on with closing and reopening your terminal before trying \"moorexa\" command.\n\n");

	// send a signal. Download was successfull
	$ch = curl_init('http://installer.wekiwork.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Moorexa Installer#successfull');
    curl_exec($ch);
    curl_close($ch);

else:
	// you may have to run this installation again
	screen_display('Oops! We could not download all the packages to your local machine. You may have to run this installation again');
endif; 

