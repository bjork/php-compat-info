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

use Bartlett\CompatInfo\Sniffs;

use Bartlett\Reflect\Analyser\AbstractSniffAnalyser;

/**
 * This analyzer collects different metrics to find out :
 * - keywords reserved
 * - deprecated elements
 * - removed elements
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version  Release: @package_version@
 * @link     http://php5.laurent-laville.org/compatinfo/
 * @since    Class available since Release 4.4.0
 */
class MigrationAnalyser extends AbstractSniffAnalyser
{
    /**
     * Initializes the migration analyser
     */
    public function __construct()
    {
        $this->sniffs = array(
            new Sniffs\PHP\KeywordReservedSniff(),
            new Sniffs\PHP\DeprecatedSniff(),
            new Sniffs\PHP\RemovedSniff(),
            new Sniffs\PHP\ShortOpenTagSniff(),
            new Sniffs\PHP\ShortArraySyntaxSniff(),
            new Sniffs\PHP\ArrayDereferencingSyntaxSniff(),
            new Sniffs\PHP\ClassMemberAccessOnInstantiationSniff(),
            new Sniffs\PHP\ConstSyntaxSniff(),
            new Sniffs\PHP\MagicMethodsSniff(),
            new Sniffs\PHP\AnonymousFunctionSniff(),
            new Sniffs\PHP\NullCoalesceOperatorSniff(),
            new Sniffs\PHP\VariadicFunctionSniff(),
            new Sniffs\PHP\UseConstFunctionSniff(),
        );
    }
}
