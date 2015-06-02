<?php

namespace YiiMinifyClientScriptPackage;

class PhpParserHelperTest extends \PHPUnit_Framework_TestCase
{

    public function testFindFirstReturnArrayStatement()
    {
        $parser = new \PhpParser\Parser(new \PhpParser\Lexer\Emulative());
        $this->assertNull(PhpParserHelper::findFirstReturnArrayStatement($parser->parse('<?php $a = 1;')));
        $this->assertInstanceOf('\PhpParser\Node\Stmt\Return_', PhpParserHelper::findFirstReturnArrayStatement($parser->parse('<?php return array(1,2,3);')));
    }

    public function testArrayGetItemByKey()
    {
        $parser         = new \PhpParser\Parser(new \PhpParser\Lexer\Emulative());
        $phpCode        = '<?php return array("a" => 1);';
        $statements     = $parser->parse($phpCode);
        $arrayStatement = $statements[0]->expr;

        $this->assertNull(PhpParserHelper::arrayGetItemByKey($arrayStatement, 'b'));
        $this->assertInstanceOf('\PhpParser\Node\Expr\ArrayItem', PhpParserHelper::arrayGetItemByKey($arrayStatement, 'a'));
    }

    public function testArrayGetValueByKey()
    {
        $parser         = new \PhpParser\Parser(new \PhpParser\Lexer\Emulative());
        $phpCode        = '<?php return array("a" => 1);';
        $statements     = $parser->parse($phpCode);
        $arrayStatement = $statements[0]->expr;

        $this->assertNull(PhpParserHelper::arrayGetValueByKey($arrayStatement, 'b'));

        $lnumber = PhpParserHelper::arrayGetValueByKey($arrayStatement, 'a');
        $this->assertInstanceOf('\PhpParser\Node\Scalar\LNumber', $lnumber);
        $this->assertEquals(1, $lnumber->value);
    }

    public function testArrayGetValues()
    {
        $parser = new \PhpParser\Parser(new \PhpParser\Lexer\Emulative());

        $phpCode        = '<?php return array("a" => "b", "b" => "a");';
        $statements     = $parser->parse($phpCode);
        $arrayStatement = $statements[0]->expr;
        $actual         = PhpParserHelper::arrayGetValues($arrayStatement);
        $this->assertEquals(array('b', 'a'), $actual);

        $phpCode        = '<?php return array(2, 1);';
        $statements     = $parser->parse($phpCode);
        $arrayStatement = $statements[0]->expr;
        $actual         = PhpParserHelper::arrayGetValues($arrayStatement);

        $this->assertEquals(2, count($actual));
        $this->assertInstanceOf('\PhpParser\Node\Scalar\LNumber', $actual[0]);
        $this->assertEquals(2, $actual[0]->value);

        $this->assertInstanceOf('\PhpParser\Node\Scalar\LNumber', $actual[1]);
        $this->assertEquals(1, $actual[1]->value);
    }

    public function testGenerateArray()
    {
        $expected = array('a', '1', 'b', '2', 'c', '3', 'd', '4');
        $actual   = PhpParserHelper::generateArray($expected);

        $this->assertInstanceOf('\PhpParser\Node\Expr\Array_', $actual);
        $this->assertEquals(count($expected), count($actual->items));

        foreach ($expected as $index => $expectedValue) {
            $actualItem = $actual->items[$index];
            $this->assertInstanceOf('\PhpParser\Node\Expr\ArrayItem', $actualItem);

            $actualItemValue = $actualItem->value;
            $this->assertInstanceOf('\PhpParser\Node\Scalar\String_', $actualItemValue);

            $this->assertEquals($expectedValue, $actualItemValue->value);
        }
    }

}
