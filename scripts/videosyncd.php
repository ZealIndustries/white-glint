#!/usr/bin/php
<?php 
/****** ULTRA LAZY CODE GOOOOOOO ******/


// Allowed arguments & their defaults
$runmode = array(
    'no-daemon' => false,
    'help' => false,
    'write-initd' => false,
);
 
// Scan command line attributes for allowed arguments
foreach($argv as $k=>$arg) {
    if (substr($arg, 0, 2) == '--' && isset($runmode[substr($arg, 2)])) {
        $runmode[substr($arg, 2)] = true;
    }
}
 
// Help mode. Shows allowed argumentents and quit directly
if($runmode['help'] == true) {
    echo 'Usage: '.$argv[0].' [runmode]' . "\n";
    echo 'Available runmodes:' . "\n";
    foreach ($runmode as $runmod=>$val) {
        echo ' --'.$runmod . "\n";
    }
    die();
}
 
// Include Class
error_reporting(E_STRICT);
require_once 'System/Daemon.php';
 
// Setup
$options = array(
    'appName' => 'videosyncd',
    'appDir' => dirname(__FILE__),
    'appDescription' => 'Communicates with VideoSyncPlugin to keep the stream in sync. Kind of a hack. :/',
    'authorName' => 'RedEnchilada',
    'authorEmail' => 'red@lyrawearspants.com',
    'sysMaxExecutionTime' => '0',
    'sysMaxInputTime' => '0',
    'sysMemoryLimit' => '512M',
    'appRunAsGID' => 1001,
    'appRunAsUID' => 1001,
);
 
System_Daemon::setOptions($options);
 
// This program can also be run in the forground with runmode --no-daemon
if(!$runmode['no-daemon']) {
    System_Daemon::start();
} else {
	System_Daemon::info('running in no-daemon mode');
}
 
// With the runmode --write-initd, this program can automatically write a
// system startup file called: 'init.d'
// This will make sure your daemon will be started on reboot
if(!$runmode['write-initd']) {
    System_Daemon::info('not writing an init.d script this time');
} else {
    if(($initd_location = System_Daemon::writeAutoRun()) === false) {
        System_Daemon::notice('unable to write init.d script');
    } else {
        System_Daemon::info('sucessfully written startup script: %s',
			$initd_location);
    }
}

$url = 'http://beta.rainbowdash.net/main/updatestream';
$interval = 5;

date_default_timezone_set('UTC');

while(!System_Daemon::isDying()) {
	$curl = curl_init();
	
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
	
	$data = curl_exec($curl);
	curl_close($curl);
     
    System_Daemon::iterate($interval);
}

System_Daemon::info('stopping');
System_Daemon::stop();

// I'm cry over how bad of a hack this is
?>