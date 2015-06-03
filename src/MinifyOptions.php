<?php

namespace YiiMinifyClientScriptPackage;

class MinifyOptions
{
    public $appBasePath;

    /**
     * @var bool Whether to rewrite "url()" rules of CSS after relocating CSS files.
     * Defaults to true.
     */
    public $rewriteCssUrl = true;

    /**
     * @var string The filename(without extension) suffix of minified files.
     * Defaults to '.min' which means a minified file is named as in "jquery.min.js".
     */
    public $minFileSuffix = '.min';

    /**
     * The path which is relative to appBasePath for publishing minified resources.
     * @var string
     */
    public $publishDir = 'assets';

    protected function validate()
    {
        if (!is_string($this->appBasePath)) {
            throw new \InvalidArgumentException('"appBasePath" should be a string.');
        }

        if (!is_dir($this->appBasePath)) {
            throw new \InvalidArgumentException('Directory not found: '.$this->appBasePath);
        }

        if (!is_bool($this->rewriteCssUrl)) {
            throw new \InvalidArgumentException('"rewriteCssUrl" should be a boolean.');
        }

        if (!is_string($this->minFileSuffix)) {
            throw new \InvalidArgumentException('"minFileSuffix" should be a string.');
        }

        if (!is_string($this->publishDir)) {
            throw new \InvalidArgumentException('"publishDir" should be a string.');
        }

        $this->publishDir = trim(strtr($this->publishDir, '\\', '/'), '/');
    }

    public function getPublishDir()
    {
        $this->validate();
        return rtrim($this->appBasePath, '\\/').DIRECTORY_SEPARATOR.$this->publishDir;
    }

    public function getCssNewBaseUrl()
    {
        $this->validate();
        return str_repeat('../', substr_count($this->publishDir, '/') + 1);
    }

}
