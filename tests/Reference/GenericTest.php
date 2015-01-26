<?php
/**
 * Unit tests for PHP_CompatInfo, Generic extension base class.
 *
 * PHP version 5
 *
 * @category   PHP
 * @package    PHP_CompatInfo
 * @subpackage Tests
 * @author     Laurent Laville <pear@laurent-laville.org>
 * @author     Remi Collet <Remi@FamilleCollet.com>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    GIT: $Id$
 * @link       http://php5.laurent-laville.org/compatinfo/
 * @since      Class available since Release 3.0.0RC1
 */

namespace Bartlett\Tests\CompatInfo\Reference;

use Bartlett\CompatInfo\Reference\ReferenceInterface;

/**
 * Tests for the PHP_CompatInfo, retrieving components informations
 * about any extension.
 *
 * @category   PHP
 * @package    PHP_CompatInfo
 * @subpackage Tests
 * @author     Laurent Laville <pear@laurent-laville.org>
 * @author     Remi Collet <Remi@FamilleCollet.com>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://php5.laurent-laville.org/compatinfo/
 */
class GenericTest extends \PHPUnit_Framework_TestCase
{
    protected static $obj = null;
    protected static $ref = null;
    protected static $ext = null;

    // Could be defined in Reference but missing (system dependant)
    protected static $optionalcfgs        = array();
    protected static $optionalconstants   = array();
    protected static $optionalfunctions   = array();
    protected static $optionalclasses     = array();
    protected static $optionalinterfaces  = array();

    // Could be present but missing in Reference (alias, ...)
    protected static $ignoredcfgs          = array();
    protected static $ignoredconstants     = array();
    protected static $ignoredfunctions     = array();
    protected static $ignoredclasses       = array();
    protected static $ignoredinterfaces    = array();

    protected static $extensions =  array('amqp','haru','pthreads');

    /**
     * Sets up the shared fixture.
     *
     * @return void
     * @link   http://phpunit.de/manual/current/en/fixtures.html#fixtures.sharing-fixture
     */
    public static function setUpBeforeClass()
    {
        if (!self::$obj instanceof ReferenceInterface) {
            self::$obj = null;
            return;
        }
        self::$ext = $extname = self::$obj->getName();

        if (!extension_loaded($extname)) {
            // if dynamic extension load is activated
            $loaded = (bool) ini_get('enable_dl');
            if ($loaded) {
                // give a second chance
                $prefix = (PHP_SHLIB_SUFFIX === 'dll') ? 'php_' : '';
                @dl($prefix . $extname . '.' . PHP_SHLIB_SUFFIX);
            }
        }
        if (!extension_loaded($extname)) {
            self::$obj = null;
        }
    }

    /**
     * Test that a reference exists and provides releases
     * @group  reference
     * @return void
     */
    public function testReference()
    {
        if (is_null(self::$obj)) {
            $this->markTestSkipped(
                "The '" . self::$ext . "' extension is not available."
            );
        }
        $this->assertTrue(true);
    }

    /**
     * Test than all referenced ini entries exists
     *
     * @depends testReference
     * @group  reference
     * @return void
     */
    public function testGetIniEntriesFromReference()
    {
        if (is_null(self::$obj)) {
            return;
        }
        $inientries = self::$obj->getIniEntries();
        $this->assertTrue(is_array($inientries));
        foreach ($inientries as $inientry => $range) {
            $min = $range['php.min'];
            $max = $range['php.max'];

            if (array_key_exists('php.excludes', $range)) {
                if (!is_array($range['php.excludes'])) {
                    $range['php.excludes'] = array($range['php.excludes']);
                }
                if (in_array(PHP_VERSION, $range['php.excludes'])) {
                    // We are in min/max, so add it as optional
                    array_push(self::$optionalcfgs, $inientry);
                }
            }
            if (!in_array($inientry, self::$optionalcfgs)
                && (empty($min) || version_compare(PHP_VERSION, $min)>=0)
                && (empty($max) || version_compare(PHP_VERSION, $max)<=0)
            ) {
                // Should be there except if set as optional
                $this->assertNotSame(
                    ini_get($inientry),
                    false,
                    "INI '$inientry', found in Reference, does not exists."
                );
            }
            if (!in_array($inientry, self::$ignoredcfgs)) {
                if (($min && version_compare(PHP_VERSION, $min)<0)
                    || ($max && version_compare(PHP_VERSION, $max)>0)
                ) {
                    // Should not be there except if ignored
                    $this->assertFalse(
                        ini_get($inientry),
                        "INI '$inientry', found in Reference ($min,$max), exists."
                    );
                }
            }
        }
    }

