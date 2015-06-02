<?php

namespace YiiMinifyClientScriptPackage;

class WrappingArrayPrettyPrinterTest extends \PHPUnit_Framework_TestCase
{

    private function render(array $statements)
    {
        $prettyPrinter = new WrappingArrayPrettyPrinter();
        return $prettyPrinter->prettyPrint($statements);
    }

    public function prettyPrintDataProvider()
    {
        $data = array();

        $newLine = PHP_EOL;
        $php     = '<?php'.$newLine;

        // level 1, empty array
        $data[] = array($php.'return array();', "return array();");

        // level 1, indexed array, 1 element
        $data[] = array($php.'return array(1);', "return array(1);");

        // level 1, indexed array, 2+ element
        $data[] = array($php.'return array(1,2,3);', "return array({$newLine}    1,{$newLine}    2,{$newLine}    3{$newLine});");

        // level 1, key-value array, 1 element
        $data[] = array($php.'return array("a"=>1);', "return array('a' => 1);");

        // level 1, key-value array, 2+ element
        $data[] = array($php.'return array("a"=>1,"b"=>2);', "return array({$newLine}    'a' => 1,{$newLine}    'b' => 2{$newLine});");

        // level 2, empty array
        $data[] = array($php.'return array("child" => array());', "return array('child' => array());");

        // level 2, indexed array, 1 element
        $data[] = array($php.'return array("child" => array(1));', "return array('child' => array(1));");

        // level 2, indexed array, 2+ element
        $data[] = array($php.'return array("child" => array(1,2,3));', "return array('child' => array({$newLine}        1,{$newLine}        2,{$newLine}        3{$newLine}    ));");

        // level 2, key-value array, 1 element
        $data[] = array($php.'return array("child" => array("grandChild"=>3));', "return array('child' => array('grandChild' => 3));");

        // level 2, key-value array, 2+ element
        $oneChildRaw = $php.'return array("child" => array("grandChild1"=>1,"grandChild2"=>2));';
        $oneChildExp = "return array('child' => array({$newLine}        'grandChild1' => 1,{$newLine}        'grandChild2' => 2{$newLine}    ));";
        $data[]      = array($oneChildRaw, $oneChildExp);

        $twoChildrenRaw = $php.'return array("child1" => array("grandChild1"=>1,"grandChild2"=>2),"child2" => array("grandChild3"=>3,"grandChild4"=>4));';
        $twoChildrenExp = "return array({$newLine}    'child1' => array({$newLine}        'grandChild1' => 1,{$newLine}        'grandChild2' => 2{$newLine}    ),{$newLine}    'child2' => array({$newLine}        'grandChild3' => 3,{$newLine}        'grandChild4' => 4{$newLine}    ){$newLine});";
        $data[]         = array($twoChildrenRaw, $twoChildrenExp);

        return $data;
    }

    /**
     * @dataProvider prettyPrintDataProvider
     */
    public function testPrettyPrint($rawPhpCode, $expectedPrettyPhpCode)
    {
        $parser              = new \PhpParser\Parser(new \PhpParser\Lexer\Emulative());
        $statements          = $parser->parse($rawPhpCode);
        $actualPrettyPhpCode = $this->render($statements);

        if($expectedPrettyPhpCode !== $actualPrettyPhpCode) {
            file_put_contents('r:/exp.php', $expectedPrettyPhpCode);
            file_put_contents('r:/act.php', $actualPrettyPhpCode);
        }

        $this->assertEquals($expectedPrettyPhpCode, $actualPrettyPhpCode);
    }

}
