<?php

namespace YiiMinifyClientScriptPackage;

use \PhpParser\Node\Expr\Array_;
use \PhpParser\Node\Expr\ArrayItem;
use \PhpParser\Node\Scalar\String_;
use \PhpParser\Node\Stmt\Return_;

class PhpParserHelper
{

    /**
     * From the top level statements, find the first "return array()" statement.
     * @param array $statements
     * @return \PhpParser\Node\Stmt\Return_ Returns the statement if found, otherwise returns null.
     */
    public static function findFirstReturnArrayStatement(array $statements)
    {
        foreach ($statements as $statement) {
            if ($statement instanceof Return_ && $statement->expr instanceof Array_) {
                return $statement;
            }
        }
    }

    /**
     * Find the array element by key name.
     * @param \PhpParser\Node\Expr\Array_ $arrayStatement
     * @param string $key
     * @return \PhpParser\Node\Scalar\String_
     */
    public static function arrayGetItemByKey(Array_ $arrayStatement, $key)
    {
        foreach ($arrayStatement->items as $item) {
            if ($item instanceof ArrayItem && $item->key instanceof String_ && $key === $item->key->value) {
                return $item;
            }
        }
    }

    /**
     * Find the array element by key name.
     * @param \PhpParser\Node\Expr\Array_ $arrayStatement
     * @param string $key
     * @return \PhpParser\Node\Expr
     */
    public static function arrayGetValueByKey(Array_ $arrayStatement, $key)
    {
        $item = self::arrayGetItemByKey($arrayStatement, $key);
        if ($item) {
            return $item->value;
        }
    }

    public static function arrayGetValues(Array_ $arrayStatement)
    {
        $values = array();

        foreach ($arrayStatement->items as $item) {
            if ($item->value instanceof String_) {
                $values[] = $item->value->value; // the string value
            } else {
                $values[] = $item->value;
            }
        }

        return $values;
    }

    public static function generateArray(array $values)
    {
        $items = array();
        foreach ($values as $value) {
            $v       = is_string($value) ? new String_($value) : $value;
            $items[] = new ArrayItem($v);
        }

        return new Array_($items);
    }

}
