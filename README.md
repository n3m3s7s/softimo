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
In order to use and activate the "on-the-fly" optimization You need Apache (with mod_rewrite) or Nginx, and a single PHP file at the (public) root of your application;

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

Note: Once you run composer, You can navigate to the folder `"vendor/n3m3s7s/softimo/src/samples"` in order to find some sample scripts and the "stub" files for both Apache and Nginx configuration;
if You want to quickly test Soft with some images the folder `"vendor/n3m3s7s/softimo/src/test"` contains some test images to play with.

## Usage

### Basics

The image manipulation is controlled via the URL, eg.:

	<img src="{$root}/i/{@mod1,@mod2,@mod3,...}/{@path/to/your/image}" />

Where the convention is:

1. **$root** := is the standard root/url of your application (could be omitted if You want to use relative paths);
2. **@mod1,@mod2,@mod3,...** := all the manipulations(*) You want to activate on the image;
3. **@path/to/your/image** := the relative path targeting your image;

(*) all manipulations provided by "Glide" are supported but in Soft all manipulations must follow these rules:

1. they cannot be passed as query-string parameters; one of the main purpose of Soft is to enable the Glide manipulations in a seo-friendly manner;
2. all manipulations are separated by a comma(,) character and processed in the given order;
3. every single manipulation is built around a "key" and a "value", separated with an underscore (_) character; for a quick reference about all supported "keys" and "values" please read the official Glide documentation (http://glide.thephpleague.com/1.0/api/quick-reference/);

here are some quick examples:

    /i/w_400,h_300,fit_crop/test/kayaks.jpg    
    
The source image, located at `"test/kayaks.jpg"`, will be resized to 400x300 pixels and the resulting image will match the width and height constraints without distorting the image.

    /i/h_500,or_90,bri_25/test/kayaks.jpg    
    
The source image, located at `"test/kayaks.jpg"`, will be proportionally resized to 500 pixels in height, then "rotated" ("or" stands for "orientation" => http://glide.thephpleague.com/1.0/api/orientation/) by 90 degrees and the resulting image will have a "brightness" of 25 (http://glide.thephpleague.com/1.0/api/adjustments/).

As You can see this way of building links and passing parameters is very similar to the Cloudinary service (http://cloudinary.com/);

**Remember**: You can use every manipulation supported by Glide (http://glide.thephpleague.com/1.0/api/quick-reference/) but Soft will set some automatic manipulations for You:

1. If the source path is targeting a JPG, than the output/response image will be converted to a "progressive JPG"; in Glide this is usually achieved with a manual "encoding", via the "format" parameter (see http://glide.thephpleague.com/1.0/api/encode/);
"progressive jpegs" are usually good for performance and Seo Optimization;
2. If the "q" parameter is not provided by a manipulation or by a "recipe", than Soft will use the "image.quality" value exposed in the configuration file (default is 80); this value will be provided to the Glide factory encoding service (http://glide.thephpleague.com/1.0/api/encode/);
3. If the source image ends with a "@2x.ext" or "@3x.ext" (like a retina asset) but the targeting image file does not exist, than the "dpr" (device pixel ratio) parameter will be automatically set to "2" for "@2x.ext" and to "3" for "@3x.ext" (http://glide.thephpleague.com/1.0/api/pixel-density/);

Examples:

    /i/w_400,h_300,fit_crop/test/kayaks@2x.jpg
    /i/w_400,h_300,fit_crop/test/kayaks@3x.jpg
    
If the file `kayaks@2x.jpg` does not exist (and the "kayaks.jpg" image does) then the resulting image will be double-sized (800x600);

Again, if the file `kayaks@3x.jpg` does not exist (and the "kayaks.jpg" image does) then the resulting image will be triple-sized (1200x900);

This is very useful when You want to use the same modifications but "retina" assets are required and can be served wihout repeating yourself, or when You want to provide different variations of the same image, such as in the new "srcset" HTML5 attribute;

    <img
    src="/i/w_400,h_300,fit_crop/test/kayaks.jpg"
    srcset="/i/w_400,h_300,fit_crop/test/kayaks@2x.jpg 2x, /i/w_400,h_300,fit_crop/test/kayaks@3x.jpg 3x">
    
Note: this is can be rewritten in a very useful/readable/seo-friendly manner by configuring a "Recipe";

### Targeting images

When You deliver Soft as an "on-the-fly" image service, usually the "targeting" image will be relative to the same directory of the "soft.php" file;

if You want to change this behaviour or if You want to omit some part of the "source" path or simply the images are located in a folder that is not available directly under your public App root you can provide a configuration file in order to set a custom directory;

Please refer to the "config" section in order to see how you can change the main "Soft" configuration;

in this section the main concept I want to show You is that when a custom "sourceDir" is provided than the "target" image in the URI must be provided without the full relative path;

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

We assume that the virtual host for the `example.com` domain is pointing to the "public" folder, where the "soft.php" is located;

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

Create a new file, named `'soft.recipes.php'`, located in the same folder of your `'soft.php'` file;

The `'soft.recipes.php'` file must return a PHP array with a set of modifications organized as an associative array;

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

As You can see in the "Recipes" section of this document, now You can easily provide the main 'keys' of this array as a single word in your URLs;

## Caching

Soft does perform two types of "caching" mechanisms; the first one is related to the "physical cache" of the outputted image; 
the second one provides a cache at HTTP Protocol level, using some concepts that all modern browser can understand;

If you want to disable the "phyisical caching" of the files You can set the variable 'cache' to FALSE in your configuration override file;

**Warning:** if the 'cacheDir' parameter is not an absolute filesystem path, than Soft will assume that the 'cache' folder will be relative to the same directory of the 'soft.php' script;
I suggest to place the "cache" folder outside your public root or to limit access to that folder by the web server;

**Important**: the "cache" folder must be writable by your PHP/Webserver account;

### Physical cache

The "Physical cache" provides a convenient way to save all output images, using the server file system in an readable way; 
 if the caching is enabled than all further requests to the same image with the same manipulations set will be processed only once and other clients can benefit of faster 
 rendering; 
 
**Important**: every caching file is "bounded" to the "Last modification time" of the original image file; as a result if the original image is overrided/overwritten wihout changing its name (or set of manipulations), a new "output" image will be created and "cached" accordingly; 

All caching files are stores in the "cacheDir" folder, setted in the configuration file; if this option is not overrided (or if its value is NULL) than Soft will try 
to store all files in the "cache/soft" directory, relative to the "soft.php" script location; obviously the "cacheDir" folder must be writeable, and I suggest to provide a directory outside the webserver scope for security reasons;

The caching structure is the one provided by Glide (http://glide.thephpleague.com/1.0/config/source-and-cache/); 
as a result every source file manipulated by Soft will be cached in a directory with the same name of the original file, which will contains all the variations of the single image;

For example if the source image is "kayaks.jpg" then You will find a directory named "kayaks.jpg" under the "cacheDir" folder;

if You want to purge the cache of a single file (with all its variations) You can easily delete the folder that represents the single source image;
 
However You can use the SoftFactory PHP Class, which expose a set of useful methods to work with Soft assets;

**Note**: in the file https://github.com/n3m3s7s/softimo/blob/master/src/samples/factory_purge.php there is a quick example on how to use the factory tools;

**Remember**: if "post-processing optimization" is enabled, than the cache image will be also optimized only during its creation, accordingly to its mime type;

This can be a time-consuming process (especially for PNGs) but Soft will assure that will be executed only for the first time and every subsequent request to the same resource will benefit of the cache mechanism;

Please refer to the "post-optimization" section of this document for more details;

###HTTP Cache

The "HTTP Cache" acts when the resource is sent back to the browser in a response; modern browsers can benefit of a structured set of headers
 that tell them how to threat assets in order to provide minimal exchanging of data between the client and server;
 
 furthermore Soft will automatically set the "Expire" header of every single image very far in the future, since this practice in very well suited in Advanced SEO optimization;
  
 In details Soft will send the following HTTP headers to the browser:
  
 1. "Cache-Control: public" =
 This tells the browser that the response can be cached normally;
 
 2. "Expires: Sun, 13 Sep 2026 11:32:37 GMT" = This marks the expiration date of the response very far in the future
 (exactly 10 years from the Last Modification time of the source image);
 
 3. "Last-Modified: Thu, 15 Sep 2016 11:32:37 GMT" = This is actually the last modification time of the original source image;
 
 4. "ETag: \"83af74db5d1fefa8d68526a3900f2ee6\"" = Soft will mark every response with an "Entity Tag"; this header will tell to a client how to identify in a unique way a single response from the server;
 accordingly to HTTP caching layer (https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching?hl=it) in a future request a client will check 
 if this can serve a cached response by its "entity tag" so that the resource will not be requested to the server (that will respond with a "304 Not Modified" HTTP status code);
 
 5. "Content-Type: image/jpeg" = the mime-type of the resource; this will be set accordingly to the source image type or if the resource has a custom "format" manipulation (http://glide.thephpleague.com/1.0/api/encode/);

 6. "Content-Length: 11199" = the exact length of the resource, calculated in real time (if the cache is enabled, this will be the content-length of the cached resource, not the source one);


Has seen in the upper statements, every time the same resource will be requested by a client, if the latter is capable of sending in its request a "If-None-Match" header,
than the server will simply respond with a "304 Not Modified" HTTP status, without sending or even reading the physical resource;


## Post-processing optimization
Usually when an image gets manipulated on a server-side base, either with GD or ImageMagick driver, the resulting asset will
 be generally smaller than the original one, but still the new resource will not be "optimized";
 
"To optimize" an image generally refers to the process of stripping metadata and unnecessary informations from the physical image;
  
please take a look at this article by Google (https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/image-optimization?hl=it) in 
order to have a complete point of view of this matter;

generally speaking the "final optimization" cannot be efficiently achieved with a server-side language; 
 
it will be required to use more than a server tool to do the job, but this tools heavily depends on the mime type of the asset You want to "optimize";

actually there are 4/5 tools that are widely used:

1. **jpegtran/jpegoptim**: these are two different tools aimed at optimizing JPG files;
2. **optipng/pngquant**: these are two different tools aimed at optimizing PNG files;
3. **gifsicle**: this the main tool capable to optimize GIF files;
4. **webp**: this is a new image mime-type created by Google; only Chrome and Opera currently supports this format;

Soft will use *only* the following tools:
 
- `jpegoptim` for JPG files; the tool will be invoked with these parameters: `-p -P -o --force --strip-all --all-progressive`;
- `optipng` for PNG files; the tool will be invoked with these parameters: `-o7 -strip all -clobber`;
- `gifsicle` for GIF files; the tool will be invoked with these parameters: `-b -O5`;
- `webp` for WEBP files; the tool will be invoked with these parameters: `-q 80`;

**Note**: please refer to the official documentations of these tools for understanding parameters and keeping version updated;

using these tools manually or at run-time on every source can be very difficult and time-consuming, since they 
are all different tools with different parameters and directives; 
 
furthermore this tools must be installed on the server; while its very simple to configure them on a Unix machine 
on the other side is not so simple to install them on a Windows machine;

Soft can easily detect which operating system it is working on and adapt the "post-optimization" process accordingly;

**Warning**: if Soft is running on a Windows machine, then You don't need to install and compile all these tools - they are available under the "bin" folder 
located in the Soft source directory;

**Warning**: on a Unix machine all tools must be installed by You or your system provider; however most of this tools, once installed, will be 
available under the "/user/bin" path of the machine (this is the standard configuration on which Soft will rely on);

if you want to change the paths of one or more executable, you can adapt the paths in the configuration files; 
please note that the paths are organized into OS "windows" and "unix", since Mac it's a Unix bases machine;
 
###How it works
Every time Soft will cache a resource, than it will be passed to the "post-optimization routine";

if You want to ignore the "post-optimization" process You can disable it in the configuration file;

basically Soft will read the mime-type of the cached resource, and only at the first time, will try to execute a system-call 
with the correct tool, passing the parameters we have seen before;

this can be a time consuming process, especially for PNGs, but it is very worthy, since this will be done once per image/manipulation 
and it does not have nothing to do with Browser/HTTP cache;

**Remember**: nowadays Google and other performance tools know if a given resource has been "post-optimized"; 
usually frameworks and CMSs have plugins or bundles that can perform this kind of operation on your app media library, 
 but this will be done on a "mass" basis and can lead to "override" issues, especially if You or other people want to override an asset (the uploaded asset will override the optimized one and You have to wait for the mass process to take effect);
  instead Soft will operate only on-demand and only on the cached asset, while the original source will never be touched; 



## Logging

If You want to know what Soft is doing You can enable its logging system; Soft will dump every step and even some server side info 
 such as "post-optimization" output result;
 
**Warning**: please keeps logging disabled on a production server since You don't want Soft wasting time writing/reading the file system on every request;

When logging is enabled, a new log file will be automatically created on a daily basis;

the log file will be named "soft.YYYY-MM-DD.log" (where YYYY-MM-DD refers to the current date) and will be placed:

- if the "logDir" option is not null and is an existing directory, than the file will be put here (the folder must be writable);
- if your using the on-the-fly Soft tool, the log file will be placed in the same directory of the "soft.php" script;
- if your using the factory Soft tool, the log file will be placed in the "sourceDir" of your configuration;

The content of a log file is similar to:

```php
[2016-11-23 13:11:19] - PARAM: w_260/backgrounds/dark_souls.jpg
[2016-11-23 13:11:19] - N3m3s7s\Soft\Soft::setSourceFile: Array
(
    [sourceFile] => backgrounds/dark_souls.jpg
    [sourceFilepath] => D:/wamp/www/soft/test/backgrounds/dark_souls.jpg
)

[2016-11-23 13:11:19] - Last-Modified: Thu, 15 Sep 2016 11:32:37 GMT
[2016-11-23 13:11:19] - MODIFICATIONS: Array
(
    [fm] => pjpg
    [w] => 260
    [q] => 80
)

[2016-11-23 13:11:19] - glideServerParameters: Array
(
    [source] => D:/wamp/www/soft/test
    [cache] => cache
    [driver] => gd
    [watermarks] => D:/wamp/www/soft/test
)

[2016-11-23 13:11:19] - Modifications that will be applied to the image: Array
(
    [fm] => pjpg
    [w] => 260
    [q] => 80
)

[2016-11-23 13:11:19] - Optimizing file
[2016-11-23 13:11:19] - Executing: D:\wamp\www\soft\src//bin/jpegoptim.exe -p -P -o --force --strip-all --all-progressive cache/backgrounds/dark_souls.jpg/43c5f8c3b5d9d7141caca3e088fca639
[2016-11-23 13:11:19] - Shell output: Array
(
    [0] => cache/backgrounds/dark_souls.jpg/43c5f8c3b5d9d7141caca3e088fca639 258x162 24bit P JFIF  [OK] 11260 --> 11199 bytes (0.54%), optimized.
)

[2016-11-23 13:11:19] - OUTPUT FILE: cache/backgrounds/dark_souls.jpg/43c5f8c3b5d9d7141caca3e088fca639
[2016-11-23 13:11:19] - OUTPUT HEADERS: Array
(
    [Cache-Control] => public
    [Expires] => Sun, 13 Sep 2026 11:32:37 GMT
    [Last-Modified] => Thu, 15 Sep 2016 11:32:37 GMT
    [ETag] => "83af74db5d1fefa8d68526a3900f2ee6"
    [Content-Type] => image/jpeg
    [Content-Length] => 11199
)
```

as You can see full details will be provided about: 

- given parameter in the url;
- target source file;
- last modification time of the source file;
- parameters that will be passed to the glideServer Factory;
- ultimate manipulations that will be applied on the source image;
- optimization info/results;
- path of the cached file;
- headers sent back to the client;


## Factory Image Optimizations
The "on-the-fly" optimization is very powerful, but sometimes You have to deal with image manipulations
 on the server-side; it will be very nice if the same features of Soft will be available as PHP class and methods;
 
Soft will give to You two approaches to use its features in your PHP scripts:

### SoftImage Class
The "SoftImage" class is a very simple class that can be used to quickly apply some manipulations to a given source file 
and save the resulting asset as a given output image;

its usage is very simple, and You can find an example in this file (https://github.com/n3m3s7s/softimo/blob/master/src/samples/standalone.php):

```php
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
```

**Warning**: this class works under the following considerations:
- no configuration file will be read; the source image and the output image do not follow any rule and they are NOT relative to the "sourceDir";
- no caching file will be created;
- no automatic manipulations will be applied;
- no "recipe" can be used in the manipulations array;
- no "post-optimization" will be performed on the output image;

as You can see this class can be used only to "expose" standard Glide features on given resources, without any logic provided by Soft;

if You want to leverage all Soft features in a PHP script You can use the "Softy" factory class;


### Softy Class
The "Softy", or "SoftFactory", class can be used to expose all features of Soft to your PHP scripts;

all concepts we have encountered so far will be applied on the assets involved by the Softy Class;

the only difference is that its highly recommended to keep only one instance of the Softy class, since 
the configuration override can only be set on the "create" method;

the "create" method accepts both an array of configuration overrides or a path to a custom configuration file;
it returns the factory class itself that can be directly used through a chaining mechanism;

its usage is very simple, and You can find few examples in this folder (https://github.com/n3m3s7s/softimo/blob/master/src/samples):

Let's view some quick usage:

```php
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
```

instead of the "SoftImage" class all assets will be relative to the given "sourceDir", and the manipulated file 
will be accordingly cached and "post-optimized"; all automatics manipulations injected by Soft will be executed and all "recipes" will be available;

You are free to use the SoftImage or Softy class or both for your needs;