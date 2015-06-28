<?php

namespace Bartlett\CompatInfo\Sniffs\PHP;

use Bartlett\Reflect\Sniffer\SniffAbstract;

use PhpParser\Node;

/**
 * Use of CONST keyword outside of a class (since PHP 5.3)
 * Constant scalar expressions are PHP 5.6 or greater
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

        if (!$node instanceof Node\Stmt\Const_) {
            return;
        }

        if (!$this->visitor->inContext('object')) {
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

        if ($this->isConstantScalarExpression($node)) {
            $name = 'const-scalar-exprs';

            if (!isset($this->constSyntax[$name])) {
                $this->constSyntax[$name] = array(
                    'version' => '5.6.0',
                    'spots'   => array()
                );
            }
            $this->constSyntax[$name]['spots'][] = array(
                'file'    => realpath($this->visitor->getCurrentFile()),
                'line'    => $node->getAttribute('startLine', 0)
            );
        }
    }

    /**
     *
     * @link https://github.com/llaville/php-compat-info/issues/140
     * @link http://php.net/manual/en/migration56.new-features.php#migration56.new-features.const-scalar-exprs
     */
    protected function isConstantScalarExpression($node)
    {
        foreach ($node->consts as $const) {
            if (!$const->value instanceof Node\Scalar) {
                return true;
            }
        }
        return false;
    }
}
