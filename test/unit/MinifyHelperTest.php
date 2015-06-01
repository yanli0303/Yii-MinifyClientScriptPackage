<?php

class MinifyHelperTest extends PHPUnit_Framework_TestCase
{
    const BASE_URL = '/MinifyTest';

    private $fileHandlesToClose = array();

    /**
     * Copied from Yii CFileHelper class.
     * Removes a directory recursively.
     *
     * @param string $directory to be deleted recursively.
     * @param array  $options   for the directory removal. Valid options are:
     *                          <ul>
     *                          <li>traverseSymlinks: boolean, whether symlinks to the directories should be traversed too.
     *                          Defaults to `false`, meaning that the content of the symlinked directory would not be deleted.
     *                          Only symlink would be removed in that default case.</li>
     *                          </ul>
     *                          Note, options parameter is available since 1.1.16
     *
     * @since 1.1.14
     */
    private static function removeDirectory($directory, $options = array())
    {
        if (!isset($options['traverseSymlinks'])) {
            $options['traverseSymlinks'] = false;
        }
        $items = glob($directory.DIRECTORY_SEPARATOR.'{,.}*', GLOB_MARK | GLOB_BRACE);
        foreach ($items as $item) {
            if (basename($item) == '.' || basename($item) == '..') {
                continue;
            }
            if (substr($item, -1) == DIRECTORY_SEPARATOR) {
                if (!$options['traverseSymlinks'] && is_link(rtrim($item, DIRECTORY_SEPARATOR))) {
                    unlink(rtrim($item, DIRECTORY_SEPARATOR));
                } else {
                    self::removeDirectory($item, $options);
                }
            } else {
                unlink($item);
            }
        }
        if (is_dir($directory = rtrim($directory, '\\/'))) {
            if (is_link($directory)) {
                unlink($directory);
            } else {
                rmdir($directory);
            }
        }
    }

    /**
     * Copied from Yii CFileHelper class.
     * Shared environment safe version of mkdir. Supports recursive creation.
     * For avoidance of umask side-effects chmod is used.
     *
     * @param string $dst       path to be created
     * @param int    $mode      the permission to be set for newly created directories, if not set - 0777 will be used
     * @param bool   $recursive whether to create directory structure recursive if parent dirs do not exist
     *
     * @return bool result of mkdir
     *
     * @see mkdir
     */
    public static function createDirectory($dst, $mode = null, $recursive = false)
    {
        if ($mode === null) {
            $mode = 0777;
        }
        $prevDir = dirname($dst);
        if ($recursive && !is_dir($dst) && !is_dir($prevDir)) {
            self::createDirectory(dirname($dst), $mode, true);
        }
        $res = mkdir($dst, $mode);
        @chmod($dst, $mode);

        return $res;
    }

    private static function getWebRoot()
    {
        $webroot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'MinifyHelperTest';
        if (!is_dir($webroot)) {
            self::createDirectory($webroot, 0755, true);
        }

        return $webroot;
    }

    public function tearDown()
    {
        parent::tearDown();

        foreach ($this->fileHandlesToClose as $handle) {
            fclose($handle);
        }
    }

    private function createFiles($files)
    {
        if (!empty($files)) {
            foreach ($files as $file) {
                if (!empty($file)) {
                    file_put_contents($file, 'MinifyHelperTest');
                }
            }
        }
    }

