<?php
/**
 * Created by PhpStorm.
 * User: fengwei
 * Date: 2018/6/1
 * Time: 10:00
 */

define("APPLICATION_PATH", dirname(dirname(__FILE__)));
header('Content-Type: text/html; charset=utf-8');

date_default_timezone_set('PRC');

require_once APPLICATION_PATH . '/vendor/autoload.php';
require_once APPLICATION_PATH . '/Pear/Core/Loading.php';
require_once APPLICATION_PATH . '/Pear/Util/func.php';

spl_autoload_register("\\Pear\\Core\\Loading::autoload");