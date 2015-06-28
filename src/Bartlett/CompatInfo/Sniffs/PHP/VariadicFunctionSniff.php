<?php

namespace Bartlett\CompatInfo\Sniffs\PHP;

use Bartlett\Reflect\Sniffer\SniffAbstract;

use PhpParser\Node;

/**
 * Variadic functions are PHP 5.6 or greater
 *
 * @link https://github.com/llaville/php-compat-info/issues/141
 * @link http://php.net/manual/en/migration56.new-features.php#migration56.new-features.variadics
 */
class VariadicFunctionSniff extends SniffAbstract
{
    private $variadicFunction;

    public function enterSniff()
    {
        parent::enterSniff();

        $this->variadicFunction = array();
    }

    public function leaveSniff()
    {
        parent::leaveSniff();

        if (!empty($this->variadicFunction)) {
            // inform analyser that few sniffs were found
            $this->visitor->setMetrics(
                array('VariadicFunction' => $this->variadicFunction)
            );
        }
    }

    public function enterNode(Node $node)
    {
        parent::enterNode($node);

        if ($this->isVariadicFunction($node)) {
            $name = '#';

            if (empty($this->variadicFunction)) {
                $this->variadicFunction[$name] = array(
                    'version' => '5.6.0',
                    'spots'   => array()
                );
            }

            $this->variadicFunction[$name]['spots'][] = array(
                'file'    => realpath($this->visitor->getCurrentFile()),
                'line'    => $node->getAttribute('startLine', 0)
            );
        }
    }

    protected function isVariadicFunction($node)
    {
        if ($node instanceof Node\Stmt\Function_
            || $node instanceof Node\Expr\Closure
            || $node instanceof Node\Stmt\ClassMethod
        ) {
            foreach ($node->params as $param) {
                if ($param->variadic) {
                    return true;
                }
            }
        }
        return false;
    }
}
