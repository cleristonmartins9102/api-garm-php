<?php
require_once "env.php";
require_once "src/jwtAuth.php";
require_once "src/function/shared.php";
require_once "vendor/shuchkin/simplexlsx/src/SimpleXLSX.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization');
define('DS', DIRECTORY_SEPARATOR);
define("DIR", dirname(__FILE__));

require 'vendor/autoload.php';
$config = ['settings' => [
    'addContentLengthHeader' => false,
    'displayErrorDetails' => false
]];

$app = new \Slim\App($config);
require_once DIR.DS."Route/Api/Call.php";
require_once DIR.DS."Route/Public/View.php";
$app->run();


