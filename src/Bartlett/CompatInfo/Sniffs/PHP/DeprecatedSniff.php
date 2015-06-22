<?php

namespace Bartlett\CompatInfo\Sniffs\PHP;

use Bartlett\CompatInfo\Environment;

use Bartlett\Reflect\Sniffer\SniffAbstract;

use PhpParser\Node;

use PDO;

use Doctrine\Common\Collections\ArrayCollection;

class DeprecatedSniff extends SniffAbstract
{
    private $deprecated;

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

        $this->deprecated = array();
    }

    public function leaveSniff()
    {
        parent::leaveSniff();

        if (!empty($this->deprecated)) {
            // inform analyser that few sniffs were found
            $this->visitor->setMetrics(
                array('Deprecated' => $this->deprecated)
            );
        }
    }

    public function leaveNode(Node $node)
    {
        parent::leaveNode($node);

        if ($this->isDeprecatedFunc($node)) {
            $name = (string) $node->name;

            if (!isset($this->deprecated[$name])) {
                $versions = $this->collection->get($name);

                $this->deprecated[$name] = array(
                    'version' => $versions['deprecated'],
                    'spots'   => array()
                );
            }
            $this->deprecated[$name]['spots'][] = array(
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
