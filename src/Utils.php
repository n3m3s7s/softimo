<?php
namespace N3m3s7s\Soft;

use Exception;

class Utils
{
    const HTTP_STATUS_NOT_FOUND = 404;
    const HTTP_STATUS_FORBIDDEN = 403;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_STATUS_BAD_REQUEST = 500;

    static function loadConfig(){
        return self::merge_config_files(SOFT_DOCROOT . '/config/config.php', SOFT_WORKSPACE . '/soft.config.php');
    }

    static function loadRecipes(){
        return self::merge_config_files(SOFT_DOCROOT . '/config/recipes.php', SOFT_WORKSPACE . '/soft.recipes.php');
    }

    static function merge_config_files($file1, $file2)
    {
        $array2 = $array1 = [];
        if (file_exists($file1)) {
            $array1 = include($file1);
        }
        if (file_exists($file2)) {
            $array2 = include($file2);
        }
        return array_replace_recursive($array1, $array2);
    }

    static function merge_config($array1, $array2)
    {
        return array_replace_recursive($array1, $array2);
    }

    static function serverOS()
    {
        $sys = strtoupper(PHP_OS);

        if (substr($sys, 0, 3) == "WIN") {
            $os = 1;
        } elseif ($sys == "LINUX") {
            $os = 2;
        } else {
            $os = 3;
        }

        return $os;
    }

    static function log($var, $pre = '', $force = false) {
        global $settings;

        if($settings["server"]["log"] == false AND $force == false){
            return;
        }

        if(isset($settings["server"]["logDir"])){
            if(is_null($settings["server"]["logDir"])){
                $logfileDir = $settings["sourceDir"];
            }else{
                $logfileDir = $settings["server"]["logDir"];
            }
        }else{
            $logfileDir = defined('SOFT_WORKSPACE') ? SOFT_WORKSPACE : $settings["sourceDir"];
        }

        $file = $logfileDir . "/soft." . date("Y-m-d") . ".log";

        if (is_array($var) OR is_object($var)) {
            $var = print_r($var, 1);
        }
        $line = ($pre == '') ? $var : "$pre: $var";
        $line = "[" . date("Y-m-d H:i:s") . "] - " . $line . PHP_EOL;
        try {
            file_put_contents($file, $line, FILE_APPEND);
        } catch (Exception $ex) {
            error_log($ex->getMessage());
        }
    }

    static function error($var, $pre = ''){
        self::log($var,$pre,true);
    }

    static function renderStatusCode($code) {
        header("{$_SERVER['SERVER_PROTOCOL']} $code");
    }
}