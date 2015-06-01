<?php

namespace YiiMinifyClientScriptPackage;

class MinifyHelper
{

    public static function joinNumericArray(&$target, $source)
    {
        foreach ($source as $value) {
            if (false === array_search($value, $target, true)) {
                $target[] = $value;
            }
        }
    }

    /**
     * Replace directory separator with OS directory separator (Win is '\' while Unix is '/').
     * @param string $path A path to normalize.
     */
    public static function normalizePath($path)
    {
        if (DIRECTORY_SEPARATOR === '/' || DIRECTORY_SEPARATOR === '\\') {
            $search = DIRECTORY_SEPARATOR === '/' ? '\\' : '/';
            return strtr($path, $search, DIRECTORY_SEPARATOR);
        }

        return $path;
    }

    /**
     * Check whether the given URL is relative or not.
     * Any URL that starts with either of ['http:', 'https:', '//'] is considered as external.
     * @return boolean If the given URL is relative, returns FALSE; otherwise returns TRUE;
     */
    public static function isExternalUrl($url)
    {
        return 0 === strncasecmp($url, 'https:', 6) || 0 === strncasecmp($url, 'http:', 5) || 0 === strncasecmp($url, '//', 2);
    }

    public static function splitUrl($url)
    {
        $domain = '';
        $query  = '';

        $questionIndex = strpos($url, '?');
        if (false !== $questionIndex) {
            $query = substr($url, $questionIndex);
            $url   = substr($url, 0, $questionIndex);
        }

        $protocolIndex = strpos($url, '//');
        // unable to determine the domain without protocol, e.g. www.google.com/search?q=1234
        if (false !== $protocolIndex) {
            $pathIndex = strpos($url, '/', $protocolIndex + 2);
            if (false !== $pathIndex) {
                $domain = substr($url, 0, $pathIndex);
                $url    = substr($url, $pathIndex);
            } else {
                $domain = $url;
                $url    = '';
            }
        }

        return array($domain, $url, $query);
    }

    /**
     * Resolves the '..' and '.' in the URL.
     *
     * @param string $url The url to process.
     *
     * @return string
     */
    public static function realurl($url)
    {
        if (empty($url)) {
            return $url;
        }
        $url = strtr($url, '\\', '/');
        list($domain, $normalizedUrl, $query) = self::splitUrl($url);
        if (strlen($normalizedUrl) < 2) {
            return $url;
        }

        $urlParts = explode('/', preg_replace('/\/+/', '/', $normalizedUrl));
        $index    = 0;
        while ($index < count($urlParts)) {
            $part = $urlParts[$index];
            if ($part === '.') {
                array_splice($urlParts, $index, 1);
            } elseif ($part === '..' && $index > 0 && $urlParts[$index - 1] !== '..' && $urlParts[$index - 1] !== '') {
                array_splice($urlParts, $index - 1, 2);
                $index -= 1;
            } else {
                $index += 1;
            }
        }
        return $domain.implode('/', $urlParts).$query;
    }

    /**
     * Removes the leading / and specified prefix from the url.
     * @param string $url the url to canonicalize.
     * @param string $prefix url prefix.
     * @return string
     */
    public static function canonicalizeUrl($url, $prefix = '')
    {
        $prefixLength = strlen($prefix);
        if ($prefixLength > 0 && 0 === strncasecmp($prefix, $url, $prefixLength)) {
            $url = substr($url, $prefixLength);
        }

        return ltrim($url, '/');
    }

    /**
     * Make url() rules of CSS absolute.
     * @param string $cssFileContent The content of the CSS file.
     * @param string $cssFileUrl The full URL to the CSS file.
     * @return string
     */
    public static function rewriteCssUrl($cssFileContent, $cssFileUrl, $newBaseUrl = null)
    {
        $newUrlPrefix = self::canonicalizeUrl(dirname($cssFileUrl)).'/';
        if (empty($newBaseUrl)) {
            $newBaseUrl = '';
        }

        // see http://www.w3.org/TR/CSS2/syndata.html#uri
        return preg_replace_callback('#\burl\(([^)]+)\)#i', function($matches) use (&$newUrlPrefix, &$newBaseUrl) {
            $url    = trim($matches[1], ' \'"');
            $ignore = substr($url, 0, 1) === '/' || substr($url, 0, 5) === 'data:' || MinifyHelper::isExternalUrl($url);
            if (!$ignore) {
                $url = $newBaseUrl.MinifyHelper::realurl($newUrlPrefix.$url);
            }

            return "url({$url})";
        }, $cssFileContent);
    }

    /**
     * Concatenate specified text files into one.
     * @param array $files List of files to concatenate (full path).
     * @param string $saveAs The file name(full path) of concatenated file.
     * @param callable $fnProcessFileContent Callback function which accepts an argument of $fileContent,
     * And returns the modified $fileContent.
     * @return bool Returns TRUE on success, otherwise returns FALSE.
     */
    public static function concat($files, $saveAs, $fnProcessFileContent = null)
    {
        $isFirst = true;
        foreach ($files as $key => $fileName) {
            $fileContent = file_get_contents($fileName);
            if (false === $fileContent) {
                throw new \Exception("Failed to get contents of '{$fileName}'.");
            }

            if (is_callable($fnProcessFileContent)) {
                $fileContent = call_user_func($fnProcessFileContent, $fileContent, $key);
            }

            $flags = $isFirst ? 0 : FILE_APPEND;
            if (!file_put_contents($saveAs, $fileContent.PHP_EOL, $flags)) {
                throw new \Exception("Failed to append the contents of '{$fileName}' into '{$saveAs}'.");
            }

            $isFirst = false;
        }
    }

    public static function findMinifiedFile($fileName, $minFileSuffix)
    {
        $lastDotPosition          = strrpos($fileName, '.');
        $extension                = false === $lastDotPosition ? '' : substr($fileName, $lastDotPosition);
        $fileNameWithoutExtension = false === $lastDotPosition ? $fileName : substr($fileName, 0, $lastDotPosition);
        $fileNameSuffix           = substr($fileNameWithoutExtension, -strlen($minFileSuffix));

        if ($minFileSuffix === $fileNameSuffix) {
            // already minified
            return $fileName;
        }

        $minFile = $fileNameWithoutExtension.$minFileSuffix.$extension;
        return file_exists($minFile) ? $minFile : null;
    }

}
