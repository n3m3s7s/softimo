<?php
namespace N3m3s7s\Soft;
use League\Glide\ServerFactory;
use N3m3s7s\Soft\Exceptions\SourceFileDoesNotExist;

class SoftImage
{
    /**
     * @var string The path to the input image.
     */
    protected $sourceFile;
    /**
     * @var array The modification the need to be made on the image.
     *            Take a look at Glide's image API to see which parameters are possible.
     *            http://glide.thephpleague.com/1.0/api/quick-reference/
     */
    protected $modificationParameters = [];
    public static function create($sourceFile)
    {
        return (new static())->setSourceFile($sourceFile);
    }
    public function setSourceFile($sourceFile)
    {
        if (!file_exists($sourceFile)) {
            throw new SourceFileDoesNotExist();
        }
        $this->sourceFile = $sourceFile;
        return $this;
    }
    public function modify(array $modificationParameters)
    {
        $this->modificationParameters = $modificationParameters;
        return $this;
    }
    public function save($outputFile)
    {
        $sourceFileName = pathinfo($this->sourceFile, PATHINFO_BASENAME);
        $cacheDir = sys_get_temp_dir();
        $glideServerParameters = [
            'source' => dirname($this->sourceFile),
            'cache' => $cacheDir,
            'driver' => 'gd',
        ];
        if (isset($this->modificationParameters['mark'])) {
            $watermarkPathInfo = pathinfo($this->modificationParameters['mark']);
            $glideServerParameters['watermarks'] = $watermarkPathInfo['dirname'];
            $this->modificationParameters['mark'] = $watermarkPathInfo['basename'];
        }
        $glideServer = ServerFactory::create($glideServerParameters);
        $modificationParameters = $this->modificationParameters ? $this->modificationParameters : null;
        $conversionResult = $cacheDir.'/'.$glideServer->makeImage($sourceFileName, ($modificationParameters));
        rename($conversionResult, $outputFile);
        return $outputFile;
    }
}