<?php

namespace YiiMinifyClientScriptPackage;

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
            if ($statement instanceof \PhpParser\Node\Stmt\Return_ &&
                    $statement->expr instanceof \PhpParser\Node\Expr\Array_) {
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
    public static function arrayGetItemByKey(\PhpParser\Node\Expr\Array_ $arrayStatement, $key)
    {
        foreach ($arrayStatement->items as $item) {
            if ($item instanceof \PhpParser\Node\Expr\ArrayItem && // valid ArrayItem expression
                    $item->key instanceof \PhpParser\Node\Scalar\String_ && // the element key should be a string
                    $key === $item->key->value) {
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
    public static function arrayGetValueByKey(\PhpParser\Node\Expr\Array_ $arrayStatement, $key)
    {
        $item = self::arrayGetItemByKey($arrayStatement, $key);
        if ($item) {
            return $item->value;
        }
    }

    public static function arrayGetValues(\PhpParser\Node\Expr\Array_ $arrayStatement)
    {
        $values = array();

        foreach ($arrayStatement->items as $item) {
            if ($item->value instanceof \PhpParser\Node\Scalar\String_) {
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
            $v       = is_string($value) ? new \PhpParser\Node\Scalar\String_($value) : $value;
            $items[] = new \PhpParser\Node\Expr\ArrayItem($v);
        }

        return new \PhpParser\Node\Expr\Array_($items);
    }

}
