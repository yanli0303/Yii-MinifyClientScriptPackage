<?php

namespace YiiMinifyClientScriptPackage;

use \Symfony\Component\Console\Input\InputOption;

class MinifyCommandTest extends \PHPUnit_Framework_TestCase
{

    private static function tearDownForTestConfigure()
    {
        $tmp                 = sys_get_temp_dir();
        $configDir           = $tmp.DIRECTORY_SEPARATOR.'protected'.DIRECTORY_SEPARATOR.'config';
        $mainPhpFile         = $configDir.DIRECTORY_SEPARATOR.'main.php';
        $clientScriptPhpFile = $configDir.DIRECTORY_SEPARATOR.'clientscript.php';

        if (is_file($mainPhpFile)) {
            unlink($mainPhpFile);
        }

        if (is_file($clientScriptPhpFile)) {
            unlink($clientScriptPhpFile);
        }

        if (is_dir($configDir)) {
            rmdir($configDir);
            rmdir(dirname($configDir));
        }
    }

    private static function setUpForTestConfigure($createDir, $createMainPhpFile, $createClientScriptFile)
    {
        self::tearDownForTestConfigure();

        $tmp                 = sys_get_temp_dir();
        $configDir           = $tmp.DIRECTORY_SEPARATOR.'protected'.DIRECTORY_SEPARATOR.'config';
        $mainPhpFile         = $configDir.DIRECTORY_SEPARATOR.'main.php';
        $clientScriptPhpFile = $configDir.DIRECTORY_SEPARATOR.'clientscript.php';

        chdir($tmp);

        if (!$createDir) {
            return;
        }

        mkdir($configDir, 0777, true);

        if ($createMainPhpFile) {
            file_put_contents($mainPhpFile, 'main.php exists');
        }

        if ($createClientScriptFile) {
            file_put_contents($clientScriptPhpFile, 'clientscript.php exists');
        }
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        self::tearDownForTestConfigure();
    }

    private function assertConfigure(MinifyCommand $cmd, $expectedConfigMode, $expectedAppBasePathDefault, $expectedConfigDefault)
    {
        $this->assertEquals('minify', $cmd->getName());
        $this->assertEquals('Minify client script packages for a Yii web application.', $cmd->getDescription());

        $expected = new InputOption('config', 'c', $expectedConfigMode, 'Path to Yii config file. If you are using a separated file for "clientScript" component, use that file instead.', $expectedConfigDefault);
        $actual   = $cmd->getDefinition()->getOption('config');
        $this->assertTrue($actual->equals($expected));
        $this->assertEquals($expected->getDescription(), $actual->getDescription());

        $expected = new InputOption('appBasePath', 'a', $expectedConfigMode, 'Root path of the Yii web application.', $expectedAppBasePathDefault);
        $actual   = $cmd->getDefinition()->getOption('appBasePath');
        $this->assertTrue($actual->equals($expected));
        $this->assertEquals($expected->getDescription(), $actual->getDescription());

        $expected = new InputOption('rewriteCssUrl', 'r', InputOption::VALUE_OPTIONAL, 'Whether to rewrite "url()" rules after relocating CSS files.', 'true');
        $actual   = $cmd->getDefinition()->getOption('rewriteCssUrl');
        $this->assertTrue($actual->equals($expected));
        $this->assertEquals($expected->getDescription(), $actual->getDescription());

        $expected = new InputOption('minFileSuffix', 'm', InputOption::VALUE_OPTIONAL, 'The file name(without extension) suffix of minified files.', '.min');
        $actual   = $cmd->getDefinition()->getOption('minFileSuffix');
        $this->assertTrue($actual->equals($expected));
        $this->assertEquals($expected->getDescription(), $actual->getDescription());

        $expected = new InputOption('publishDir', 'p', InputOption::VALUE_OPTIONAL, 'The path which is relative to "appBasePath" for publishing minified resources.', 'assets');
        $actual   = $cmd->getDefinition()->getOption('publishDir');
        $this->assertTrue($actual->equals($expected));
        $this->assertEquals($expected->getDescription(), $actual->getDescription());
    }

    public function testConfigureRequireConfigFile()
    {
        $this->setUpForTestConfigure(false, false, false);
        $cmd = new MinifyCommand();
        $this->assertConfigure($cmd, InputOption::VALUE_REQUIRED, null, null);
    }

    public function testConfigureConfigDirFound()
    {
        $this->setUpForTestConfigure(true, false, false);
        $cmd = new MinifyCommand();
        $this->assertConfigure($cmd, InputOption::VALUE_REQUIRED, null, null);
    }

    public function testConfigureMainPhpFound()
    {
        $tmp         = sys_get_temp_dir();
        $configDir   = $tmp.DIRECTORY_SEPARATOR.'protected'.DIRECTORY_SEPARATOR.'config';
        $mainPhpFile = $configDir.DIRECTORY_SEPARATOR.'main.php';

        $this->setUpForTestConfigure(true, true, false);
        $cmd = new MinifyCommand();
        $this->assertConfigure($cmd, InputOption::VALUE_OPTIONAL, sys_get_temp_dir(), $mainPhpFile);
    }

    public function testConfigureClientScriptPhpFound()
    {
        $tmp                 = sys_get_temp_dir();
        $configDir           = $tmp.DIRECTORY_SEPARATOR.'protected'.DIRECTORY_SEPARATOR.'config';
        $clientScriptPhpFile = $configDir.DIRECTORY_SEPARATOR.'clientscript.php';

        $this->setUpForTestConfigure(true, true, true);
        $cmd = new MinifyCommand();
        $this->assertConfigure($cmd, InputOption::VALUE_OPTIONAL, $tmp, $clientScriptPhpFile);
    }

}
