<?php

namespace Bartlett\CompatInfo\Sniffs\PHP;

use Bartlett\Reflect\Sniffer\SniffAbstract;

use PhpParser\Node;

/**
 * Array dereferencing syntax (since PHP 5.4)
 *
 * @link https://github.com/wimg/PHPCompatibility/issues/52
 * @link https://github.com/llaville/php-compat-info/issues/148
 *       Array short syntax and array dereferencing not detected
 * @link http://php.net/manual/en/migration54.new-features.php
 */
class ArrayDereferencingSyntaxSniff extends SniffAbstract
{
    private $arrayDereferencingSyntax;

    public function enterSniff()
    {
        parent::enterSniff();

        $this->arrayDereferencingSyntax = array();
    }

    public function leaveSniff()
    {
        parent::leaveSniff();

        if (!empty($this->arrayDereferencingSyntax)) {
            // inform analyser that few sniffs were found
            $this->visitor->setMetrics(
                array('ArrayDereferencingSyntax' => $this->arrayDereferencingSyntax)
            );
        }
    }

    public function leaveNode(Node $node)
    {
        parent::leaveNode($node);

        if ($node instanceof Node\Expr\ArrayDimFetch
            && $node->var instanceof Node\Expr\FuncCall
        ) {
            $name = '#';

            if (empty($this->arrayDereferencingSyntax)) {
                $this->arrayDereferencingSyntax[$name] = array(
                    'version' => '5.4.0',
                    'spots'   => array()
                );
            }
            $this->arrayDereferencingSyntax[$name]['spots'][] = array(
                'file'    => realpath($this->visitor->getCurrentFile()),
                'line'    => $node->getAttribute('startLine', 0)
            );
        }
    }
}
