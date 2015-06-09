<?php

namespace YiiMinifyClientScriptPackage;

class YiiConfigTest extends BaseTestCase
{

    public function constructExceptionDataProvider()
    {
        $data = array();

        $data[] = array(null);
        $data[] = array(__DIR__.'/'.microtime());

        return $data;
    }

    /**
     * @dataProvider constructExceptionDataProvider
     * @expectedException \InvalidArgumentException
     */
    public function testConstructExpectsInvalidArgumentException($configFile)
    {
        new YiiConfig($configFile);
    }

    public function constructDataProvider()
    {
        $data = array();

        $phpFileHeader = "<?php\n\n";

        // no return array statement
        $data[] = array('<?php $a = 1;', $phpFileHeader.'$a = 1;');

        // empty return array statement
        $data[] = array('<?php return array();', $phpFileHeader.'return array();');

        // no "packages"
        $data[] = array('<?php return array("components"=>array("clientScript"=>$_GET["abc"]));', $phpFileHeader."return array('components' => array('clientScript' => \$_GET['abc']));");

        return $data;
    }

    /**
     * @dataProvider constructDataProvider
     */
    public function testConstruct($rawCode, $expectedCode)
    {
        $file = tempnam(sys_get_temp_dir(), 'tst');
        if (file_put_contents($file, $rawCode)) {
            self::$filesToRemove[] = $file;
        } else {
            $this->fail('Unable to write file: '.$file);
        }

        $config     = new YiiConfig($file);
        $actualCode = $config->render();
        $this->assertEquals($expectedCode, $actualCode);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage It seems the "clientScript" options are in a separated file, please specify that file as the config file.
     */
    public function testConstructSeparatedClientScript()
    {
        $rawCode = '<?php return array("components"=>array("clientScript"=>require(__DIR__ . "/clientscript.php")));';

        $file = tempnam(sys_get_temp_dir(), 'tst');
        if (file_put_contents($file, $rawCode)) {
            self::$filesToRemove[] = $file;
        } else {
            $this->fail('Unable to write file: '.$file);
        }

        new YiiConfig($file);
    }

    public function setUpForTestMinify()
    {
        $appBasePath         = sys_get_temp_dir().DIRECTORY_SEPARATOR.microtime();
        self::copyDirectory(__DIR__.'/../YiiWebApp', $appBasePath);
        self::$dirsToRemove[] = $appBasePath;

        return $appBasePath;
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Client script package not found: notExistPackage
     */
    public function testMinifyDependPackageNotFound()
    {
        $appBasePath      = $this->setUpForTestMinify();
        $configDir        = $appBasePath.DIRECTORY_SEPARATOR.'protected'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR;
        $clientScriptCode = file_get_contents($configDir.'cs.php');
        $configFile       = $configDir.'csForThisTest.php';
        file_put_contents($configFile, str_replace("array('jquery', 'layout')", "array('jquery', 'layout', 'notExistPackage')", $clientScriptCode));

        $config  = new YiiConfig($configFile);
        $options = new MinifyOptions($appBasePath);
        $config->minifyClientScriptPackages($options);
    }

    public function testMinifyNoPackages()
    {
        $appBasePath = $this->setUpForTestMinify();
        $configDir   = $appBasePath.DIRECTORY_SEPARATOR.'protected'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR;
        $configFile  = $configDir.'csForThisTest.php';
        file_put_contents($configFile, '<?php return array();');

        $config  = new YiiConfig($configFile);
        $options = new MinifyOptions($appBasePath);
        $config->minifyClientScriptPackages($options);
        $this->assertEquals("<?php\n\nreturn array();", $config->render());
    }

    public function testMinify()
    {
        $appBasePath = $this->setUpForTestMinify();
        $configDir   = $appBasePath.DIRECTORY_SEPARATOR.'protected'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR;
        $configFile  = $configDir.'cs.php';

        $config  = new YiiConfig($configFile);
        $options = new MinifyOptions($appBasePath);

        ob_start();
        $config->minifyClientScriptPackages($options);
        ob_end_clean();

        $this->assertContains("array('jquery')", $config->render());
    }

}
