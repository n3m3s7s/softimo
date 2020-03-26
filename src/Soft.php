<?php

namespace N3m3s7s\Soft;

use League\Glide\ServerFactory;
use N3m3s7s\Soft\Exceptions\CacheDirIsNotWritable;
use N3m3s7s\Soft\Exceptions\SourceDirIsNotReadable;
use N3m3s7s\Soft\Exceptions\SourceFileDoesNotExist;
use N3m3s7s\Soft\Exceptions\WatermarkDirIsNotReadable;

error_reporting(0);

define('SOFT_DOCROOT', rtrim(realpath(dirname(__FILE__)), '/'));
@ini_set("gd.jpeg_ignore_warning", 1);
global $settings;


class Soft
{
    protected $settings = [];
    protected $sourceFile;
    protected $sourceFilepath;
    protected $usingPlaceholder = false;
    protected $outputFile;
    protected $cacheDir;
    protected $sourceDir;
    protected $cache;
    protected $glideServer;
    protected $modificationParameters = [];
    protected $mime;

    function __construct()
    {
        global $settings;
        $this->settings = Utils::loadConfig();
        $settings = $this->settings;
    }

    protected function applySettings()
    {
        $settings = $this->settings;

        //Setup cache
        $this->cache = $settings['cache'];
        if ($this->cache) {
            if (!isset($settings['cacheDir']) OR $settings['cacheDir'] == null) {
                $settings['cacheDir'] = sys_get_temp_dir();
            } else {
                if (!is_dir($settings['cacheDir'])) {
                    try {
                        mkdir($settings['cacheDir'], 0775, true);
                    } catch (\Exception $e) {
                        Utils::error($e->getMessage());
                        Utils::error("Cache dir [{$settings['cacheDir']}] is not writable");
                        throw new CacheDirIsNotWritable();
                    }
                } else {
                    if (!is_writable($settings['cacheDir'])) {
                        Utils::error("Cache dir [{$settings['cacheDir']}] is not writable");
                        throw new CacheDirIsNotWritable();
                    }
                }
            }
        } else {
            $settings['cacheDir'] = sys_get_temp_dir();
        }

        //Setup source dir
        if (!isset($settings['sourceDir']) OR $settings['sourceDir'] == null) {
            $settings['sourceDir'] = SOFT_WORKSPACE;
        } else {
            if (!is_readable($settings['sourceDir'])) {
                Utils::error("Source dir [{$settings['sourceDir']}] is not readable");
                throw new SourceDirIsNotReadable();
            }
        }

        //Setup watermarks
        if (!isset($settings['watermarkDir']) OR $settings['watermarkDir'] == null) {
            $settings['watermarkDir'] = $settings['sourceDir'];
        } else {
            if (!is_readable($settings['watermarkDir'])) {
                Utils::error("Watermark Source dir [{$settings['watermarkDir']}] is not readable");
                throw new WatermarkDirIsNotReadable();
            }
        }

        $this->settings = $settings;
    }

    public function setConfig($config)
    {
        global $settings;
        if (is_null($config))
            return $this;
        if (is_array($config)) {
            $this->settings = Utils::merge_config($this->settings, $config);
        }
        //could be the location of a config file
        if (file_exists($config) AND is_readable($config)) {
            $array = include $config;
            $this->settings = Utils::merge_config($this->settings, $array);
        }
        $settings = $this->settings;
        return $this;
    }

    public function file($file)
    {
        return $this->setSourceFile($file);
    }

    public function setSourceFile($sourceFile)
    {
        $this->applySettings();
        $settings = $this->settings;
        if (Utils::serverOS() == 1) {
            $sourceFile = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $sourceFile);
        } else {
            $sourceFile = urldecode($sourceFile);
        }

        if (strpos($sourceFile, '@2x') !== false) {
            $settings['image']['upscaling'] = true;
            $sourceFile = str_replace('@2x', '', $sourceFile);
            $this->modification('dpr', 2);
        }
        if (strpos($sourceFile, '@3x') !== false) {
            $settings['image']['upscaling'] = true;
            $sourceFile = str_replace('@3x', '', $sourceFile);
            $this->modification('dpr', 3);
        }
        //set JPG as progressive if the original file is JPG
        if (strpos(strtolower($sourceFile), 'jpg') !== false) {
            if (!isset($this->modificationParameters['fm'])) {
                $this->modification('fm', 'pjpg');
            }
        }
        //set WEBP
        if (strpos(strtolower($sourceFile), '.webp') !== false) {
            $sourceFile = str_replace('.webp', '', $sourceFile);
            $this->modification('fm', 'webp');
            if (!isset($this->modificationParameters['q'])) {
                $this->modification('q', 75);
            }
        }

