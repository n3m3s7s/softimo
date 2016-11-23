<?php

require_once 'vendor/autoload.php';

use N3m3s7s\Soft\SoftImage as Img;

$pathToImage = "test/kayaks.jpg";
$outputImage = "test/kayaks_modified.jpg";


// apply modifications to a source image and then saves the output as a new file
// this process do not cache any resource
Img::create($pathToImage)
    ->modify(['w'=> 50, 'filt'=>'greyscale'])
    ->save($outputImage);