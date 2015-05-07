<?php
/**
 * Extension Factory.
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version  GIT: $Id$
 * @link     http://php5.laurent-laville.org/compatinfo/
 */

namespace Bartlett\CompatInfo\Reference;

/**
 * Extension factory to build a concrete Reference instance with all releases,
 * independent from the platform.
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version  Release: @package_version@
 * @link     http://php5.laurent-laville.org/compatinfo/
 * @since    Class available since Release 4.0.0-alpha2
 */
class ExtensionFactory implements ReferenceInterface
{
    const LATEST_PHP_5_2 = '5.2.17';
    const LATEST_PHP_5_3 = '5.3.29';
    const LATEST_PHP_5_4 = '5.4.40';
    const LATEST_PHP_5_5 = '5.5.24';
    const LATEST_PHP_5_6 = '5.6.8';

    protected $storage;

    private $name;

    /**
     * Creates a new extension reference
     *
     * @param string $name Name of extension
     */
    public function __construct($name)
    {
        $this->storage = new SqliteStorage($name);
        $this->name    = $name;
    }

    /**
     * Returns name of current extension
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentVersion()
    {
        $persistent = $this->storage->getMetaData('persistent');

        if (empty($persistent)) {
            $version = phpversion($this->name);
        } else {
            // extension is bundled with PHP
            $version = self::getLatestPhpVersion();
        }

        return $version;
    }

    /**
     * {@inheritdoc}
     */
    public function getLatestVersion()
    {
        if (!empty($this->version)) {
            return $this->version;
        }
        return $this->getLatestPhpVersion();
    }

    public static function getLatestPhpVersion()
    {
        if (version_compare(PHP_VERSION, '5.3', 'lt')) {
            return self::LATEST_PHP_5_2;
        }
        if (version_compare(PHP_VERSION, '5.4', 'lt')) {
            return self::LATEST_PHP_5_3;
        }
        if (version_compare(PHP_VERSION, '5.5', 'lt')) {
            return self::LATEST_PHP_5_4;
        }
        if (version_compare(PHP_VERSION, '5.6', 'lt')) {
            return self::LATEST_PHP_5_5;
        }
        return self::LATEST_PHP_5_6;
    }

    /**
     * {@inheritdoc}
     */
    public function getReleases()
    {
        return $this->storage->getMetaData('releases');
    }

    /**
     * {@inheritdoc}
     */
    public function getInterfaces()
    {
        return $this->storage->getMetaData('interfaces');
    }

    /**
     * {@inheritdoc}
     */
    public function getClasses()
    {
        return $this->storage->getMetaData('classes');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return $this->storage->getMetaData('functions');
    }

    /**
     * {@inheritdoc}
     */
    public function getConstants()
    {
        return $this->storage->getMetaData('constants');
    }

    /**
     * {@inheritdoc}
     */
    public function getIniEntries()
    {
        return $this->storage->getMetaData('iniEntries');
    }

    /**
     * {@inheritdoc}
     */
    public function getClassConstants()
    {
        return $this->storage->getMetaData('classConstants');
    }

    /**
     * {@inheritdoc}
     */
    public function getClassStaticMethods()
    {
        return $this->storage->getMetaData('classMethods', true);
    }

    /**
     * {@inheritdoc}
     */
    public function getClassMethods()
    {
        return $this->storage->getMetaData('classMethods', false);
    }
}