    /**
     * Test that each ini entries are defined in reference
     *
     * @depends testReference
     * @group  reference
     * @return void
     */
    public function _testGetIniEntriesFromExtension()
    {
        $extname = self::$ext;

        if ('internal' == $extname) {
            // only Core is a valid extension name for API reflection
            return;
        }
        $dict       = self::$obj->getIniEntries();
        $extension  = new \ReflectionExtension($extname);
        $iniEntries = array_keys($extension->getINIEntries());
        $this->assertTrue(is_array($dict));

        foreach ($iniEntries as $iniEntry) {
            if (!in_array($iniEntry, self::$ignoredcfgs)) {
                $this->assertArrayHasKey(
                    $iniEntry,
                    $dict,
                    "Defined INI '$iniEntry' not known in Reference."
                );
            }
        }
    }

    /**
     * Test than all referenced functions exists
     *
     * @depends testReference
     * @group  reference
     * @return void
     */
    public function testGetFunctionsFromReference()
    {
        if (is_null(self::$obj)) {
            return;
        }
        $fcts = self::$obj->getFunctions();
        $this->assertTrue(is_array($fcts));
        foreach ($fcts as $fctname => $range) {
            $min = $range['php.min'];
            $max = $range['php.max'];

            if (array_key_exists('php.excludes', $range)) {
                if (!is_array($range['php.excludes'])) {
                    $range['php.excludes'] = array($range['php.excludes']);
                }
                if (in_array(PHP_VERSION, $range['php.excludes'])) {
                    // We are in min/max, so add it as optional
                    array_push(self::$optionalfunctions, $fctname);
                }
            }
            if (!in_array($fctname, self::$optionalfunctions)
                && (empty($min) || version_compare(PHP_VERSION, $min)>=0)
                && (empty($max) || version_compare(PHP_VERSION, $max)<=0)
            ) {
                // Should be there except if set as optional
                $this->assertTrue(
                    function_exists($fctname),
                    "Function '$fctname', found in Reference, does not exists."
                );
            }
            if (!in_array($fctname, self::$ignoredfunctions)) {
                if (($min && version_compare(PHP_VERSION, $min)<0)
                    || ($max && version_compare(PHP_VERSION, $max)>0)
                ) {
                    // Should not be there except if ignored
                    $this->assertFalse(
                        function_exists($fctname),
                        "Function '$fctname', found in Reference ($min,$max), exists."
                    );
                }
            }
        }
    }

    /**
     * Test that each functions are defined in reference
     *
     * @depends testReference
     * @group  reference
     * @return void
     */
    public function _testGetFunctionsFromExtension()
    {
        $ext = get_extension_funcs(self::$ext);
        if (!is_array($ext)) {
            // can be NULL for ext without function
            $ext = array();
        }
        $dict = self::$obj->getFunctions();
        $this->assertTrue(is_array($dict));

        foreach ($ext as $fctname) {
            if (!in_array($fctname, self::$ignoredfunctions)) {
                $this->assertArrayHasKey(
                    $fctname,
                    $dict,
                    "Defined function '$fctname' not known in Reference."
                );
            }
        }
    }

