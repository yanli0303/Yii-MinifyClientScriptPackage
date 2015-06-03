<?php

namespace YiiMinifyClientScriptPackage;

class MinifyOptionsTest extends \PHPUnit_Framework_TestCase
{

    public function testDefaults()
    {
        $options = new MinifyOptions();

        $this->assertNull($options->appBasePath);
        $this->assertTrue($options->rewriteCssUrl);
        $this->assertEquals('.min', $options->minFileSuffix);
        $this->assertEquals('assets', $options->publishDir);

        try {
            $options->getPublishDir();
        } catch (\InvalidArgumentException $iae) {
            $this->assertEquals('"appBasePath" should be a string.', $iae->getMessage());
        }

        try {
            $options->appBasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'not_exist_dir';
            $options->getPublishDir();
        } catch (\InvalidArgumentException $iae) {
            $this->assertEquals('Directory not found: '.$options->appBasePath, $iae->getMessage());
        }

        $options->appBasePath = sys_get_temp_dir();
        $this->assertEquals(sys_get_temp_dir().DIRECTORY_SEPARATOR.'assets', $options->getPublishDir());

        $this->assertEquals('../', $options->getCssNewBaseUrl());
    }

    public function exceptionDataProvider()
    {
        $data = array();

        $data[] = array(array('rewriteCssUrl' => 1));
        $data[] = array(array('minFileSuffix' => true));
        $data[] = array(array('publishDir' => true));

        return $data;
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider exceptionDataProvider
     */
    public function testExceptions($attributes)
    {
        $options = new MinifyOptions();
        $options->appBasePath = sys_get_temp_dir();

        foreach ($attributes as $prop => $value) {
            $options->$prop = $value;
        }

        $options->getPublishDir();
    }

}
