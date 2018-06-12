<?php
/**
 * Created by PhpStorm.
 * User: fengwei
 * Date: 2018/5/30
 * Time: 11:10
 */

define('APPLICATION_PATH',str_replace("\\","/",dirname(__DIR__)));

date_default_timezone_set('PRC');

require_once APPLICATION_PATH . '/vendor/autoload.php';
require_once APPLICATION_PATH . '/Pear/Core/Loading.php';

spl_autoload_register("\\Pear\\Core\\Loading::autoload");

define('APP_DEBUG',true);//设置smarty调试模式
$smarty = new \Smarty();

$oApp = new \Pear\Core\Application($smarty);

$smarty->assign('title','标题');
$smarty->display('index.tpl');