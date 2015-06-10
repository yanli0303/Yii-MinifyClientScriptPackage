<?php

namespace YiiMinifyClientScriptPackage;

use \PhpParser\Parser;
use \PhpParser\Lexer\Emulative;
use \PhpParser\Node\Expr\ArrayItem;
use \PhpParser\Node\Expr\Array_;
use \PhpParser\Node\Expr\BinaryOp\Concat;
use \PhpParser\Node\Scalar\String_;
use \PhpParser\Node\Scalar\MagicConst\Dir;

class YiiClientScriptPackageTest extends BaseTestCase
{

    public function constructDataProvider()
    {
        $data = array();

        // empty
        $data[] = array('"empty" => array()', array(
                'name'       => 'empty',
                'depends'    => array(),
                'basePath'   => null,
                'baseUrl'    => null,
                'css'        => array(),
                'js'         => array(),
                'isExternal' => false
        ));

        // external
        $data[] = array('"external" => array("baseUrl" => "//code.jquery.com")', array(
                'name'       => 'external',
                'depends'    => array(),
                'basePath'   => null,
                'baseUrl'    => '//code.jquery.com',
                'css'        => array(),
                'js'         => array(),
                'isExternal' => true
        ));

        // empty base url
        $data[] = array('"empty base url" => array("baseUrl" => "")', array(
                'name'       => 'empty base url',
                'depends'    => array(),
                'basePath'   => null,
                'baseUrl'    => '',
                'css'        => array(),
                'js'         => array(),
                'isExternal' => false
        ));

        // not literal string url
        $notLiteralStringUrl = <<<PHP
'not literal string url' => array(
    'baseUrl' => 'home/page',
    'css'     => array(__DIR__.'/css/print.css'),
    'js'      => array('js/index.js')
)
PHP;
        $dir                 = new Dir();
        $dir->setLine(3);
        $dir->setAttribute('endLine', 3);
        $right               = new String_('/css/print.css');
        $right->setLine(3);
        $right->setAttribute('endLine', 3);
        $concatUrl           = new Concat($dir, $right);
        $concatUrl->setLine(3);
        $concatUrl->setAttribute('endLine', 3);
        $data[]              = array($notLiteralStringUrl, array(
                'name'       => 'not literal string url',
                'depends'    => array(),
                'basePath'   => null,
                'baseUrl'    => 'home/page',
                'css'        => array(new Concat(new String_('home/page/'), $concatUrl)),
                'js'         => array('home/page/js/index.js'),
                'isExternal' => false
        ));

        return $data;
    }

    private function toArrayItem($code)
    {
        $parser      = new Parser(new Emulative());
        $statements  = $parser->parse("<?php return array($code);");
        $returnArray = PhpParserHelper::findFirstReturnArrayStatement($statements);
        return $returnArray->expr->items[0];
    }

