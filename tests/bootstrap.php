<?php declare(strict_types = 1);

use Tester\Environment;
use Tester\Helpers;

if (!@include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer update --dev`';
	exit(1);
}

date_default_timezone_set('Europe/Prague');

// configure environment
Environment::setup();
date_default_timezone_set('Europe/Prague');
// create temporary directory
define('TEMP_DIR', __DIR__ . '/tmp/' . (isset($_SERVER['argv']) ? md5(serialize($_SERVER['argv'])) : getmypid()));
Helpers::purge(TEMP_DIR);
