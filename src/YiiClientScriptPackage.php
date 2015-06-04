<?php

namespace YiiMinifyClientScriptPackage;

class YiiClientScriptPackage
{
    /**
     * Name of the client script package.
     * @var string
     */
    public $name;

    /**
     * Names of depended packages.
     * @var array An array of strings.
     */
    public $depends = array();

    /**
     * Alias of the directory containing the script files.
     * @var string
     */
    public $basePath;

    /**
     * Base URL for the script files.
     * @var string
     */
    public $baseUrl;

    /**
     * List of css files relative to basePath/baseUrl.
     * @var array An array of strings.
     */
    public $css = array();

    /**
     * List of js files relative to basePath/baseUrl.
     * @var array An array of strings.
     */
    public $js          = array();
    public $isExternal  = false;
    protected $minified = false;

    /**
     *  'bootstrap3' => array(
     *      'baseUrl' => '',
     *      'depends' => array('angular'),
     *      'js' => array('bower_components/bootstrap/dist/js/bootstrap.js'),
     *      'css' => array('bower_components/bootstrap/dist/css/bootstrap.css', 'bower_components/bootstrap/dist/css/bootstrap-theme.css')
     *  )
     * @param \PhpParser\Node\Expr\ArrayItem $package
     * @throws \Exception
     */
    public function __construct(\PhpParser\Node\Expr\ArrayItem $package)
    {
        // $this->package->key should be an instance of \PhpParser\Node\Scalar\String_
        $this->name = $package->key->value;

        $baseUrlExpr   = PhpParserHelper::arrayGetValueByKey($package->value, 'baseUrl');
        $this->baseUrl = isset($baseUrlExpr) ? rtrim($baseUrlExpr->value, '/') : null;

        $basePathExpr   = PhpParserHelper::arrayGetValueByKey($package->value, 'basePath');
        $this->basePath = isset($basePathExpr) ? $basePathExpr->value : null;
        if (is_string($this->basePath)) {
            $reason = " This tool can't resolve basePath, because the path of alias can only be recognized by Yii framework at runtime.";
            throw new \Exception("Can\'t resolve basePath of \"{$this->basePath}\" in package \"{$this->name}\".".$reason);
        }

        foreach (array('depends', 'css', 'js') as $group) {
            $array        = PhpParserHelper::arrayGetValueByKey($package->value, $group);
            $this->$group = $array instanceof \PhpParser\Node\Expr\Array_ ? PhpParserHelper::arrayGetValues($array) : array();
        }

        // the package contains only external resources
        $this->isExternal = is_string($this->baseUrl) && MinifyHelper::isExternalUrl($this->baseUrl);
        if ($this->isExternal) {
            $this->minified = true;
        } else {
            $this->prependBaseUrl();
        }
    }

    public function isMinified()
    {
        return $this->minified;
    }

    protected function urlToLocalPath($url, $appBasePath)
    {
        $relativePath = MinifyHelper::normalizePath($url);
        $realpath     = realpath(empty($appBasePath) ? $relativePath : rtrim($appBasePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$relativePath);
        if (false === $realpath) {
            throw new \Exception('File not found: '.$url);
        }

        return $realpath;
    }

    protected function prependBaseUrl()
    {
        if (empty($this->baseUrl)) {
            return;
        }

        $base = $this->baseUrl.'/';
        foreach (array('css', 'js') as $group) {
            $newArray = array();

            foreach ($this->$group as $url) {
                if (is_string($url)) {
                    $newArray[] = $base.$url;
                } else {
                    $left       = new \PhpParser\Node\Scalar\String_($base);
                    $newArray[] = new \PhpParser\Node\Expr\BinaryOp\Concat($left, $url);
                }
            }

            $this->$group = $newArray;
        }
    }

    protected function generateBigMinFileName(array $smallFileHashes, $extension)
    {
        $hash = hash('sha256', implode(',', $smallFileHashes));
        return preg_replace('/[^A-Za-z0-9-]+/', '_', $this->name).'_'.$hash.$extension;
    }

    protected function filterLocals($urls, \YiiMinifyClientScriptPackage\MinifyOptions $options)
    {
        $externals = array();
        $locals    = array();

        foreach ($urls as $url) {
            if (!is_string($url) || MinifyHelper::isExternalUrl($url)) {
                $externals[] = $url;
            } else {
                $localFile = $this->urlToLocalPath($url, $options->getAppBasePath());
                $minFile   = MinifyHelper::findMinifiedFile($localFile, $options->getMinFileSuffix());
                if (empty($minFile)) {
                    throw new \Exception('The minified version was not found for: '.$localFile);
                }

                $locals[$url] = MinifyHelper::findMinifiedFile($localFile, $options->getMinFileSuffix());
            }
        }

        return array($externals, $locals);
    }

    protected function minifyGroup($group, \YiiMinifyClientScriptPackage\MinifyOptions $options)
    {
        list($externals, $locals) = $this->filterLocals($this->$group, $options);
        if (empty($locals)) {
            return;
        }

        $fileHashes = array();
        $tmpBigMin  = tempnam(sys_get_temp_dir(), 'min');
        if ('css' === $group && $options->getRewriteCssUrl()) {
            $cssNewBaseUrl = $options->getCssNewBaseUrl();
            MinifyHelper::concat($locals, $tmpBigMin, function($content, $url) use (&$cssNewBaseUrl, &$fileHashes) {
                $fileHashes[] = hash('sha256', $content);
                return MinifyHelper::rewriteCssUrl($content, $url, $cssNewBaseUrl);
            });
        } else {
            MinifyHelper::concat($locals, $tmpBigMin, function($content) use (&$fileHashes) {
                $fileHashes[] = hash('sha256', $content);
                return $content;
            });
        }

        $bigMin = $this->generateBigMinFileName($fileHashes, $options->getMinFileSuffix().'.'.$group);
        $moveTo = $options->getAppBasePath().DIRECTORY_SEPARATOR.$options->getPublishDir().DIRECTORY_SEPARATOR.$bigMin;
        if (!rename($tmpBigMin, $moveTo)) {
            throw new \Exception("Unable to move file from '{$tmpBigMin}' to '{$moveTo}'.");
        }

        // Final items comprise of externals and the big min local file
        $externals[]  = $options->getPublishDir().'/'.$bigMin;
        $this->$group = $externals;
    }

    public function minify(\YiiMinifyClientScriptPackage\MinifyOptions $options)
    {
        if (!$this->minified) {
            echo 'Package: '.$this->name.PHP_EOL;

            $this->minifyGroup('css', $options);
            $this->minifyGroup('js', $options);
            $this->minified = true;
        }

        $this->minified = true;
    }

    /**
     * @return \PhpParser\Node\Expr\ArrayItem
     */
    public function generateArrayItem()
    {
        $key       = new \PhpParser\Node\Scalar\String_($this->name);
        $items     = array();
        $itemsMeta = array(
            'baseUrl' => $this->isExternal ? $this->baseUrl : '',
            'depends' => $this->depends,
            'css'     => $this->css,
            'js'      => $this->js
        );
        foreach ($itemsMeta as $prop => $value) {
            if (is_array($value) && empty($value)) {
                continue;
            }

            $iKey    = new \PhpParser\Node\Scalar\String_($prop);
            $iValue  = is_string($value) ? new \PhpParser\Node\Scalar\String_($value) : PhpParserHelper::generateArray($value);
            $items[] = new \PhpParser\Node\Expr\ArrayItem($iValue, $iKey);
        }
        $value = new \PhpParser\Node\Expr\Array_($items);

        return new \PhpParser\Node\Expr\ArrayItem($value, $key);
    }

}