    private function removeFiles($files)
    {
        if (!empty($files)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    public function canonicalizeUrlDataProvider()
    {
        $data = array();
        $url  = '/path/to/file.php';

        $data[] = array($url, '', 'path/to/file.php');
        $data[] = array($url, '/pathNotFound', 'path/to/file.php');
        $data[] = array($url, '/pathNotFound/', 'path/to/file.php');
        $data[] = array($url, '/pathNotFound/notFound', 'path/to/file.php');
        $data[] = array($url, '/pathNotFound/notFound/', 'path/to/file.php');
        $data[] = array($url, '/path', 'to/file.php');
        $data[] = array($url, '/PaTh', 'to/file.php');
        $data[] = array($url, '/path/', 'to/file.php');
        $data[] = array($url, '/path/TO/', 'file.php');

        return $data;
    }

    /**
     * @dataProvider canonicalizeUrlDataProvider
     */
    public function testCanonicalizeUrl($url, $prefix, $expected)
    {
        $actual = MinifyHelper::canonicalizeUrl($url, $prefix);
        $this->assertEquals($expected, $actual);
    }

    public function findMinifiedFileDataProvider()
    {
        $tmp      = sys_get_temp_dir().DIRECTORY_SEPARATOR;
        $expected = $tmp.'yanli.min.js';
        $this->removeFiles(array($expected));

        $data = array();

        $data[] = array($tmp.'yanli.js', null, null);
        $data[] = array($tmp.'yanli.js', $expected, $expected);
        $data[] = array($expected, $expected, $expected);

        return $data;
    }

    /**
     * @dataProvider findMinifiedFileDataProvider
     */
    public function testFindMinifiedFile($filename, $expected, $createFile)
    {
        $this->createFiles(array($createFile));

        $actual = MinifyHelper::findMinifiedFile($filename);
        $this->assertEquals($expected, $actual);

        $this->removeFiles(array($createFile));
    }

    public function splitUrlDataProvider()
    {
        $data = array();

        $data[] = array('', array('', '', ''));

        $data[] = array('www.google.com/search?q=1234', array('www.google.com', '/search', '?q=1234'));

        $data[] = array('http://www.google.com', array('http://www.google.com', '', ''));
        $data[] = array('http://www.google.com/', array('http://www.google.com', '/', ''));
        $data[] = array('http://www.google.com?q=1234', array('http://www.google.com', '', '?q=1234'));
        $data[] = array('http://www.google.com/?q=1234', array('http://www.google.com', '/', '?q=1234'));
        $data[] = array('http://www.google.com/search?q=1234', array('http://www.google.com', '/search', '?q=1234'));

        $data[] = array('https://www.google.com', array('https://www.google.com', '', ''));
        $data[] = array('https://www.google.com/', array('https://www.google.com', '/', ''));
        $data[] = array('https://www.google.com?q=1234', array('https://www.google.com', '', '?q=1234'));
        $data[] = array('https://www.google.com/?q=1234', array('https://www.google.com', '/', '?q=1234'));
        $data[] = array('https://www.google.com/search?q=1234', array('https://www.google.com', '/search', '?q=1234'));

        $data[] = array('//www.google.com', array('//www.google.com', '', ''));
        $data[] = array('//www.google.com/', array('//www.google.com', '/', ''));
        $data[] = array('//www.google.com?q=1234', array('//www.google.com', '', '?q=1234'));
        $data[] = array('//www.google.com/?q=1234', array('//www.google.com', '/', '?q=1234'));
        $data[] = array('//www.google.com/search?q=1234', array('//www.google.com', '/search', '?q=1234'));

        $data[] = array('/path/search?q=1234', array('', '/path/search', '?q=1234'));
        $data[] = array('path/search?q=1234', array('path', '/search', '?q=1234'));

        return $data;
    }

    /**
     * @dataProvider splitUrlDataProvider
     */
    public function testSplitUrl($url, $expected)
    {
        $actual = MinifyHelper::splitUrl($url);
        $this->assertEquals($expected, $actual);
    }

    public function isExternalUrlDataProvider()
    {
        $data = array();

        $data[] = array('http://www.google.com/search?q=1234', true);
        $data[] = array('https://www.google.com/search?q=1234', true);
        $data[] = array('//www.google.com/search?q=1234', true);
        $data[] = array('www.google.com/search?q=1234', false);
        $data[] = array('', false);

        return $data;
    }

    /**
     * @dataProvider isExternalUrlDataProvider
     */
    public function testIsExternalUrl($url, $expected)
    {
        $actual = MinifyHelper::isExternalUrl($url);
        $this->assertEquals($expected, $actual);
    }

    public function realurlDataProvider()
    {
        $data = array();

        $data[] = array('', '');

        $data[] = array('www.google.com/search?q=1234', true);

        $data[] = array('http://www.google.com', true);
        $data[] = array('http://www.google.com?q=1234', true);
        $data[] = array('http://www.google.com/?q=1234', true);
        $data[] = array('http://www.google.com/search?q=1234', true);

        $data[] = array('https://www.google.com', true);
        $data[] = array('https://www.google.com?q=1234', true);
        $data[] = array('https://www.google.com/?q=1234', true);
        $data[] = array('https://www.google.com/search?q=1234', true);

        $data[] = array('//www.google.com', true);
        $data[] = array('//www.google.com?q=1234', true);
        $data[] = array('//www.google.com/?q=1234', true);
        $data[] = array('//www.google.com/search?q=1234', true);

        $data[] = array('/path/search?q=1234', true);
        $data[] = array('path/search?q=1234', true);

        $data[] = array('http://www.google.com/path1/path2/path3/path4/index.html', 'http://www.google.com/path1/path2/path3/path4/index.html');
        $data[] = array('http:\\\\www.google.com\path1\path2\path3\path4\index.html', 'http://www.google.com/path1/path2/path3/path4/index.html');

        $data[] = array('http://www.google.com/./path1/path2/path3/path4/index.html', 'http://www.google.com/path1/path2/path3/path4/index.html');
        $data[] = array('http://www.google.com/path1/./path2/path3/path4/index.html', 'http://www.google.com/path1/path2/path3/path4/index.html');
        $data[] = array('http://www.google.com/path1/path2/./path3/path4/index.html', 'http://www.google.com/path1/path2/path3/path4/index.html');
        $data[] = array('http://www.google.com/path1/path2/path3/./path4/index.html', 'http://www.google.com/path1/path2/path3/path4/index.html');
        $data[] = array('http://www.google.com/path1/path2/path3/path4/./index.html', 'http://www.google.com/path1/path2/path3/path4/index.html');

        $data[] = array('http://www.google.com/./../path1/path2/path3/path4/index.html', 'http://www.google.com/../path1/path2/path3/path4/index.html');
        $data[] = array('http://www.google.com/path1/.././path2/path3/path4/index.html', 'http://www.google.com/path2/path3/path4/index.html');
        $data[] = array('http://www.google.com/path1/../path2/./path3/path4/index.html', 'http://www.google.com/path2/path3/path4/index.html');
        $data[] = array('http://www.google.com/path1/path2/../../path3/./path4/index.html', 'http://www.google.com/path3/path4/index.html');
        $data[] = array('http://www.google.com/path1/path2/../../../path3/path4/./index.html', 'http://www.google.com/../path3/path4/index.html');

        $data[] = array(self::BASE_URL.'/../image/path0/./path0/../img.png', '/image/path0/img.png');
        $data[] = array(self::BASE_URL.'/../image/path0/./path0/../path1/./path1/../img.png', '/image/path0/path1/img.png');
        $data[] = array(self::BASE_URL.'/../image/path0/./path0/../path1/./path1/../path2/./path2/../path3/./path3/../img.png', '/image/path0/path1/path2/path3/img.png');
        $data[] = array(self::BASE_URL.'/../../image/path0/./path0/../path1/./path1/../path2/./path2/../path3/./path3/../img.png', '/../image/path0/path1/path2/path3/img.png');
        $data[] = array('///////////img.png', '///img.png'); // the first two slashse indicates the procotol "//"

        return $data;
    }

    /**
     * @dataProvider realurlDataProvider
     */
    public function testRealurl($url, $expected)
    {
        if (true === $expected) {
            $expected = $url;
        }

        $actual = MinifyHelper::realurl($url);
        $this->assertEquals($expected, $actual);
    }

    public function testRewriteCssUrl()
    {
        $baseUrl    = self::BASE_URL;
        $url        = $baseUrl.'///////path1/./path_not_useful/../path2/path3/path4/style.css';
        $cssContent = <<<CSS
.class1 { background-image: url(./images/img.png); }
.class2 { background-image: url('.././images/img.png'); } /* remove single quotes */
.class3 { background-image: url("./../images/img.png"); } /* remove double quotes */
.class4 { background-image: url(../../images/img.png); }
.class5 { background-image: url(../../images/../img.png); }
.class6 { background-image: url(../../../../../../images/../img.png); }
.class7 { background-image: url(http://www.google.com/images/../img.png); } /* external url */
.class8 { background-image: url(https://www.google.com/images/../img.png); } /* external url */
.class9 { background-image: url(//www.google.com/images/../img.png); } /* external url */
.class10 { background-image: url(/../images/../img.png); } /* app relative url */
CSS;

        $expected = <<<CSS
.class1 { background-image: url({$baseUrl}/path1/path2/path3/path4/images/img.png); }
.class2 { background-image: url({$baseUrl}/path1/path2/path3/images/img.png); } /* remove single quotes */
.class3 { background-image: url({$baseUrl}/path1/path2/path3/images/img.png); } /* remove double quotes */
.class4 { background-image: url({$baseUrl}/path1/path2/images/img.png); }
.class5 { background-image: url({$baseUrl}/path1/path2/img.png); }
.class6 { background-image: url(/../img.png); }
.class7 { background-image: url(http://www.google.com/images/../img.png); } /* external url */
.class8 { background-image: url(https://www.google.com/images/../img.png); } /* external url */
.class9 { background-image: url(//www.google.com/images/../img.png); } /* external url */
.class10 { background-image: url(/../images/../img.png); } /* app relative url */
CSS;

        $actual = MinifyHelper::rewriteCssUrl($cssContent, $url);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Failed to get contents of 'z:/not_exist.file'.
     */
    public function testConcatInputFileNotExist()
    {
        $files  = array('z:/not_exist.file');
        $saveAs = tempnam(Yii::getPathOfAlias('webroot'), 'cat');
        MinifyHelper::concat($files, $saveAs);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Failed to append the contents of
     */
    public function notWorkingOnLinux_ConcatOutputFileLocked()
    {
        $files = array('current' => __FILE__);

        $saveAs                     = tempnam(Yii::getPathOfAlias('webroot'), 'loc');
        $writer                     = fopen($saveAs, 'w+');
        $this->fileHandlesToClose[] = $writer;

        if (!flock($writer, LOCK_EX | LOCK_NB)) {
            $this->fail('Unable to gain exclusively lock on temporary file: '.$saveAs);

            return;
        }

        MinifyHelper::concat($files, $saveAs);
    }

    public function testConcat()
    {
        $files = array(
            'current' => __FILE__,
            'style'   => self::getWebRoot().DIRECTORY_SEPARATOR.'style.css',
            'script'  => self::getWebRoot().DIRECTORY_SEPARATOR.'script.js',
        );

        $saveAs = tempnam(Yii::getPathOfAlias('webroot'), 'cat');
        MinifyHelper::concat($files, $saveAs, function ($content, $key) {
            return $key;
        });

        $actual   = file_get_contents($saveAs);
        $expected = 'current'.PHP_EOL.'style'.PHP_EOL.'script'.PHP_EOL;
        $this->assertEquals($expected, $actual);
    }

}
