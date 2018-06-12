<?php
/**
 * Created by PhpStorm.
 * User: fengwei
 * Date: 2018/5/30
 * Time: 11:04
 */
namespace Pear\Util;

class MysqlPDO extends PDO {
    private static $_db;
    private static $_db_type = "mysql"; //数据库类型
    private static $_db_bind = array(); //绑定字段
    private static $_remote = false; //验证ip
    public $debug = false;
    /**
     * @var
     */
    private $_dbhost;
    /**
     * @var
     */
    private $_dbport;
    /**
     * @var
     */
    private $_databasename;

    /**
     * @var
     */
    private $_dbuser;

    /**
     * @var
     */
    private $_dbpass;

    /**
     * MysqlPdo constructor.
     * @param string $dbhost
     * @param string $dbport
     * @param string $databasename
     * @param string $dbuser
     * @param string $dbpass
     */
    public function __construct($dbhost = '', $dbport = '', $databasename = '', $dbuser = '', $dbpass = '') {
        $this->_dbhost = $dbhost;
        $this->_dbport = $dbport;
        $this->_databasename = $databasename;
        $this->_dbuser = $dbuser;
        $this->_dbpass = $dbpass;
        try {
            if ($this->_dbhost && $this->_dbport && $this->_databasename && $this->_dbuser && $this->_dbpass) {
                $dsn = "mysql:host={$this->_dbhost};port={$this->_dbport};dbname={$this->_databasename}";
                self::$_db = @new PDO($dsn, $this->_dbuser, $this->_dbpass, array(PDO::ATTR_PERSISTENT => true,PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8';", PDO::ATTR_TIMEOUT => 5));
            } else {
                if (!defined('DB_HOST') or !defined('DB_NAME') or !defined('DB_USER') or !defined('DB_PASS') or !defined('DB_PORT')) {
                    echo "数据库配置信息不全";
                    exit(0);
                }
                $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
                self::$_db = @new PDO($dsn, DB_USER, DB_PASS, array(PDO::ATTR_PERSISTENT => true,PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8';", PDO::ATTR_TIMEOUT => 5));
            }
        } catch (PDOException $e) {
            echo 'Connection failed:' . $e->getMessage();
            self::$_db = null;
            exit;
        }
        self::$_db->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        return self::$_db;
    }

    function setDBType($str) {
        self::$_db_type = $str;
    }
    function getDBType() {
        return self::$_db_type;
    }
    function checkSql($sql) {
        if (self::$_remote == false) {
            return true;
        }
        if (stristr($sql, "insert") or stristr($sql, "update") or stristr($sql, "delete")) {
            if (defined(DEV) and DEV) {
                return false;
            } else {
                return true;
            }
        }
    }
    //查询sql语句，获取所有数据
    function querySql($sql, $aParam = array()) {
        $time_start = microtime(true);
        $showsql = $this->getShowSql($sql, $aParam);
        //debugVar($showsql);
        $rs = self::$_db->prepare($sql);
        $this->setBind($rs,$aParam);
        if (!$rs) {
            return false;
        }
        if (count($aParam) > 0) {
            $rs->execute($aParam);
        } else {
            $rs->execute();
        }
        if (!$rs) {
            return false;
        }
        if (strstr(strtolower($sql), "insert")) {
            $result_arr['lastid'] = self::$_db->lastinsertid();
        } else {
            //$rs->setFetchMode(PDO::FETCH_ASSOC);
            $rs->setFetchMode(PDO::FETCH_OBJ);
            $result_arr = $rs->fetchAll();
        }
        $time_end = microtime(true);
        self::logSql($time_end, $time_start, $showsql);
        return $result_arr;
    }
    //查询sql语句，获取查询的列
    function query_cols($sql) {
        $sql = self::translateTag($sql);
        if (self::checkSql($sql) == false) {
            return false;
        }
        $time_start = microtime(true);
        $rs = self::$_db->prepare($sql);
        if (!$rs) {
            return false;
        }
        try {
            $rs->execute();
        } catch (PDOException $e) {
            $sql .= " error-msg:" . $e->getMessage();
            $time_end = microtime(true);
            self::logSql($time_end, $time_start, $sql);
            return false;
        }
        if (!$rs) {
            return false;
        }
        $time_end = microtime(true);
        $rs->setFetchMode(PDO::FETCH_ASSOC);
        $result_arr = $rs->fetchColumn();

        self::logSql($time_end, $time_start, $sql);
        return $result_arr;
    }
    function insert($sTable, $aData, $update = false, $transaction = false) {
        $sql = "insert into {$sTable}(" . implode(",", array_keys($aData)) . ") values";
        $aUpdate = array();
        $aParam = array();
        foreach ($aData as $sKey => $sVal) {
            $aParam[":{$sKey}"] = $sVal;
            if ($update) {
                $aUpdate[] = "{$sKey} = :{$sKey}";
            }
        }
        $sql .= "(:" . implode(",:", array_keys($aData)) . ")";
        if ($update) {
            $sql .= " ON DUPLICATE KEY UPDATE " . implode(",", $aUpdate) . ")";
        }
        if ($transaction) {
            $this->execTransaction($sql);
        }
        return $this->query($sql . ";", $aParam);
    }
    function existTable($tableName) {
        $sql = "select count(*) from {$tableName} limit 1";
        $field = self::query_cols($sql);
        if ($field) {
            return true;
        } else {
            return false;
        }
    }
    function setBind(&$rs,$aParam) {
        if(!empty($aParam)){
            foreach ($aParam as $sKey => $sVal) {
                $rs->bindValue($sKey, $sVal, PDO::PARAM_STR);
            }
        }
        return true;
    }
    function replaceBind(&$rs, $sql) {
        foreach (self::$_db_bind as $key => $aBind) {
            $aBind["value"] = str_replace("||http_t||", "http://", $aBind["value"]);
            $aBind["value"] = str_replace("||http_d||", ".", $aBind["value"]);
            $aBind["value"] = str_replace("||http_e||", "/", $aBind["value"]);
            //$aBind["value"] = mysql_real_escape_string(htmlspecialchars_decode(urldecode($aBind["value"])));
            if (self::$_db_type == "oracle") {
                $aBind["value"] = iconv(self::$_web_encoding, self::$_db_encoding, $aBind["value"]);
            }
            if ($aBind["type"] == "PDO::PARAM_STR") {
                $sql = str_replace($key, "'{$aBind["value"]}'", $sql);
            } else if ($aBind["type"] == "PDO::PARAM_INT") {
                $sql = str_replace($key, $aBind["value"], $sql);
            } else {
                return false;
            }
        }
        self::debugVar("prepare:" . $sql);
        $rs = self::$_db->prepare($sql);
        return true;
    }
    function update($strSql, $aParam = array()) {
        //编码要先转换为数据库的编码，编码才不会乱掉
        $bRet = false;
        $iCnt = 0;
        try {
            $time_start = microtime(true);
            self::$_db->beginTransaction();
            $strSql .= ";";
            $showsql = $this->getShowSql($strSql, $aParam);
            //debugVar($showsql);
            $stmt = self::$_db->prepare($strSql);
            if (!$stmt) {
                return false;
            }
            $btmp = $stmt->execute($aParam);
            $iCnt = $stmt->rowCount();
            if ($btmp) {
                $bRet = true;
            }
            self::$_db->commit();
            $time_end = microtime(true);
            self::logSql($time_end, $time_start, $showsql);
        } catch (PDOException $e) {
            self::$_db->rollBack();
            $bRet = false;
            $strSql .= " error-msg:" . $e->getMessage();
            //	self::debugVar(self::writeSql($strSql));
        }
        if ($iCnt == 0) {
            return false;
        } else {
            return $bRet;
        }
    }
    function getShowSql($sql, $param) {
        if (count($param) > 0) {
            foreach ($param as $key => $val) {
                $sql = str_replace($key.',', "'$val',", $sql);
                $sql = str_replace($key.' ', "'$val' ", $sql);
                $sql = str_replace($key, "'$val'", $sql);
            }
            if ($this->debug) {
                self::debugVar($sql);
            }
        }
        return $sql;
    }
    function translateTag($sql) {
        $aBind = array();
        if (!empty(self::$_db_bind)) {
            foreach (self::$_db_bind as $key => $value) {
                if (strstr($sql, $key)) {
                    $aBind[$key] = $value;
                }
            }
        }
        self::$_db_bind = $aBind;
        return $sql;
    }
    function TransEncoding(&$array, $charset_in, $charset_out) {
        if ($charset_in == $charset_out) {
            return $array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_string($value)) {
                    $array[$key] = iconv($charset_in, $charset_out, self::TransValue($value));
                } else if (is_array($value)) {
                    self::TransEncoding($array[$key], $charset_in, $charset_out);
                }
            }
        } else if (is_string($array)) {
            $array = iconv($charset_in, $charset_out, self::TransValue($array));
        }
        return $array;
    }
    function TransValue($var) {
        if (substr($var, 0, 1) == ".") {
            return $var = "0" . $var;
        } else {
            return $var;
        }
    }
    function writeSql($sql) {
        foreach (self::$_db_bind as $key => $aBind) {
            $aBind["value"] = str_replace("||http_t||", "http://", $aBind["value"]);
            $aBind["value"] = str_replace("||http_d||", ".", $aBind["value"]);
            $aBind["value"] = str_replace("||http_e||", "/", $aBind["value"]);
            if ($aBind["type"] == "PDO::PARAM_STR") {
                $sql = str_replace($key, "'{$aBind["value"]}'", $sql);
            } else if ($aBind["type"] == "PDO::PARAM_INT") {
                $sql = str_replace($key, $aBind["value"], $sql);
            } else {
                return false;
            }
        }
        return $sql;
    }
    function logSql($time_end, $time_start, $sql) {
    }
    function writeLog($filename, $line) {
    }
    function debugVar($var) {
    }
}