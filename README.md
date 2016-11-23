S.O.F.T.I.M.O. (aka "Soft")
=====

# Server and on-the-fly Image Optimization
#### by Fabio Politi (fabio.politi.dev@gmail.com)

Convert and optimize images with **Glide** (http://glide.thephpleague.com), both server-side and on-the-fly. Supports caching, recipes, image quality settings and post-processing optimization.

## Installation

Add "n3m3s7s/softimo" as a requirement to your composer.json:

```json
{    
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/n3m3s7s/softimo.git"
        }
    ],
    "require": {
        "n3m3s7s/softimo": "@dev"
    }
}
```

Then run `composer update` or `composer install`;

**Warning**: I strongly suggest to run the command "composer dump-autoload --optimize" after composer has finished to download and install all the packages;


## On-the-fly Image Optimizations
In order to use and activate the "on-the-fly" optimization You need Apache or Nginx, and a single PHP file at the (public) root of your application;

Now, if You are using Apache, open the *.htaccess* file of your project or create a new one;
add this rule to the .htaccess file (https://github.com/n3m3s7s/softimo/blob/master/src/samples/htaccess.txt):

    RewriteEngine On
    ### IMAGE RULES
    RewriteRule ^i\/(.+)$ soft.php?param=$1 [B,L,NC]

Instead, if You are using Nginx, You should add this rule to your Virtual host configuration (https://github.com/n3m3s7s/softimo/blob/master/src/samples/nginx.txt):

    # nginx configuration
    location /i {
        rewrite ^/i\/(.+)$ /soft.php?param=$1 break;
    }

Create a "soft.php" file under the public root folder of your project and fill it with these lines of code (https://github.com/n3m3s7s/softimo/blob/master/soft.php):

**Attention**: if You are using Laravel this file must be created under the "public/" Laravel directory;

```php
<?php

require_once 'vendor/autoload.php'; //Please verify that You are actually requiring the correct path

define('SOFT_WORKSPACE', rtrim(realpath(dirname(__FILE__) ), '/'));

use N3m3s7s\Soft\SoftServer as Server;

$server = new Server();
$server->response();
```

That's all! You can now use *Soft* to dinamically process image on the fly!

Note: Once you run composer, You can navigate to the folder "vendor/n3m3s7s/softimo/src/samples" in order to find some sample scripts and the "stub" files for both Apache and Nginx configuration;
if You want to quickly test Soft with some images the folder "vendor/n3m3s7s/softimo/src/test" contains some test images to play with.

## Usage

### Basics

The image manipulation is controlled via the URL, eg.:

	<img src="{$root}/i/{@mod1,@mod2,@mod3,...}/{@path/to/your/image}" />

Where the convention is:

1. *$root* := is the standard root/url of your application (could be omitted if You want to use relative paths);
2. *@mod1,@mod2,@mod3,...* := all the manipulations(*) You want to activate on the image;
3. *@path/to/your/image* := the relative path pointing to your image(**);

(*) all manipulations provided by "Glide" are supported but in Soft all manipulations must follow these rules:

1. they cannot be passed as query-string parameters; one of the main purpose of Soft is to enable the Glide manipulations in a seo-friendly manner;
2. all manipulations are separated by a comma(,) character and processed in the given order;
3. every single manipulation is built around a "key" and a "value", separated with an underscore (_) character; for a quick reference about all supported "keys" and "values" please read the official Glide documentation (http://glide.thephpleague.com/1.0/api/quick-reference/);

here are some quick examples:

    /i/w_400,h_300,fit_crop/test/kayaks.jpg    
    
The source image, located at "test/kayaks.jpg", will be resized to 400x300 pixels and the resulting image will match the width and height constraints without distorting the image.

    /i/h_500,or_90,bri_25/test/kayaks.jpg    
    
The source image, located at "test/kayaks.jpg", will be proportionally resized to 500 pixels in height, then "rotated" ("or" stands for "orientation" => http://glide.thephpleague.com/1.0/api/orientation/) the image by 90 degrees and the resulting image will have a "brightness" of 25 (http://glide.thephpleague.com/1.0/api/adjustments/).

As You can see this way of building links and passing parameters is very similar to the Cloudinary service (http://cloudinary.com/);

Remember: You can use every manipulation supported by Glide (http://glide.thephpleague.com/1.0/api/quick-reference/) but Soft will set some automatic manipulations for You:

1. If the source path is targeting a JPG, than the output/response image will be converted to a "progressive JPG"; in Glide this is usually achieved with a manual "encoding", via the "format" parameter (see http://glide.thephpleague.com/1.0/api/encode/);
"progressive jpegs" are usually good for performance and Seo Optimization;
2. If the "q" parameter is not provided by a manipulation or by a recipe, than Soft will use the "image.quality" value exposed in the configuration file (default is 80); this value will be provided to the Glide factory encoding service (http://glide.thephpleague.com/1.0/api/encode/);
3. If the source image ends with a "@2x.ext" or "@3x.ext" (like a retina asset) but the targeting image file does not exist, than the "dpr" (device pixel ratio) parameter will be automatically set to "2" for "@2x.ext" and to "3" for "@3x.ext" (http://glide.thephpleague.com/1.0/api/pixel-density/);

Examples:

    /i/w_400,h_300,fit_crop/test/kayaks@2x.jpg
    /i/w_400,h_300,fit_crop/test/kayaks@3x.jpg
    
If the file "kayaks@2x.jpg" does not exist (and the "kayaks.jpg" image does) then the resulting image will be double-sized (800x600);

Again, if the file "kayaks@3x.jpg" does not exist (and the "kayaks.jpg" image does) then the resulting image will be triple-sized (1200x900);

This is very useful when You want to use the same modifications but "retina" assets are required and can be served wihout repeating yourself, or when You want to provide different variations of the same image, such as in the new "srcset" HTML5 attribute;

    <img
    src="/i/w_400,h_300,fit_crop/test/kayaks.jpg"
    srcset="/i/w_400,h_300,fit_crop/test/kayaks@2x.jpg 2x, /i/w_400,h_300,fit_crop/test/kayaks@3x.jpg 3x">
    
Note: this is can be rewritten in a very useful/readable/sep-friendly manner by configuring a "Recipe";

### Targeting images

When You deliver Soft as an "on-the-fly" image service, usually the "targeting" image will be relative to the same directory of the "soft.php" file;

if You want to change this behaviour or if You want to omit some part of the "source" path or simply the images are located in a folder that is not available directly under your public App root you can provide a configuration file in order to set a custom directory;

Please refer to the "config" section in order to see how you can change the main "Soft" configuration;

in this section the main concept I want to show You is that a custom "sourceDir" is provided that the "target" imae in the URI must be provided without the full relative path;

#####Look! We have examples:

Consider the following app structure (content of "/var/www/example.com"):

    app
        ...
    storage
        media
            images
                logos
                    logo.png
                kayaks.jpg
                
    public
        .htaccess
        index.php
        soft.php
    vendor
    composer.json
    composer.lock
    ...

We assume that the virtual host for the example.com domain is pointing to the "public" folder, where the "soft.php" is located;

as You can see in this configuration the "kayaks.jpg" file cannot be accessed in a browser;

if You want to use the on-the-fly manipulation on the "kayaks.jpg" file, You can provide a custom configuration file overriding the "sourceDir", like so:

```php
<?php

return [
    'cacheDir' => '/var/www/example.com/storage/media/images'
];
```

Then every image under the 'cacheDir' folder can be manipulated directly in the Url, without providing the full or relative path of your media storage;

    # I can output the image even if it is unreachable 
    http://example.com/i/w_400,h_300,fit_crop/kayaks.jpg
    =====> the "target image" will be '/var/www/example.com/storage/media/images/kayaks.jpg'
    # Please consider that all paths relative to the 'cacheDir' folder must be provided as well
    http://example.com/i/w_400,h_300,fit_crop/logos/logo.png
    =====> the "target image" will be '/var/www/example.com/storage/media/images/logos/logo.png'
    
### Recipes

A "recipe" is a group of manipulations that can be organized under a "key" string value; 
a recipe allows to instantly target a particular set of manipulations in a simple and readable way, avoiding the usage of explicit manipulations in the URI;

furthermore they can play a role in Search Engine Optimization of your app, since they can be easily mapped as "semantic" paths;

#####Look! We have examples:

Let's take the previous Url used so far, such as:

    /i/w_400,h_300,fit_crop/kayaks.jpg
    
and consider a simple PHP array, exposing this recipe with the same set of modifications of the "explicit" Url:

```php
<?php

return [
    'medium' => [
        'w' => 400,   
        'h' => 300,   
        'fit' => 'crop',   
    ]    
];
```

Then the same output image can be referenced simply as:

    /i/medium/kayaks.jpg
    # or even
    /i/medium/kayaks@2x.jpg
    
Wait, you can even provide other modifications or override existing ones, if it is needed:

    # overriding the 'width' parameter
    /i/medium,w_300/kayaks.jpg
    
    # adding a 'filter' to the output image
    /i/medium,filt_sepia/kayaks.jpg
    
That's a lot better now and the readability of your manipulations is even enhanced!

**Ok, but I can provide my custom set of recipes?**

Well, this can be easily done per-project; please refer to the "configuration" section to full details;
    
### Configuration
Soft includes a configuration file in order to modify the behaviour of the script, or to enabling/disabling some features;
open the PHP file `vendor/n3m3s7s/softimo/src/config/config.php` (https://github.com/n3m3s7s/softimo/blob/master/src/config/config.php);
by default it contains all settings that can be managed:

```php
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
```

However is higly discouraged to edit this file manually, as It can be overrided by composer if the "softimo" package gets an update;

In order to create a custom configuration set You can simply create a new PHP file, named 'soft.config.php', located in the same folder of your 'soft.php' file;

The 'soft.config.php' file must return a PHP array with only the values You want to override;

You can open the `vendor/n3m3s7s/softimo/src/samples/soft.config.php` file (https://github.com/n3m3s7s/softimo/blob/master/src/samples/soft.config.php) to get a quick way of a sample override file;

```php
<?php
return [
    'cacheDir' => 'cache',
    'sourceDir' => 'D:/wamp/www/soft/test',
];
```

In this case we are telling Soft that the 'sourceDir' for every "target image" will be 'D:/wamp/www/soft/test', while the 'cacheDir' will be the 'cache' directory (relative to the same path of the 'soft.php' file);

So You can provide a small set of key/values in order to adapt Soft to your needs;

#### Recipes: Configuration
The same logic for the "configuration override" applies for your custom "recipes";

Create a new file, named 'soft.recipes.php', located in the same folder of your 'soft.php' file;

The 'soft.recipes.php' file must return a PHP array with a set of modifications organized as an associative array;

You can open the `vendor/n3m3s7s/softimo/src/samples/soft.recipes.php` file (https://github.com/n3m3s7s/softimo/blob/master/src/samples/soft.recipes.php) to get a quick way of simple custom 'recipes';

```php
<?php
return array(
    'sepia' => [
        'w' => 200,
        'h' => 200,
        'filt' => 'sepia',
    ],
    'grey' => [
        'w' => 350,
        'sharp' => 15,
        'filt' => 'greyscale',
    ],
);
```

As You can see in the "Recipes" section of this document, now You can easily provided the main 'keys' of this array as a single word in your URLs;











If you want to enable the "caching" of the files You can set the variable 'cache' to TRUE;
**Warning:** in able to work You have to create a "cache" folder at `vendor/n3m3s7s/yajit/src/Yajit` and it must be writable by your PHP/Webserver account;



There are four possible modes:

- `0` none
- `1` resize
- `2` resize and crop (used in the example)
- `3` crop
- `4` resize to fit

If you're using mode `2` or `3` for image cropping you need to specify the reference position (gravity):

	+---+---+---+
	| 1 | 2 | 3 |
	+---+---+---+
	| 4 | 5 | 6 |
	+---+---+---+
	| 7 | 8 | 9 |
	+---+---+---+

If you're using mode `2` or `3` for image cropping, there is an optional fifth parameter for background color. This can accept shorthand or full hex colors.

- *For `.jpg` images, it is advised to use this if the crop size is larger than the original, otherwise the extra canvas will be black.*
- *For transparent `.png` or `.gif` images, supplying the background color will fill the image. This is why the setting is optional*

The extra fifth parameter makes the URL look like this:

	<img src="{$root}/i/2/80/80/5/fff{image/@path}/{image/filename}" />

- *If you wish to crop and maintain the aspect ratio of an image but only have one fixed dimension (that is, width or height), simply set the other dimension to 0*


### External sources & Trusted Sites (still in progress)

In order pull images from external sources, you must set up a white-list of trusted sites. To do this, edit the "config.php" file under the setting "trusted-sites". To match anything use a single asterisk (`*`).

The URL then requires a sixth parameter, external, (where the fourth and fifth parameter may be optional), which is simply `1` or `0`. By default, this parameter is `0`, which means the image is located on the same domain as YAJIT. Setting it to `1` will allow YAJIT to process external images provided they are on the Trusted Sites list.

	<img src="{$root}/i/1/80/80/1/{full/path/to/image}" />
	                                ^ External parameter

### Recipes (basic functionality)

Recipes are named rules for the YAJIT settings which help improve security and are more convenient. Open the file `vendor/n3m3s7s/yajit/src/Yajit/config/recipes.php`. A recipe URL might look like:

	<img src="{$root}/i/thumbs{image/@path}/{image/filename}" />

When YAJIT parses a URL like this, it will check the recipes file for a recipe with a handle of `thumbs` and apply it's rules. You can completely disable dynamic YAJIT rules and choose to use recipes only which will prevent a malicious user from hammering your server with large or multiple YAJIT requests.

Recipes can be copied between installations and changes will be reflected by every image using this recipe.