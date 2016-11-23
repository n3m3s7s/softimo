<?php

require_once 'vendor/autoload.php';

use N3m3s7s\Soft\SoftFactory as Softy;

$softy = Softy::create([
    'cacheDir' => 'cache',
    'sourceDir' => 'D:/wamp/www/soft/test',
]);

$watermark = "watermark.png";
$sourceFile = "backgrounds/dark_souls.jpg";
$outputFile = "test/backgrounds/dark_souls_watermarked.jpg";

$softy->file($sourceFile)
    ->modify([
        'w' => 500,
        'mark' => $watermark,
        'markw' => '40',
        'markh' => '40',
        'markpad' => '15',
        'markpos' => 'top-right',
        'markalpha' => 50,
    ])
    ->save($outputFile);