    /**
     * Test than all referenced constants exists
     *
     * @depends testReference
     * @group  reference
     * @return void
     */
    public function testGetConstantsFromReference()
    {
        if (is_null(self::$obj)) {
            return;
        }
        $dict = self::$obj->getConstants();
        $this->assertTrue(is_array($dict));
        foreach ($dict as $constname => $range) {
            $min = $range['php.min'];
            $max = $range['php.max'];

            if (array_key_exists('php.excludes', $range)) {
                if (!is_array($range['php.excludes'])) {
                    $range['php.excludes'] = array($range['php.excludes']);
                }
                if (in_array(PHP_VERSION, $range['php.excludes'])) {
                    // We are in min/max, so add it as optional
                    array_push(self::$optionalconstants, $constname);
                }
            }
            if (!in_array($constname, self::$optionalconstants)
                && (empty($min) || version_compare(PHP_VERSION, $min)>=0)
                && (empty($max) || version_compare(PHP_VERSION, $max)<=0)
            ) {
                $this->assertTrue(
                    defined($constname),
                    "Constant '$constname', found in Reference, does not exists."
                );
            }
            if (!in_array($constname, self::$ignoredconstants)) {
                if (($min && version_compare(PHP_VERSION, $min)<0)
                    || ($max && version_compare(PHP_VERSION, $max)>0)
                ) {
                    $this->assertFalse(
                        defined($constname),
                        "Constant '$constname', found in Reference ($min,$max), exists."
                    );
                }
            }
        }
    }

    /**
     * Test that each constants are defined in reference
     *
     * @depends testReference
     * @group  reference
     * @return void
     */
    public function _testGetConstantsFromExtension()
    {
        $extname = self::$ext;
        $const   = get_defined_constants(true);
        $dict    = self::$obj->getConstants();
        $this->assertTrue(is_array($dict));

        if (defined('__PHPUNIT_PHAR__')) {
            // remove '' . "\0" . '__COMPILER_HALT_OFFSET__' . "\0" . __PHPUNIT_PHAR__
            array_pop($const['Core']);
        }

        if (isset($const[$extname])) {
            // Test if each constants are in reference
            foreach ($const[$extname] as $constname => $value) {
                if (!in_array($constname, self::$ignoredconstants)) {
                    $this->assertArrayHasKey(
                        $constname,
                        $dict,
                        "Defined constant '$constname' not known in Reference."
                    );
                }
            }
        }
    }

    /**
     * Test than all referenced classes exists
     *
     * @depends testReference
     * @group  reference
     * @return void
     */
    public function testGetClassesFromReference()
    {
        if (is_null(self::$obj)) {
            return;
        }
        $dict = self::$obj->getClasses();
        $this->assertTrue(is_array($dict));
        foreach ($dict as $classname => $range) {
            $min = $range['php.min'];
            $max = $range['php.max'];

            if (array_key_exists('php.excludes', $range)) {
                if (!is_array($range['php.excludes'])) {
                    $range['php.excludes'] = array($range['php.excludes']);
                }
                if (in_array(PHP_VERSION, $range['php.excludes'])) {
                    // We are in min/max, so add it as optional
                    array_push(self::$ignoredclasses, $constname);
                }
            }
            if (!in_array($classname, self::$optionalclasses)
                && (empty($min) || version_compare(PHP_VERSION, $min)>=0)
                && (empty($max) || version_compare(PHP_VERSION, $max)<=0)
            ) {
                $this->assertTrue(
                    class_exists($classname, false),
                    "Class '$classname', found in Reference, does not exists."
                );
            }
            if (!in_array($classname, self::$ignoredclasses)) {
                if (($min && version_compare(PHP_VERSION, $min)<0)
                    || ($max && version_compare(PHP_VERSION, $max)>0)
                ) {
                    $this->assertFalse(
                        class_exists($classname, false),
                        "Class '$classname', found in Reference ($min,$max), exists."
                    );
                }
            }
        }
    }

    /**
     * Test that each classes are defined in reference
     *
     * @depends testReference
     * @group  reference
     * @return void
     */
    public function _testGetClassesFromExtension()
    {
        $extname = self::$ext;

        if ('internal' == $extname) {
            // only Core is a valid extension name for API reflection
            return;
        }
        $dict1     = self::$obj->getClasses();
        $dict2     = self::$obj->getInterfaces();
        $extension = new \ReflectionExtension($extname);
        $classes   = $extension->getClassNames();
        $this->assertTrue(is_array($classes));

        foreach ($classes as $classname) {
            if (class_exists($classname)) {
                if (!in_array($classname, self::$ignoredclasses)) {
                    $this->assertArrayHasKey(
                        $classname,
                        $dict1,
                        "Defined class '$classname' not known in Reference."
                    );
                }
            } else {
                if (!in_array($classname, self::$ignoredinterfaces)) {
                    $this->assertArrayHasKey(
                        $classname,
                        $dict2,
                        "Defined interface '$classname' not known in Reference."
                    );
                }
            }
        }
    }

