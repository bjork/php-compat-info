<?php
/**
 * Migration Analyser
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version  GIT: $Id$
 * @link     http://php5.laurent-laville.org/compatinfo/
 */

namespace Bartlett\CompatInfo\Analyser;

use Bartlett\CompatInfo\Environment;
use Bartlett\CompatInfo\Collection\ReferenceCollection;

use Bartlett\Reflect\Analyser\AbstractAnalyser;

use PhpParser\Node;

/**
 * This analyzer collects different metrics to find out deprecated or removed elements.
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version  Release: @package_version@
 * @link     http://php5.laurent-laville.org/compatinfo/
 * @since    Class available since Release 4.2.0
 */
class MigrationAnalyser extends AbstractAnalyser
{
    private $references;

    /**
     * Initializes the migration analyser
     */
    public function __construct()
    {
        $pdo = Environment::initRefDb();

        $this->metrics = array(
            'forbidden'  => array(
                'constants' => array(),
                'functions' => array(),
            ),
            'deprecated' => array(
                'constants' => array(),
                'functions' => array(),
            ),
        );

        $this->references = new ReferenceCollection(array(), $pdo);
    }

    /**
     * Called when leaving a node.
     *
     * @param Node $node Node
     *
     * @return null|Node|false|Node[] Node
     */
    public function leaveNode(Node $node)
    {
        parent::leaveNode($node);

        if ($node instanceof Node\Expr\FuncCall) {
            $this->checkElement($node, 'functions');
        }
    }

    /**
     * Checks if an element is deprecated or removed.
     *
     * @param Node $node
     *
     * @return void
     */
    protected function checkElement(Node $node, $context)
    {
        if (!$node->name instanceof Node\Name) {
            return;
        }

        $element = (string) $node->name;

        if (isset($this->metrics['forbidden'][$context][$element])) {
            $group = 'forbidden';

        } elseif (isset($this->metrics['deprecated'][$context][$element])) {
            $group = 'deprecated';

        } else {
            $versions = $this->references->find($context, $element);

            if (!empty($versions['php.max'])) {
                $group = 'forbidden';

                if (version_compare($versions['php.max'], '5.3', 'lt')) {
                    $version = '5.3.0';

                } elseif (version_compare($versions['php.max'], '5.4', 'lt')) {
                    $version = '5.4.0';

                } elseif (version_compare($versions['php.max'], '5.5', 'lt')) {
                    $version = '5.5.0';

                } elseif (version_compare($versions['php.max'], '5.6', 'lt')) {
                    $version = '5.6.0';

                } elseif (version_compare($versions['php.max'], '7.0', 'lt')) {
                    $version = '7.0.0';
                }

            } elseif (!empty($versions['deprecated'])) {
                $version = $versions['deprecated'];
                $group = 'deprecated';

            } else {
                return;
            }

            $this->metrics[$group][$context][$element] = array(
                'version' => $version,
                'spots'   => array()
            );
        }

        $this->metrics[$group][$context][$element]['spots'][] = array(
            'file'    => realpath($this->file),
            'line'    => $node->getAttribute('startLine', 0)
        );
    }
}
