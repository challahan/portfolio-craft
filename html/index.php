<?php

// Path to your craft/ folder
$craftPath = '../craft';

// Do not edit below this line
defined('CRAFT_BASE_PATH') || define('CRAFT_BASE_PATH', realpath($craftPath));

if (!is_dir(CRAFT_BASE_PATH.'/vendor')) {
  exit('Could not find your vendor/ folder. Please ensure that <strong><code>$craftPath</code></strong> is set correctly in '.__FILE__);
}

require_once CRAFT_BASE_PATH.'/vendor/autoload.php';
$app = require CRAFT_BASE_PATH.'/vendor/craftcms/cms/bootstrap/web.php';
$app->run();
