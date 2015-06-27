<?php

namespace Bartlett\CompatInfo\Sniffs\PHP;

use Bartlett\CompatInfo\Environment;

use Bartlett\Reflect\Sniffer\SniffAbstract;

use PhpParser\Node;

use PDO;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Deprecated elements such as
 * - functions
 * - ini entries
 * - assignment by reference on object construction
 *
 * @link https://github.com/wimg/PHPCompatibility/issues/68 Assignment by reference on object construction
 */
class DeprecatedSniff extends SniffAbstract
{
    private $deprecatedFunctions;
    private $deprecatedDirectives;
    private $deprecatedAssignRefs;

    // database abstraction layer
    private $dbal;

    private $stmtIniEntries;
    private $stmtFunctions;

    private $collection;

    public function setUpBeforeSniff()
    {
        parent::setUpBeforeSniff();

        /**
         * Initializes CompatInfo DB
         */
        $this->dbal = Environment::initRefDb();
        $this->dbal->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->doInitialize();

        $references = array();

        foreach (array('iniEntries', 'functions') as $group) {
            $stmt = 'stmt' . ucfirst($group);
            $this->$stmt->execute();

            while ($row = $this->$stmt->fetch(PDO::FETCH_ASSOC)) {
                $name = $row['name'];
                unset($row['name']);
                $references[$name] = $row;
            }
        }

        $this->collection = new ArrayCollection($references);

        $this->deprecatedFunctions  = array();
        $this->deprecatedDirectives = array();
        $this->deprecatedAssignRefs = array();
    }

    public function leaveSniff()
    {
        parent::leaveSniff();

        if (!empty($this->deprecatedFunctions)) {
            // inform analyser that few sniffs were found
            $this->visitor->setMetrics(
                array('DeprecatedFunctions' => $this->deprecatedFunctions)
            );
        }
        if (!empty($this->deprecatedDirectives)) {
            // inform analyser that few sniffs were found
            $this->visitor->setMetrics(
                array('DeprecatedDirectives' => $this->deprecatedDirectives)
            );
        }
        if (!empty($this->deprecatedAssignRefs)) {
            // inform analyser that few sniffs were found
            $this->visitor->setMetrics(
                array('DeprecatedAssignRefs' => $this->deprecatedAssignRefs)
            );
        }
    }

    public function leaveNode(Node $node)
    {
        parent::leaveNode($node);

        if ($this->isDeprecatedFunc($node)) {
            $name = (string) $node->name;

            if (!isset($this->deprecatedFunctions[$name])) {
                $versions = $this->collection->get($name);

                $this->deprecatedFunctions[$name] = array(
                    'version' => $versions['deprecated'],
                    'spots'   => array()
                );
            }
            $this->deprecatedFunctions[$name]['spots'][] = array(
                'file'    => realpath($this->visitor->getCurrentFile()),
                'line'    => $node->getAttribute('startLine', 0)
            );

        } elseif ($this->isDeprecatedDirective($node)) {
            $name = strtolower($node->args[0]->value->value);

            if (!isset($this->deprecatedDirectives[$name])) {
                $versions = $this->collection->get($name);

                $this->deprecatedDirectives[$name] = array(
                    'version' => $versions['deprecated'],
                    'spots'   => array()
                );
            }
            $this->deprecatedDirectives[$name]['spots'][] = array(
                'file'    => realpath($this->visitor->getCurrentFile()),
                'line'    => $node->getAttribute('startLine', 0)
            );

        } elseif ($this->isDeprecatedAssignRef($node)) {
            $name = 'new';

            if (!isset($this->deprecatedAssignRefs[$name])) {
                $versions = $this->collection->get($name);

                $this->deprecatedAssignRefs[$name] = array(
                    'version' => '5.3.0',
                    'spots'   => array()
                );
            }
            $this->deprecatedAssignRefs[$name]['spots'][] = array(
                'file'    => realpath($this->visitor->getCurrentFile()),
                'line'    => $node->getAttribute('startLine', 0)
            );
        }
    }

    protected function isDeprecatedFunc($node)
    {
        return ($node instanceof Node\Expr\FuncCall
            && $node->name instanceof Node\Name
            && $this->collection->containsKey((string) $node->name)
        );
    }

    protected function isDeprecatedDirective($node)
    {
        return ($node instanceof Node\Expr\FuncCall
            && $node->name instanceof Node\Name
            && in_array(strtolower((string) $node->name), array('ini_set', 'ini_get'))
            && $node->args[0]->value instanceof Node\Scalar\String_
            && $this->collection->containsKey(strtolower($node->args[0]->value->value))
        );
    }

    protected function isDeprecatedAssignRef($node)
    {
        return ($node instanceof Node\Expr\AssignRef
            && $node->expr instanceof Node\Expr\New_
        );
    }

    /**
     * Initializes DB statements
     *
     * @return void
     */
    protected function doInitialize()
    {
        $this->stmtIniEntries = $this->dbal->prepare(
            'SELECT i.name,' .
            ' ext_min as "ext.min", ext_max as "ext.max",' .
            ' php_min as "php.min", php_max as "php.max",' .
            ' deprecated' .
            ' FROM bartlett_compatinfo_inientries i,  bartlett_compatinfo_extensions e' .
            ' WHERE i.ext_name_fk = e.id AND i.deprecated <> ""'
        );

        $this->stmtFunctions = $this->dbal->prepare(
            'SELECT f.name,'.
            ' e.name as "ext.name", ext_min as "ext.min", ext_max as "ext.max",' .
            ' php_min as "php.min", php_max as "php.max",' .
            ' parameters, php_excludes as "php.excludes",' .
            ' deprecated' .
            ' FROM bartlett_compatinfo_functions f,  bartlett_compatinfo_extensions e' .
            ' WHERE f.ext_name_fk = e.id AND f.deprecated <> ""'
        );
    }
}
