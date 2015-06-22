<?php

namespace Bartlett\CompatInfo\Sniffs\PHP;

use Bartlett\Reflect\Sniffer\SniffAbstract;

use PhpParser\Node;

/**
 * Use of CONST keyword outside of a class (since PHP 5.3)
 *
 * @link https://github.com/wimg/PHPCompatibility/issues/50
 */
class ConstSyntaxSniff extends SniffAbstract
{
    private $constSyntax;

    public function enterSniff()
    {
        parent::enterSniff();
        $this->constSyntax = array();
    }

    public function leaveSniff()
    {
        parent::leaveSniff();

        if (!empty($this->constSyntax)) {
            // inform analyser that few sniffs were found
            $this->visitor->setMetrics(
                array('ConstSyntax' => $this->constSyntax)
            );
        }
    }

    public function enterNode(Node $node)
    {
        parent::enterNode($node);

        if ($node instanceof Node\Stmt\Const_) {
            if ($this->visitor->inContext('object')) {
                return;
            }

            $name = '#';

            if (empty($this->constSyntax)) {
                $this->constSyntax[$name] = array(
                    'version' => '5.3.0',
                    'spots'   => array()
                );
            }
            $this->constSyntax[$name]['spots'][] = array(
                'file'    => realpath($this->visitor->getCurrentFile()),
                'line'    => $node->getAttribute('startLine', 0)
            );
        }
    }
}
