<?php
return [
    'driver' => 'gd', //The driver used for Image manipulation. Acceptable values are 'gd' and 'imagick'
    'cache' => true, //Enable/disable the cache engine
    'cacheDir' => 'cache/soft', //The directory used for caching; can be an absolute or relative path
    'sourceDir' => null, //The directory used as the source for all images; if null is relative to the 'soft.php' script
    'watermarkDir' => null, //The directory used as the source for all watermarks; if null is equal to 'sourceDir' parameter
    'image' => [
        'quality' => 80, //The default quality value (mainly for Jpg)
        'upscaling' => false, //Enable/disable the upscaling during an image resizing process
        'fix_webp_bytecode' => false, //Enable/disable auto-fixing 0 bytecode for WEBp raw response
    ],
    'placeholder' => [
        'enabled' => true, //Enable/disable the placeholder usage; if true the placeholder image will be used it the source image does not exist
        'file' => 'not-found.jpg', //relative to the "sourceDir" directory
    ],
    'server' => array(
        'log' => false, //Enable/disable the server log; this should be false in production mode
        'logDir' => null, //The directory used for logs storage; if null the sourceDir will be used
        'timezone' => 'Europe/Rome', //The timezone used to print information about time in the log file
    ),
    'optimize' => array(
        'enabled' => true, //Enable/disable the post-processing optimization
        'unix' => array(
            'png' => '/usr/bin/optipng',
            'jpg' => '/usr/bin/jpegoptim',
            'gif' => '/usr/bin/gifsicle',
            'webp' => '/usr/bin/cwebp',
        ),
        'windows' => array( //. = relative to Soft vendor folder
            'png' => './bin/optipng.exe',
            'jpg' => './bin/jpegoptim.exe',
            'gif' => './bin/gifsicle.exe',
            'webp' => './bin/cwebp.exe',
        )
    ),
];