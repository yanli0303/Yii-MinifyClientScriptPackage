<?php

namespace YiiMinifyClientScriptPackage;

class WrappingArrayPrettyPrinter extends \PhpParser\PrettyPrinter\Standard
{
    protected function getIndent(\PhpParser\Node $node)
    {
        $size = $node->getAttribute('indent', 0);
        return is_int($size) && $size > 0 ? str_repeat('    ', $size) : '';
    }

    public function pExpr_Array(\PhpParser\Node\Expr\Array_ $node)
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

    public function pExpr_ArrayItem(\PhpParser\Node\Expr\ArrayItem $node)
    {
        return (null !== $node->key ? $this->getIndent($node).$this->p($node->key).' => ' : '')
                .($node->byRef ? '&' : '').$this->p($node->value);
    }

    protected function updateIndent($statements, $indents)
    {
        if ($statements instanceof \PhpParser\Node) {
            $statements->setAttribute('indent', $indents);

            $childIndent = $statements instanceof \PhpParser\Node\Expr\ArrayItem ? $indents : $indents + 1;
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
