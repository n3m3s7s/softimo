<?php

require_once 'vendor/autoload.php';

use N3m3s7s\Soft\SoftFactory as Softy;

$softy = Softy::create([
    'cacheDir' => 'cache',
    'sourceDir' => 'D:/wamp/www/soft/test',
]);

$sourceFile = "backgrounds/dark_souls.jpg";
$outputFile = "test/backgrounds/dark_souls_modified.jpg";

$softy->file($sourceFile)
    ->modify([
        'w'=> 50,
        'filt'=>'greyscale'
    ])
    ->save($outputFile);