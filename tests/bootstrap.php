<?php
define('BASEPATH', getcwd().'/system/');
define('APPPATH', 'application/');
define('VIEWPATH', 'application/views/');
define('ENVIRONMENT', 'development');
require_once BASEPATH.'core/CodeIgniter.php';
require __DIR__ . '/../vendor/autoload.php';
