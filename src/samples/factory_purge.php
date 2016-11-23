<?php

require_once 'vendor/autoload.php';

use N3m3s7s\Soft\SoftFactory as Softy;

$softy = Softy::create([
    'cacheDir' => 'cache',
    'sourceDir' => 'D:/wamp/www/soft/test',
]);

$pathToImage = "backgrounds/dark_souls.jpg";

$softy->purge($pathToImage);