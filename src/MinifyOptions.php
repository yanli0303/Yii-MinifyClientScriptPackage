<?php

namespace YiiMinifyClientScriptPackage;

class MinifyOptions
{
    private $appBasePath;
    private $rewriteCssUrl;
    private $minFileSuffix;
    private $publishDir;
    private $cssNewBaseUrl;

    /**
     * @param string $appBasePath Root path to Yii web app (webroot).
     * @param bool $rewriteCssUrl True to rewrite "url()" rules of CSS after relocating CSS files.
     * @param string $minFileSuffix The filename(without extension) suffix of minified files.
     * Defaults to '.min' which means a minified file is named as in "file.min.js".
     * @param string $publishDir The path which is relative to appBasePath for publishing minified resources.
     * @throws \InvalidArgumentException
     * @throws Exception When the publish dir doesn't exist.
     */
    public function __construct($appBasePath, $rewriteCssUrl = true, $minFileSuffix = '.min', $publishDir = 'assets')
    {
        if (!is_string($appBasePath)) {
            throw new \InvalidArgumentException('appBasePath is required.');
        }
        $this->appBasePath = rtrim($appBasePath, '\\/');

        if (!is_dir($this->appBasePath)) {
            throw new \InvalidArgumentException('Directory not found: '.$this->appBasePath);
        }

        if (!is_bool($rewriteCssUrl)) {
            throw new \InvalidArgumentException('rewriteCssUrl should be a boolean.');
        }
        $this->rewriteCssUrl = $rewriteCssUrl;

        if (!is_string($minFileSuffix)) {
            throw new \InvalidArgumentException('minFileSuffix is required.');
        }
        $this->minFileSuffix = $minFileSuffix;

        if (!is_string($publishDir)) {
            throw new \InvalidArgumentException('publishDir is required.');
        }

        $normalizedPublishDir = trim(strtr($publishDir, '\\', '/'), '/');
        $absPublishDir        = $this->appBasePath.DIRECTORY_SEPARATOR.$normalizedPublishDir;
        if (!is_dir($absPublishDir)) {
            throw new \InvalidArgumentException('Directory not found: '.$absPublishDir);
        }
        $this->publishDir    = $normalizedPublishDir;
        $this->cssNewBaseUrl = str_repeat('../', substr_count($this->publishDir, '/') + 1);
    }

    public function getAppBasePath()
    {
        return $this->appBasePath;
    }

    public function getRewriteCssUrl()
    {
        return $this->rewriteCssUrl;
    }

    public function getMinFileSuffix()
    {
        return $this->minFileSuffix;
    }

    public function getPublishDir()
    {
        return $this->publishDir;
    }

    public function getCssNewBaseUrl()
    {
        return $this->cssNewBaseUrl;
    }

}
