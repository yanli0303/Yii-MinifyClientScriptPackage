<?php

namespace YiiMinifyClientScriptPackage;

class MinifyHelperTest extends \PHPUnit_Framework_TestCase
{

    private function createFiles($files)
    {
        if (!empty($files)) {
            foreach ($files as $file) {
                if (!empty($file) && !is_file($file)) {
                    file_put_contents($file, 'MinifyHelperTest');
                }
            }
        }
    }

    public function testJoinNumericArray()
    {
        $actual   = array(1, 3, 5, 7, 9);
        MinifyHelper::joinNumericArray($actual, array(2, 4, 6, 8, 10));
        $expected = array(1, 3, 5, 7, 9, 2, 4, 6, 8, 10);

        $this->assertEquals($expected, $actual);
    }

    public function testNormalizePath()
    {
        $nonDirSeparator = DIRECTORY_SEPARATOR === '/' ? '\\' : '/';
        $dirSeparator    = DIRECTORY_SEPARATOR;
        $testData        = "{$dirSeparator}a{$nonDirSeparator}b{$nonDirSeparator}{$nonDirSeparator}c{$dirSeparator}";

        $expected = "{$dirSeparator}a{$dirSeparator}b{$dirSeparator}{$dirSeparator}c{$dirSeparator}";
        $actual   = MinifyHelper::normalizePath($testData);
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

    public function splitUrlDataProvider()
    {
        $data = array();

        $data[] = array('', array('', '', ''));

        $data[] = array('www.google.com/search?q=1234', array('', 'www.google.com/search', '?q=1234'));

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
        $data[] = array('path/search?q=1234', array('', 'path/search', '?q=1234'));

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

        $data[] = array('/MinifyTest/../image/path0/./path0/../img.png', '/image/path0/img.png');
        $data[] = array('/MinifyTest/../image/path0/./path0/../path1/./path1/../img.png', '/image/path0/path1/img.png');
        $data[] = array('/MinifyTest/../image/path0/./path0/../path1/./path1/../path2/./path2/../path3/./path3/../img.png', '/image/path0/path1/path2/path3/img.png');
        $data[] = array('/MinifyTest/../../image/path0/./path0/../path1/./path1/../path2/./path2/../path3/./path3/../img.png', '/../image/path0/path1/path2/path3/img.png');
        $data[] = array('///////////img.png', '///img.png');

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

    public function rewriteCssUrlDataProvider()
    {
        $data = array();

        $cssContent = <<<CSS
.class1 { background-image: url(./images/img.png); }
.class2 { background-image: url('.././images/img.png'); } /* remove single quotes */
.class3 { background-image: url("./../images/img.png"); } /* remove double quotes */
.class4 { background-image: url(../../images/img.png); }
.class5 { background-image: url(../../images/../img.png); }
.class6 { background-image: url(../../../../../../images/../img.png); }
.class7 { background-image: url(http://www.google.com/images/../img.png); } /* external url */
.class8 { background-image: url(https://www.google.com/images/../img.png); } /* external url */
.class82 { background-image: url(data:image/jpeg;abcde); } /* external url */
.class9 { background-image: url(//www.google.com/images/../img.png); } /* external url */
.class10 { background-image: url(/../images/../img.png); } /* app relative url */
CSS;

        $fileUrl = '///////path1/./path_not_useful/../path2/path3/path4/style.css';

        $expected1 = <<<CSS
.class1 { background-image: url(/MinifyTestpath1/path2/path3/path4/images/img.png); }
.class2 { background-image: url(/MinifyTestpath1/path2/path3/images/img.png); } /* remove single quotes */
.class3 { background-image: url(/MinifyTestpath1/path2/path3/images/img.png); } /* remove double quotes */
.class4 { background-image: url(/MinifyTestpath1/path2/images/img.png); }
.class5 { background-image: url(/MinifyTestpath1/path2/img.png); }
.class6 { background-image: url(/MinifyTest../../img.png); }
.class7 { background-image: url(http://www.google.com/images/../img.png); } /* external url */
.class8 { background-image: url(https://www.google.com/images/../img.png); } /* external url */
.class82 { background-image: url(data:image/jpeg;abcde); } /* external url */
.class9 { background-image: url(//www.google.com/images/../img.png); } /* external url */
.class10 { background-image: url(/../images/../img.png); } /* app relative url */
CSS;

        $expected2 = <<<CSS
.class1 { background-image: url(path1/path2/path3/path4/images/img.png); }
.class2 { background-image: url(path1/path2/path3/images/img.png); } /* remove single quotes */
.class3 { background-image: url(path1/path2/path3/images/img.png); } /* remove double quotes */
.class4 { background-image: url(path1/path2/images/img.png); }
.class5 { background-image: url(path1/path2/img.png); }
.class6 { background-image: url(../../img.png); }
.class7 { background-image: url(http://www.google.com/images/../img.png); } /* external url */
.class8 { background-image: url(https://www.google.com/images/../img.png); } /* external url */
.class82 { background-image: url(data:image/jpeg;abcde); } /* external url */
.class9 { background-image: url(//www.google.com/images/../img.png); } /* external url */
.class10 { background-image: url(/../images/../img.png); } /* app relative url */
CSS;

        $data[] = array($cssContent, $fileUrl, '/MinifyTest', $expected1);

        $data[] = array($cssContent, $fileUrl, null, $expected2);

        return $data;
    }

    /**
     * @dataProvider rewriteCssUrlDataProvider
     */
    public function testRewriteCssUrl($cssContent, $fileUrl, $baseUrl, $expected)
    {
        $actual = MinifyHelper::rewriteCssUrl($cssContent, $fileUrl, $baseUrl);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Failed to get contents of 'z:/not_exist.file'.
     */
    public function testConcatInputFileNotExist()
    {
        $files  = array('z:/not_exist.file');
        $saveAs = tempnam(sys_get_temp_dir(), 'cat');
        MinifyHelper::concat($files, $saveAs);
    }

    public function testConcat()
    {
        $tmp   = sys_get_temp_dir();
        $files = array(
            'current' => __FILE__,
            'style'   => $tmp.DIRECTORY_SEPARATOR.'style.css',
            'script'  => $tmp.DIRECTORY_SEPARATOR.'script.js'
        );
        $this->createFiles($files);

        $saveAs = tempnam(sys_get_temp_dir(), 'cat');
        MinifyHelper::concat($files, $saveAs, function($content, $key) {
            return $key;
        });

        $actual   = file_get_contents($saveAs);
        $expected = 'current'.PHP_EOL.'style'.PHP_EOL.'script'.PHP_EOL;
        $this->assertEquals($expected, $actual);
    }

    public function findMinifiedFileDataProvider()
    {
        $tmp      = sys_get_temp_dir().DIRECTORY_SEPARATOR;
        $expected = $tmp.'yanli.min.js';
        if (is_file($expected)) {
            unlink($expected);
        }

        $data = array();

        $data[] = array($tmp.'yanli.js', NULL, NULL);
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

        $actual = MinifyHelper::findMinifiedFile($filename, '.min');
        $this->assertEquals($expected, $actual);

        if (is_file($createFile)) {
            unlink($createFile);
        }
    }

}
