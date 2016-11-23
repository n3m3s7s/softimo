S.O.F.T.I.M.O. (aka "Soft")
=====

# Server and on-the-fly Image Optimization
#### by Fabio Politi (fabio.politi.dev@gmail.com)

Convert and optimize images with Glide, both server-side and on-the-fly. Supports caching, recipes, image quality settings and post-processing optimization.

## Installation

Add "n3m3s7s/yajit" as a requirement to composer.json:

```json
{    
     "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/n3m3s7s/yajit.git"
        }
    ],
    "require": {
        "n3m3s7s/yajit": "1.1.*@dev"
    }
}
```

Then run `composer update` or `composer install`;

**Warning**: I strongly suggest to run the command "composer dump-autoload --optimize" after composer has finished to download and install all the packages;

Now, if You are using Apache, open the *.htaccess* file of your project or create a new one;
add this rule to the .htaccess file:

    RewriteEngine On
    ### IMAGE RULES
    RewriteRule ^i\/(.+)$ yajit.php?param=$1 [B,L,NC]

Instead if You are using Nginx, You should add this rule to your Vhost config:

    # nginx configuration
    location /i {
        rewrite ^/i\/(.+)$ /yajit.php?param=$1 break;
    }

Create a "yajit.php" file under the root folder of your project and fill it with these lines of code (https://github.com/n3m3s7s/yajit/blob/master/yajit.php):

```php
<?php
require 'vendor/autoload.php';
define('WORKSPACE', rtrim(realpath(dirname(__FILE__) ), '/'));

use Yajit\Yajit;

$yajit = new Yajit();
$yajit->process();
```

That's all! You can now use Yajit to dinamically process image on the fly!

## Usage

### Basics

The image manipulation is controlled via the URL, eg.:

	<img src="{$root}/i/2/80/80/5/fff{image/@path}/{image/filename}" />

The extension accepts four numeric settings and one text setting for the manipulation.

1. mode
2. width
3. height
4. reference position (for cropping only)
5. background color (for cropping only)

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

### Configuration
Yajit includes a configuration file in order to modify the behaviour of the script, or to enabling/disabling some features;
open the PHP file `vendor/n3m3s7s/yajit/src/Yajit/config/config.php`;
by default it contains all settings that can be managed:

```php
<?php
global $settings;

$settings = array(
    'image' => array(
        'cache' => false,
        'quality' => 80,
        'disable_upscaling' => 'yes',
        'disable_regular_rules' => 'no',
    ),    
    'server' => array(
        'log' => false,
        'timezone' => 'Europe/Rome',
    )
);
```

If you want to enable the "caching" of the files You can set the variable 'cache' to TRUE;
**Warning:** in able to work You have to create a "cache" folder at `vendor/n3m3s7s/yajit/src/Yajit` and it must be writable by your PHP/Webserver account;

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