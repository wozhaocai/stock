<?php
/**
 * Created by PhpStorm.
 * User: fengwei
 * Date: 2018/5/21
 * Time: 19:15
 */
namespace Pear\Core;

class Loading
{
    public static function autoload($className){
        $fileName = str_replace('\\', DIRECTORY_SEPARATOR, APPLICATION_PATH . '\\'. $className) . '.php';
        if (is_file($fileName)) {
            require $fileName;
        } else {
            echo $fileName . ' is not exist'; die;
        }
    }
}