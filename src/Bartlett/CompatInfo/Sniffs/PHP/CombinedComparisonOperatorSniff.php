<?php

namespace Bartlett\CompatInfo\Sniffs\PHP;

use Bartlett\Reflect\Sniffer\SniffAbstract;

use PhpParser\Node;

/**
 * Combined Comparison (Spaceship) Operator since PHP 7.0.0 alpha1
 *
 * @link https://wiki.php.net/rfc/combined-comparison-operator
 */
class CombinedComparisonOperatorSniff extends SniffAbstract
{
    private $combinedComparisonOperator;

    public function enterSniff()
    {
        parent::enterSniff();

        $this->combinedComparisonOperator = array();
    }

    public function leaveSniff()
    {
        parent::leaveSniff();

        if (!empty($this->combinedComparisonOperator)) {
            // inform analyser that few sniffs were found
            $this->visitor->setMetrics(
                array('CombinedComparisonOperator' => $this->combinedComparisonOperator)
            );
        }
    }

    public function enterNode(Node $node)
    {
        parent::enterNode($node);

        if ($node instanceof Node\Expr\BinaryOp\Spaceship) {
            $name = '#';

            if (empty($this->combinedComparisonOperator)) {
                $this->combinedComparisonOperator[$name] = array(
                    'version' => '7.0.0alpha1',
                    'spots'   => array()
                );
            }

            $this->combinedComparisonOperator[$name]['spots'][] = $this->getCurrentSpot($node);
        }
    }
}
