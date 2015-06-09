<?php

namespace YiiMinifyClientScriptPackage;

use \PhpParser\PrettyPrinter\Standard;
use \PhpParser\Node;
use \PhpParser\Node\Expr\Array_;
use \PhpParser\Node\Expr\ArrayItem;

class WrappingArrayPrettyPrinter extends Standard
{

    protected function getIndent(Node $node)
    {
        $size = $node->getAttribute('indent', 0);
        return is_int($size) && $size > 0 ? str_repeat('    ', $size) : '';
    }

    public function pExpr_Array(Array_ $node)
    {
        if (1 < count($node->items)) {
            $indent = $this->getIndent($node);
            return 'array('.PHP_EOL.$this->pCommaSeparated($node->items).PHP_EOL.$indent.')';
        }

        return parent::pExpr_Array($node);
    }

    protected function pCommaSeparated(array $nodes)
    {
        if (1 < count($nodes)) {
            return $this->pImplode($nodes, ','.PHP_EOL);
        }

        return parent::pCommaSeparated($nodes);
    }

    public function pExpr_ArrayItem(ArrayItem $node)
    {
        $siblings = $node->getAttribute('siblings', 0);
        $indent   = is_int($siblings) && $siblings > 1 ? $this->getIndent($node) : '';
        return $indent.parent::pExpr_ArrayItem($node);
    }

    protected function updateIndent($statements, $indents)
    {
        if ($statements instanceof Node) {
            $statements->setAttribute('indent', $indents);

            if ($statements instanceof Array_) {
                $itemsCount = count($statements->items);
                foreach ($statements->items as $item) {
                    $item->setAttribute('siblings', $itemsCount);
                }
            }

            $childIndent = $statements instanceof ArrayItem ? $indents : $indents + 1;
            foreach ($statements->getSubNodeNames() as $name) {
                $this->updateIndent($statements->$name, $childIndent);
            }
        } elseif (is_array($statements)) {
            foreach ($statements as $s) {
                $this->updateIndent($s, $indents);
            }
        }
    }

    public function prettyPrint(array $stmts)
    {
        $this->updateIndent($stmts, -1);
        return parent::prettyPrint($stmts);
    }

}
