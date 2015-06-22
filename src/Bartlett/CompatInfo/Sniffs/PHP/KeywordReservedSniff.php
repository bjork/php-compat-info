<?php

namespace Bartlett\CompatInfo\Sniffs\PHP;

use Bartlett\Reflect\Sniffer\SniffAbstract;

use PhpParser\Node;

class KeywordReservedSniff extends SniffAbstract
{
    private $forbiddenNames;
    private $keywordReserved;

    public function enterSniff()
    {
        parent::enterSniff();

        $this->forbiddenNames = array(
            'abstract' => '5.0',
            'and' => '4.0',
            'array' => '4.0',
            'as' => '4.0',
            'break' => '4.0',
            'callable' => '5.4',
            'case' => '4.0',
            'catch' => '5.0',
            'class' => '4.0',
            'clone' => '5.0',
            'const' => '4.0',
            'continue' => '4.0',
            'declare' => '4.0',
            'default' => '4.0',
            'do' => '4.0',
            'else' => '4.0',
            'elseif' => '4.0',
            'enddeclare' => '4.0',
            'endfor' => '4.0',
            'endforeach' => '4.0',
            'endif' => '4.0',
            'endswitch' => '4.0',
            'endwhile' => '4.0',
            'extends' => '4.0',
            'final' => '5.0',
            'finally' => '5.5',
            'for' => '4.0',
            'foreach' => '4.0',
            'function' => '4.0',
            'global' => '4.0',
            'goto' => '5.3',
            'if' => '4.0',
            'implements' => '5.0',
            'interface' => '5.0',
            'instanceof' => '5.0',
            'insteadof' => '5.4',
            'namespace' => '5.3',
            'new' => '4.0',
            'or' => '4.0',
            'private' => '5.0',
            'protected' => '5.0',
            'public' => '5.0',
            'static' => '4.0',
            'switch' => '4.0',
            'throw' => '5.0',
            'trait' => '5.4',
            'try' => '5.0',
            'use' => '4.0',
            'var' => '4.0',
            'while' => '4.0',
            'xor' => '4.0',
            '__class__' => '4.0',
            '__dir__' => '5.3',
            '__file__' => '4.0',
            '__function__' => '4.0',
            '__method__' => '4.0',
            '__namespace__' => '5.3',
        );

        $this->keywordReserved = array();
    }
    public function leaveSniff()
    {
        parent::leaveSniff();

        if (!empty($this->keywordReserved)) {
            // inform analyser that few sniffs were found
            $this->visitor->setMetrics(
                array('KeywordReserved' => $this->keywordReserved)
            );
        }
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_
            || $node instanceof Node\Stmt\Interface_
            || $node instanceof Node\Stmt\Function_
            || $node instanceof Node\Stmt\ClassMethod
            || $node instanceof Node\Expr\MethodCall
            || $node instanceof Node\Expr\StaticCall
        ) {
            $name = (string) $node->name;
        } elseif ($node instanceof Node\Stmt\Trait_) {
            $name = 'trait';
        } elseif (($node instanceof Node\Expr\ConstFetch || $node instanceof Node\Expr\FuncCall)
            && $node->name instanceof Node\Name
        ) {
            $name = $node->name->toString();
        } elseif ($node instanceof Node\Expr\New_
            || $node instanceof Node\Expr\ClassConstFetch
        ) {
            $name = $node->class->toString();
        } else {
            return;
        }

        $name = strtolower($name);

        if (isset($this->forbiddenNames[$name])) {
            if (!isset($this->keywordReserved[$name])) {
                $this->keywordReserved[$name] = array(
                    'version' => $this->forbiddenNames[$name],
                    'spots'   => array()
                );
            }
            $this->keywordReserved[$name]['spots'][] = array(
                'file'    => realpath($this->visitor->getCurrentFile()),
                'line'    => $node->getAttribute('startLine', 0)
            );
        }
    }
}
