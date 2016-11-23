<?php
/**
 * Created by PhpStorm.
 * User: Fabio
 * Date: 21/11/2016
 * Time: 18:03
 */

namespace N3m3s7s\Soft;


class SoftFactory extends Soft
{
    public static function create($config)
    {
        return (new static())->setConfig($config);
    }

    public function purge($file){
        $this->setSourceFile($file);
        $glideServer = $this->createServer();
        return $glideServer->deleteCache($this->sourceFile);
    }

    public function save($file){
        $tempFile = $this->process();
        rename($tempFile,$file);
    }
}