<?php
/**
 * Created by PhpStorm.
 * User: edz
 * Date: 2018/5/30
 * Time: 11:48
 */

namespace Pear\Core;


class Application
{
    private $_oSmarty = null;
    public function __construct(&$smarty)
    {
        $this->_oSmarty = $smarty;
        $this->initSmarty();
    }

    public function initSmarty(){
        $this->_oSmarty->left_delimiter = "<{";
        $this->_oSmarty->right_delimiter = "}>";
        $this->_oSmarty->setTemplateDir(APPLICATION_PATH."/templates");
        $this->_oSmarty->setCompileDir(APPLICATION_PATH.'/data/cache/templates_c');
        $this->_oSmarty->setConfigDir(APPLICATION_PATH.'/templates/smarty_configs');
        $this->_oSmarty->setCacheDir(APPLICATION_PATH.'/data/cache/smarty_cache');
        if(APP_DEBUG){
            $this->_oSmarty->debugging = true;
            $this->_oSmarty->caching = false;
            $this->_oSmarty->cache_lifetime = 0;
        }else{
            $this->_oSmarty->debugging = false;
            $this->_oSmarty->caching = true;
            $this->_oSmarty->cache_lifetime = 120;
        }
    }
}