    /**
     * Test that each class methods are defined in reference
     *
     * @depends testReference
     * @group  reference
     * @return void
     */
    public function _testGetClassMethodsFromExtension()
    {
        if (!in_array(self::$ext, self::$extensions)) {
            $this->assertFalse(false);
            return;
        }

        $extname   = self::$ext;
        $extension = new \ReflectionExtension($extname);
        $classes   = array_unique($extension->getClassNames());
        $this->assertTrue(is_array($classes));

        $nonStaticMethods = self::$obj->getClassMethods();
        $staticMethods    = self::$obj->getClassStaticMethods();

        foreach ($classes as $classname) {
            $class   = new \ReflectionClass($classname);
            if ($class->getName() != $classname) {
                /* Skip class alias */
                continue;
            }
            $methods = $class->getMethods();

            foreach ($methods as $method) {
                if (!$method->isPublic()) {
                    continue;
                }
                $methodname = $method->getName();
                if ($method->isStatic()) {
                    $this->assertArrayHasKey(
                        $classname,
                        $staticMethods,
                        "Defined static method '$classname::$methodname' not known in Reference."
                    );
                    $this->assertArrayHasKey(
                        $methodname,
                        $staticMethods[$classname],
                        "Defined static method '$classname::$methodname' not known in Reference."
                    );
                } else {
                    $this->assertArrayHasKey(
                        $methodname,
                        $nonStaticMethods[$classname],
                        "Defined method '$classname::$methodname' not known in Reference."
                    );
                }
            }
        }
    }

    /**
     * Test that each class constants are defined in reference
     *
     * @depends testReference
     * @group  reference
     * @return void
     */
    public function _testGetClassConstantsFromExtension()
    {
        if (!in_array(self::$ext, self::$extensions)) {
            $this->assertFalse(false);
            return;
        }

        $extname   = self::$ext;
        $extension = new \ReflectionExtension($extname);
        $classes   = array_unique($extension->getClassNames());
        $this->assertTrue(is_array($classes));

        $classconstants = self::$obj->getClassConstants();

        foreach ($classes as $classname) {
            $class   = new \ReflectionClass($classname);
            if ($class->getName() != $classname) {
                /* Skip class alias */
                continue;
            }
            $constants = $class->getConstants();

            foreach ($constants as $constantname => $constantvalue) {
                $this->assertArrayHasKey(
                    $constantname,
                    $classconstants[$classname],
                    "Defined class constant '$classname::$constantname' not known in Reference."
                );
            }
        }
    }

    /**
     * Test than all referenced interfaces exists
     *
     * @depends testReference
     * @group  reference
     * @return void
     */
    public function testGetInterfacesFromReference()
    {
        if (is_null(self::$obj)) {
            return;
        }
        $dict = self::$obj->getInterfaces();
        $this->assertTrue(is_array($dict));
        foreach ($dict as $intname => $range) {
            $min = $range['php.min'];
            $max = $range['php.max'];

            if (array_key_exists('php.excludes', $range)) {
                if (!is_array($range['php.excludes'])) {
                    $range['php.excludes'] = array($range['php.excludes']);
                }
                if (in_array(PHP_VERSION, $range['php.excludes'])) {
                    // We are in min/max, so add it as optional
                    array_push(self::$optionalinterfaces, $intname);
                }
            }
            if (!in_array($intname, self::$optionalinterfaces)
                && (empty($min) || version_compare(PHP_VERSION, $min)>=0)
                && (empty($max) || version_compare(PHP_VERSION, $max)<=0)
            ) {
                $this->assertTrue(
                    interface_exists($intname, false),
                    "Interface '$intname', found in Reference, does not exists."
                );
            }
        }
    }
}