    /**
     * @dataProvider constructDataProvider
     */
    public function testConstruct($code, $expected)
    {
        $arrayItem = $this->toArrayItem($code);

        $package = new YiiClientScriptPackage($arrayItem);
        $actual  = array(
            'name'       => $package->name,
            'depends'    => $package->depends,
            'basePath'   => $package->basePath,
            'baseUrl'    => $package->baseUrl,
            'css'        => $package->css,
            'js'         => $package->js,
            'isExternal' => $package->isExternal
        );
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Can't resolve basePath of package "home". This tool can't resolve basePath, because the path of alias can only be recognized by Yii framework at runtime.
     */
    public function testConstructExpectsException()
    {
        $arrayItem = $this->toArrayItem('"home" => array("basePath" => "sth not null")');
        new YiiClientScriptPackage($arrayItem);
    }

    public function minifyDataProvider()
    {
        $data = array();

        // external
        $data[] = array('"test" => array("baseUrl" => "//code.jquery.com")', array(
                'name'       => 'test',
                'depends'    => array(),
                'basePath'   => null,
                'baseUrl'    => '//code.jquery.com',
                'css'        => array(),
                'js'         => array(),
                'isExternal' => true
            ), '');

        // contains only external files
        $data[] = array('"test" => array("js" => array("//code.jquery.com"))', array(
                'name'       => 'test',
                'depends'    => array(),
                'basePath'   => null,
                'baseUrl'    => null,
                'css'        => array(),
                'js'         => array("//code.jquery.com"),
                'isExternal' => false
            ), 'Package: test'.PHP_EOL);

        // file not found
        $data[] = array('"file not found" => array("js" => array("js/homePage/not_exist.js"))', array(
                'name'       => 'file not found',
                'depends'    => array(),
                'basePath'   => null,
                'baseUrl'    => null,
                'css'        => array(),
                'js'         => array("js/homePage/homePage.js"),
                'isExternal' => false
            ), 'Package: file not found'.PHP_EOL, 'File not found: js/homePage/not_exist.js');

        // minified version was not found
        $data[] = array('"min file not found" => array("js" => array("js/homePage/homePage.js"))', array(
                'name'       => 'min file not found',
                'depends'    => array(),
                'basePath'   => null,
                'baseUrl'    => null,
                'css'        => array(),
                'js'         => array(),
                'isExternal' => false
            ), 'Package: min file not found'.PHP_EOL, 'The minified version was not found for: ', array('js/homePage/homePage.min.js'));

        $code   = <<<PHP
'home' => array(
    'baseUrl' => '',
    'css'     => array('css/layout/pageHeader.css', 'css/layout/pageFooter.css'),
    'js' => array('//code.jquery.com/jquery-1.11.3.min.js', 'js/homePage/homePage.js')
)
PHP;
        $data[] = array($code, array(
                'name'       => 'home',
                'depends'    => array(),
                'basePath'   => null,
                'baseUrl'    => '',
                'css'        => array('assets/home_d7863c9225bcf7aa095c87fc2c0c0b0090f0eadb53d9cb6431161211a1548618.min.css'),
                'js'         => array(
                    '//code.jquery.com/jquery-1.11.3.min.js',
                    'assets/home_8cc592b513cac0e9def4e1ae6c8fc56d03b9769e9664ef769eb8d9b48d57c458.min.js'
                ),
                'isExternal' => false
            ), 'Package: home'.PHP_EOL);

        return $data;
    }

    public function setUpForTestMinify()
    {
        $appBasePath          = sys_get_temp_dir().DIRECTORY_SEPARATOR.microtime();
        self::copyDirectory(__DIR__.'/../YiiWebApp', $appBasePath);
        self::$dirsToRemove[] = $appBasePath;

        return $appBasePath;
    }

    /**
     * @dataProvider minifyDataProvider
     */
    public function testMinify($code, $expected, $expectedOutput, $expectedExceptionMsg = null, $removeFiles = array())
    {
        $arrayItem = $this->toArrayItem($code);
        $package   = new YiiClientScriptPackage($arrayItem);

        $appBasePath = $this->setUpForTestMinify();
        $options     = new MinifyOptions($appBasePath);

        foreach ($removeFiles as $file) {
            $filename = $appBasePath.DIRECTORY_SEPARATOR.$file;
            if (is_file($filename)) {
                unlink($filename);
            }
        }

        if (is_string($expectedExceptionMsg)) {
            $this->setExpectedException('\Exception', $expectedExceptionMsg);
            $package->minify($options);
        } else {
            ob_start();
            $package->minify($options);
            $actualConsoleOutput = ob_get_contents();
            ob_end_clean();
            $this->assertEquals($expectedOutput, $actualConsoleOutput);

            $actual = array(
                'name'       => $package->name,
                'depends'    => $package->depends,
                'basePath'   => $package->basePath,
                'baseUrl'    => $package->baseUrl,
                'css'        => $package->css,
                'js'         => $package->js,
                'isExternal' => $package->isExternal
            );

            $this->assertEquals($expected, $actual);
        }
    }

    public function generateArrayItemDataProvider()
    {
        $data = array();

        $external         = array(
            'name'       => 'external',
            'depends'    => array(),
            'basePath'   => null,
            'baseUrl'    => 'notnull',
            'css'        => array('css/layout/pageHeader.css', 'css/layout/pageFooter.css'),
            'js'         => array('//code.jquery.com/jquery-1.11.3.min.js'),
            'isExternal' => true
        );
        $externalItems    = array(
            new ArrayItem(new String_('notnull'), new String_('baseUrl')),
            new ArrayItem(PhpParserHelper::generateArray(array('css/layout/pageHeader.css', 'css/layout/pageFooter.css')), new String_('css')),
            new ArrayItem(PhpParserHelper::generateArray(array('//code.jquery.com/jquery-1.11.3.min.js')), new String_('js')),
        );
        $externalExpected = new ArrayItem(new Array_($externalItems), new String_('external'));
        $data[]           = array($external, $externalExpected);

        $notExternal         = array(
            'name'       => 'notExternal',
            'depends'    => array('jquery', 'shared'),
            'basePath'   => null,
            'baseUrl'    => 'notempty',
            'css'        => array('css/layout/pageHeader.css', 'css/layout/pageFooter.css'),
            'isExternal' => false
        );
        $notExternalItems    = array(
            new ArrayItem(new String_(''), new String_('baseUrl')),
            new ArrayItem(PhpParserHelper::generateArray(array('jquery', 'shared')), new String_('depends')),
            new ArrayItem(PhpParserHelper::generateArray(array('css/layout/pageHeader.css', 'css/layout/pageFooter.css')), new String_('css')),
        );
        $notExternalExpected = new ArrayItem(new Array_($notExternalItems), new String_('notExternal'));
        $data[]              = array($notExternal, $notExternalExpected);

        return $data;
    }

    /**
     * @dataProvider generateArrayItemDataProvider
     */
    public function testGenerateArrayItem($attributes, $expected)
    {
        $value     = new Array_();
        $key       = new String_('package');
        $arrayItem = new ArrayItem($value, $key);
        $package   = new YiiClientScriptPackage($arrayItem);
        foreach ($attributes as $prop => $value) {
            $package->$prop = $value;
        }

        $actual = $package->generateArrayItem();
        $this->assertEquals($expected, $actual);
    }

}
