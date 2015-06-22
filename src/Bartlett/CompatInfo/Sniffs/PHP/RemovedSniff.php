<?php

namespace Bartlett\CompatInfo\Sniffs\PHP;

use Bartlett\CompatInfo\Environment;

use Bartlett\Reflect\Sniffer\SniffAbstract;

use PhpParser\Node;

use PDO;

use Doctrine\Common\Collections\ArrayCollection;

class RemovedSniff extends SniffAbstract
{
    private $removed;

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

        $this->removed = array();
    }

    public function leaveSniff()
    {
        parent::leaveSniff();

        if (!empty($this->removed)) {
            // inform analyser that few sniffs were found
            $this->visitor->setMetrics(
                array('Removed' => $this->removed)
            );
        }
    }

    public function leaveNode(Node $node)
    {
        parent::leaveNode($node);

        if ($this->isRemovedFunc($node)) {
            $name = (string) $node->name;

            if (!isset($this->removed[$name])) {
                $versions = $this->collection->get($name);

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

                $this->removed[$name] = array(
                    'version' => $version,
                    'spots'   => array()
                );
            }
            $this->removed[$name]['spots'][] = array(
                'file'    => realpath($this->visitor->getCurrentFile()),
                'line'    => $node->getAttribute('startLine', 0)
            );
        }
    }

    protected function isRemovedFunc($node)
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
            ' WHERE i.ext_name_fk = e.id AND i.php_max <> ""'
        );

        $this->stmtFunctions = $this->dbal->prepare(
            'SELECT f.name,'.
            ' e.name as "ext.name", ext_min as "ext.min", ext_max as "ext.max",' .
            ' php_min as "php.min", php_max as "php.max",' .
            ' parameters, php_excludes as "php.excludes",' .
            ' deprecated' .
            ' FROM bartlett_compatinfo_functions f,  bartlett_compatinfo_extensions e' .
            ' WHERE f.ext_name_fk = e.id AND f.php_max <> ""'
        );
    }
}
