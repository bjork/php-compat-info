<?php

namespace Bartlett\CompatInfo\Sniffs\PHP;

use Bartlett\Reflect\Sniffer\SniffAbstract;

use PhpParser\Node;

/**
 * Class::{expr}() syntax since PHP 5.4
 *
 * @link http://php.net/manual/en/migration54.new-features.php
 * @link https://github.com/wimg/PHPCompatibility/issues/54
 */
class ClassExprSyntaxSniff extends SniffAbstract
{
    private $classExprSyntax;

    public function enterSniff()
    {
        parent::enterSniff();

        $this->classExprSyntax = array();
   }

    public function leaveSniff()
    {
        parent::leaveSniff();

        if (!empty($this->classExprSyntax)) {
            // inform analyser that few sniffs were found
            $this->visitor->setMetrics(
                array('ClassExprSyntax' => $this->classExprSyntax)
            );
        }
    }

    public function leaveNode(Node $node)
    {
        parent::leaveNode($node);

        if ($this->isClassExprSyntax($node)) {
            $name = '#';

            if (empty($this->classExprSyntax)) {
                $this->classExprSyntax[$name] = array(
                    'version' => '5.4.0',
                    'spots'   => array()
                );
            }
            $this->classExprSyntax[$name]['spots'][] = $this->getCurrentSpot($node);
        }
    }

    protected function isClassExprSyntax($node)
    {
        return ($node instanceof Node\Expr\StaticCall
            && $node->class instanceof Node\Name
            && $node->name instanceof Node\Scalar\String_
        );
    }
}