        $sourceFilepath = rtrim($settings['sourceDir'], '/') . '/' . $sourceFile;
        if (!file_exists($sourceFilepath)) {
            if ($settings['placeholder']['enabled']) {
                $sourceFile = $settings['placeholder']['file'];
                $sourceFilepath = rtrim($settings['sourceDir'], '/') . '/' . $sourceFile;
                if (!file_exists($sourceFilepath)) {
                    Utils::error("Placeholder file [$sourceFilepath] does not exist");
                    throw new SourceFileDoesNotExist();
                }
                $this->usingPlaceholder = true;
                $this->sourceFile = $sourceFile;
                $this->sourceFilepath = $sourceFilepath;
                return;
            }
            Utils::error("Source file [$sourceFilepath] does not exist");
            throw new SourceFileDoesNotExist();
        }
        $this->sourceFile = $sourceFile;
        $this->sourceFilepath = $sourceFilepath;
        Utils::log(compact('sourceFile', 'sourceFilepath'), __METHOD__);
        return $this;
    }

    public function modification($key, $value)
    {
        $this->modificationParameters[$key] = $value;
        return $this;
    }

    public function modify(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            $this->modificationParameters[$key] = $value;
        }
        return $this;
    }

    /**
     * @return \League\Glide\Server
     */
    public function createServer()
    {
        $settings = $this->settings;

        $cacheDir = $settings['cacheDir'];
        $sourceDir = $settings['sourceDir'];
        $glideServerParameters = [
            'source' => $sourceDir,
            'cache' => $cacheDir,
            'driver' => $settings['driver'],
            'watermarks' => $settings['watermarkDir'],
        ];
        Utils::log($glideServerParameters, 'glideServerParameters');

        $glideServer = ServerFactory::create($glideServerParameters);
        $recipes = Utils::loadRecipes();
        if (!empty($recipes)) {
            $glideServer->setPresets($recipes);
        }
        $this->glideServer = $glideServer;
        return $this->glideServer;
    }


    //Process the image
    protected function process()
    {
        $settings = $this->settings;

        $glideServer = $this->createServer();

        $cacheDir = $settings['cacheDir'];
        $sourceFileName = $this->sourceFile;

        $isGif = false;
        if (strtolower(substr($sourceFileName, -3)) === 'gif') {
            $isGif = true;
        }

        $modificationParameters = $this->modificationParameters ? $this->modificationParameters : null;
        Utils::log($modificationParameters, "Modifications that will be applied to the image");
        $cacheFileExists = $glideServer->cacheFileExists($sourceFileName, $modificationParameters);
        if ($isGif) {
            $conversionResult = $this->sourceFilepath;
            $cacheFileExists = true;
        } else {
            $conversionResult = $cacheDir . '/' . $glideServer->makeImage($sourceFileName, $modificationParameters);
        }

        $this->outputFile = $conversionResult;
        if ($cacheFileExists === false){
            $this->handleSavedFile();
            try {
                $this->optimize();
            } catch (\Exception $e) {
                Utils::log($e->getMessage(), "Optimization error!");
            }
        }

        return $conversionResult;
    }

    protected function handleSavedFile()
    {
        Utils::log($this->modificationParameters, __METHOD__);
        if(isset($this->modificationParameters['fm']) && $this->modificationParameters['fm'] === 'webp'){
            // Fix WebP binary
            Utils::log(filesize($this->outputFile), 'FILESIZE');
            if (filesize($this->outputFile) % 2 === 1) {
                file_put_contents($this->outputFile, "\0", FILE_APPEND);
                Utils::log($this->outputFile, "Added binary flag for WebP image");
            }
        }
    }


    // Performs the post-optimization on the image
    protected function optimize()
    {
        $settings = $this->settings;
        if ($settings['optimize']['enabled'] == false) {
            return;
        }
        Utils::log('Optimizing file');
        if(isset($this->modificationParameters['fm']) && $this->modificationParameters['fm'] === 'webp'){
            Utils::log('DO NOT optimize cached file on WEBP, since it is already optimized - Exit #1');
            return;
        }
        $file = $this->outputFile;
        if (isset($this->mime)) {
            $mime = $this->mime;
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file);
            finfo_close($finfo);
        }
        Utils::log($mime, "Calculated mime");
        switch ($mime) {
            default:
            case 'image/jpg':
            case 'image/jpeg':
            case 'image/pjpg':
                $format = 'jpg';
                break;

            case 'image/png':
                $format = 'png';
                break;

            case 'image/gif':
                $format = 'gif';
                break;

            case 'image/webp':
                $format = 'webp';
                break;
        }

        if($format === 'webp'){
            Utils::log('DO NOT optimize cached file on WEBP, since it is already optimized - Exit #2');
            return;
        }

        $isWin = Utils::serverOS() == 1;

        if ($isWin and $format == 'png') {
            //do not optimize png on windows, since it is very heavy
            return;
        }

        $suppressOutput = ($isWin ? '' : ' 1> /dev/null 2> /dev/null');

        $bin = ($isWin) ? $settings['optimize']['windows'] : $settings['optimize']['unix'];

        foreach ($bin as $key => $value) {
            if ($value[0] == '.') {
                $bin[$key] = SOFT_DOCROOT . '/' . substr($value, 1);
            }
        }

        $bin['jpg'] .= " -p -P -o --force --strip-all --all-progressive :source";
        $bin['png'] .= " -o3 -strip all -out :target -clobber :source";
        $bin['gif'] .= " -b -O5 :source :target";
        $bin['webp'] .= " -q 75 :source -o :target";

        $cmd = str_replace([':source', ':target'], [$file, $file], $bin[$format]);

        Utils::log($cmd, "Executing");

        $command = escapeshellcmd($cmd) . $suppressOutput;

        exec($command, $output, $result);

        Utils::log($output, "Shell output");

        if ($result == 127) {
            throw new \Exception(sprintf('Command "%s" not found.', $command));
        } else if ($result != 0) {
            throw new \Exception(sprintf('Command failed, return code: %d, command: %s', $result, $command));
        }
    }

}