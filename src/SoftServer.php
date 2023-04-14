<?php

namespace N3m3s7s\Soft;

use Intervention\Image\ImageManagerStatic as Image;

class SoftServer extends Soft
{

    function __construct()
    {
        parent::__construct();

        $param = $_GET['param'];
        Utils::log($param, 'Modification parameter');
        $command = explode('/', $param)[0];
        $sourceFile = str_replace($command . '/', '', $param);
        Utils::log($sourceFile, 'Given SourceFile');
        $this->setSourceFile($sourceFile);

        if ($this->usingPlaceholder == false) {
            $this->header('Cache-Control', 'public');
            $last_modified_gmt = $etag = $expires = null;
            $last_modified = is_file($this->sourceFilepath) ? filemtime($this->sourceFilepath) : null;
            if ($last_modified) {
                $last_modified_gmt = gmdate('D, d M Y H:i:s', $last_modified) . ' GMT';
                $etag = md5($last_modified . $this->sourceFilepath);
                $offset = 10 * 365 * 24 * 60 * 60;
                $expires = gmdate('D, d M Y H:i:s', $last_modified + $offset) . ' GMT';

                $this->header('Expires', $expires);
                $this->header('Last-Modified', $last_modified_gmt);
                $this->header('Cache-Control', 'max-age=31536000, public');
                $this->header('ETag', "\"$etag\"");
                Utils::log($last_modified_gmt, "Last-Modified");
            }

            // Check to see if the requested image needs to be generated or if a 304
            // can just be returned to the browser to use it's cached version.
            if ($this->cache === true && (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH']))) {
                if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == $last_modified_gmt || str_replace('"', NULL, stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $etag) {
                    $this->sendHeaders();
                    Utils::renderStatusCode(Utils::HTTP_NOT_MODIFIED);
                    Utils::log("Returning 304 code");
                    exit;
                }
            }
        }

        $this->processCommand($command);
        $this->process();
    }


    public function header($key, $value)
    {
        $this->headers[$key] = $value;
    }


    private function processCommand($command)
    {
        $settings = $this->settings;

        //parse directives
        $directives = explode(',', $command);

        foreach ($directives as $directive) {
            $directive = str_replace('|', ',', $directive);
            if (strpos($directive, '_') !== false) {
                list($modifier, $value) = explode('_', $directive);
            } else {
                $modifier = 'p';
                $value = $directive;
            }

            $this->modification($modifier, $value);
        }

        //automatic modifications
        if (!isset($this->modificationParameters['q'])) {
            $this->modification('q', $settings['image']['quality']);
        }

        if (isset($this->modificationParameters['fm'])) {
            $format = $this->modificationParameters['fm'];
            switch ($format) {
                case 'pjpg':
                case 'jpeg':
                case 'jpg':
                    $this->mime = 'image/jpeg';
                    break;
                case 'png':
                    $this->mime = 'image/png';
                    break;
                case 'webp':
                    $this->mime = 'image/webp';
                    break;
            }
        }

        Utils::log($this->modificationParameters, 'MODIFICATIONS');
    }


    private function raw($img)
    {
        $image_path = $img->basePath();
        if (!isset($this->mime)) {
            $data = file_get_contents($image_path);
            $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $data);
            $length = strlen($data);
        } else {
            $mime = $this->mime;
            $length = filesize($image_path);
        }

        if ($this->mime === 'image/webp' && $length % 2 === 1) {
            $length++;
        }

        $this->header('Content-Type', $mime);
        $this->header('Content-Length', $length);

        Utils::log($this->headers, "OUTPUT HEADERS");

        if (function_exists('app') && is_a($app = app(), 'Illuminate\Foundation\Application')) {

            $response = \Response::make($data);
            foreach ($this->headers as $key => $value) {
                $response->header($key, $value);
            }

            echo $response;

        } else {
            $this->sendHeaders();

            readfile($image_path);
        }

        $fixWebp = isset($this->settings['image']['fix_webp_bytecode']) && true === $this->settings['image']['fix_webp_bytecode'];

        if ($fixWebp && $this->mime === 'image/webp') {
            echo '\0';
        }
    }

    private function sendHeaders()
    {
        foreach ($this->headers as $key => $value) {
            header(sprintf("%s: %s", $key, $value));
        }
    }

    public function response()
    {

        Utils::log($this->outputFile, 'OUTPUT FILE');

        try {
            $img = Image::make($this->outputFile);
            $this->raw($img);
            exit();
        } catch (\Exception $e) {
            Utils::error($e->getMessage(), 'RESPONSE ERROR');
        }

    }
}
