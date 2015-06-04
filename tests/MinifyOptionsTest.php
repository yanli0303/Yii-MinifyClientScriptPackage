<?php

namespace YiiMinifyClientScriptPackage;

class MinifyOptionsTest extends \PHPUnit_Framework_TestCase
{

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $dir = sys_get_temp_dir().'/assets/assets1/assets2/assets3';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        $tmp = sys_get_temp_dir();
        rmdir($tmp.'/assets/assets1/assets2/assets3');
        rmdir($tmp.'/assets/assets1/assets2');
        rmdir($tmp.'/assets/assets1');
        rmdir($tmp.'/assets');
    }

    public function constructExceptionDataProvider()
    {
        $data = array();

        $tmp = sys_get_temp_dir();

        // $appBasePath should be a string.
        $data[] = array('$appBasePath should be a string.', null);

        // $appBasePath Directory not found
        $notExistDir = $tmp.DIRECTORY_SEPARATOR.'not exist dir'.uniqid();
        $data[]      = array('Directory not found: '.$notExistDir, $notExistDir);

        // $rewriteCssUrl should be a boolean.
        $data[] = array('$rewriteCssUrl should be a boolean.', $tmp, 1);

        // $minFileSuffix should be a string.
        $data[] = array('$minFileSuffix should be a string.', $tmp, true, true);

        // $publishDir should be a string.
        $data[] = array('$publishDir should be a string.', $tmp, true, '.min', true);

        // $publishDir directory not found
        $notExistDir2 = 'not exist dir'.uniqid();
        $data[]       = array('Directory not found: '.$tmp.DIRECTORY_SEPARATOR.$notExistDir2, $tmp, true, '.min', $notExistDir2);

        return $data;
    }

    /**
     * @dataProvider constructExceptionDataProvider
     */
    public function testConstructExceptions($expectedMessage, $appBasePath, $rewriteCssUrl = true, $minFileSuffix = '.min', $publishDir = 'assets')
    {
        $this->setExpectedException('\InvalidArgumentException', $expectedMessage);
        new MinifyOptions($appBasePath, $rewriteCssUrl, $minFileSuffix, $publishDir);
    }

    public function constructDataProvider()
    {
        $data = array();

        $tmp = sys_get_temp_dir();

        // appBasePath rtrim \/
        $data[] = array(array(
                'getAppBasePath'   => $tmp,
                'getRewriteCssUrl' => true,
                'getMinFileSuffix' => '.min',
                'getPublishDir'    => 'assets',
                'getCssNewBaseUrl' => '../'
            ), $tmp.'/');
        $data[] = array(array(
                'getAppBasePath'   => $tmp,
                'getRewriteCssUrl' => false,
                'getMinFileSuffix' => '.min',
                'getPublishDir'    => 'assets',
                'getCssNewBaseUrl' => '../'
            ), $tmp.'\\', false);
        $data[] = array(array(
                'getAppBasePath'   => $tmp,
                'getRewriteCssUrl' => false,
                'getMinFileSuffix' => '.mini',
                'getPublishDir'    => 'assets',
                'getCssNewBaseUrl' => '../'
            ), $tmp.'/\\', false, '.mini');

        // publishDir trim \/
        $data[] = array(array(
                'getAppBasePath'   => $tmp,
                'getRewriteCssUrl' => false,
                'getMinFileSuffix' => '.mini',
                'getPublishDir'    => 'assets',
                'getCssNewBaseUrl' => '../'
            ), $tmp, false, '.mini', '//assets/');
        $data[] = array(array(
                'getAppBasePath'   => $tmp,
                'getRewriteCssUrl' => false,
                'getMinFileSuffix' => '.mini',
                'getPublishDir'    => 'assets',
                'getCssNewBaseUrl' => '../'
            ), $tmp, false, '.mini', '\\assets\\\\');
        $data[] = array(array(
                'getAppBasePath'   => $tmp,
                'getRewriteCssUrl' => false,
                'getMinFileSuffix' => '.mini',
                'getPublishDir'    => 'assets',
                'getCssNewBaseUrl' => '../'
            ), $tmp, false, '.mini', '/\\/assets//\\');

        // publishDir multiple levels
        $data[] = array(array(
                'getAppBasePath'   => $tmp,
                'getRewriteCssUrl' => false,
                'getMinFileSuffix' => '.mini',
                'getPublishDir'    => 'assets/assets1',
                'getCssNewBaseUrl' => '../../'
            ), $tmp, false, '.mini', '/\\assets/assets1\\');
        $data[] = array(array(
                'getAppBasePath'   => $tmp,
                'getRewriteCssUrl' => false,
                'getMinFileSuffix' => '.mini',
                'getPublishDir'    => 'assets/assets1/assets2',
                'getCssNewBaseUrl' => '../../../'
            ), $tmp, false, '.mini', '/\\assets\\assets1/assets2/');
        $data[] = array(array(
                'getAppBasePath'   => $tmp,
                'getRewriteCssUrl' => false,
                'getMinFileSuffix' => '.mini',
                'getPublishDir'    => 'assets/assets1/assets2/assets3',
                'getCssNewBaseUrl' => '../../../../'
            ), $tmp, false, '.mini', '/\\assets/assets1\\assets2/assets3\\');

        return $data;
    }

    /**
     * @dataProvider constructDataProvider
     */
    public function testConstruct($expected, $appBasePath, $rewriteCssUrl = true, $minFileSuffix = '.min', $publishDir = 'assets')
    {
        $options = new MinifyOptions($appBasePath, $rewriteCssUrl, $minFileSuffix, $publishDir);
        foreach ($expected as $fn => $expectedValue) {
            $this->assertEquals($expectedValue, $options->$fn());
        }
    }

}
