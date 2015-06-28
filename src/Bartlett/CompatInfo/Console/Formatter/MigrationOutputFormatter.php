<?php
/**
 * Migration Analyser formatter class for console output.
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version  GIT: $Id$
 * @link     http://php5.laurent-laville.org/compatinfo/
 */

namespace Bartlett\CompatInfo\Console\Formatter;

use Bartlett\Reflect\Console\Formatter\OutputFormatter;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Migration Analyser formatter class for console output.
 *
 * @category PHP
 * @package  PHP_CompatInfo
 * @author   Laurent Laville <pear@laurent-laville.org>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version  Release: @package_version@
 * @link     http://php5.laurent-laville.org/compatinfo/
 * @since    Class available since Release 4.4.0
 */
class MigrationOutputFormatter extends OutputFormatter
{
    /**
     * Migration Analyser console output format
     *
     * @param OutputInterface $output   Console Output concrete instance
     * @param array           $response Analyser Metrics
     *
     * @return void
     */
    public function __invoke(OutputInterface $output, $response)
    {
        $this->printHeader($output);

        // prints details of each elements found
        $this->printBody($output, $response);
    }

    /**
     * Prints header of report
     *
     * @param OutputInterface $output Console Output concrete instance
     *
     * @return void
     */
    protected function printHeader(OutputInterface $output)
    {
        $output->writeln(
            sprintf('%s<info>Migration Analysis</info>%s', PHP_EOL, PHP_EOL)
        );
    }

    /**
     * Prints details of report
     *
     * @param OutputInterface $output   Console Output concrete instance
     * @param array           $response Analyser Metrics
     *
     * @return void
     */
    protected function printBody(OutputInterface $output, $response)
    {
        $genericTemplate = '%s%s is <%s>%s</%s> since <info>%s</info>';

        $templates = array(
            'DeprecatedFunctions'
                => '%sFunction <info>%s()</info> is <%s>%s</%s> since <info>%s</info>',
            'DeprecatedDirectives'
                => '%sIni Entry <info>%s</info> is <%s>%s</%s> since <info>%s</info>',
            'MagicMethods'
                => '%sMagic Method <info>%s()</info> is <%s>%s</%s> since <info>%s</info>',
        );

        foreach ($response as $group => $elements) {

            $output->writeln(
                sprintf('%s<info>Report of %s elements</info>%s', PHP_EOL, $group, PHP_EOL)
            );

            foreach ($elements as $element => $values) {
                if (version_compare(PHP_VERSION, $values['version'], 'ge')) {
                    $status = 'info';
                } else {
                    $status = 'error';
                }

                if ('KeywordReserved' == $group) {
                    $status = 'error';
                    $label  = 'reserved';
                    $element = sprintf('Keyword <info>%s</info>', $element);

                } elseif (in_array($group, array('DeprecatedFunctions', 'DeprecatedDirectives', 'DeprecatedAssignRefs'))) {
                    $status = 'warning';
                    $label  = 'deprecated';
                    if ('new' == $element) {
                        $element = 'Assignment by reference for object construction';
                    }

                } elseif ('Removed' == $group) {
                    $status = 'error';
                    $label  = 'forbidden';
                    $element = sprintf('Function <info>%s()</info>', $element);

                } elseif ('ShortOpenTag' == $group) {
                    if (version_compare(PHP_VERSION, $values['version'], 'ge')) {
                        $status = 'info';
                    } else {
                        $status = 'warning';
                    }
                    $label   = 'always available';
                    $element = 'Short open tag syntax';

                } elseif ('ShortArraySyntax' == $group) {
                    $label   = 'allowed';
                    $element = 'Short array syntax';

                } elseif ('ArrayDereferencingSyntax' == $group) {
                    $label   = 'allowed';
                    $element = 'Array dereferencing syntax';

                } elseif ('ClassMemberAccessOnInstantiation' == $group) {
                    $label   = 'allowed';
                    $element = 'Class member access on instantiation syntax';

                } elseif ('ConstSyntax' == $group) {
                    $label = 'allowed';
                    if ('#' == $element) {
                        $element = 'Use of CONST keyword outside of a class';
                    } elseif ('const-scalar-exprs' == $element) {
                        $element = 'Constant scalar expressions';
                    }

                } elseif ('MagicMethods' == $group) {
                    $label = 'available';

                } elseif('AnonymousFunction' == $group) {
                    $label = 'allowed';

                    if ('this' == $element) {
                        $element = 'Use of $this inside a closure';
                    } elseif (in_array($element, array('self', 'static'))) {
                        $element = sprintf('Use of %s inside a closure', $element);
                    } else {
                        $element = 'Anonymous function';
                    }

                } elseif ('NullCoalesceOperator' == $group) {
                    $label   = 'allowed';
                    $element = 'Null Coalesce Operator';

                } elseif ('VariadicFunction' == $group) {
                    $label   = 'allowed';
                    $element = 'Variadic Function';

                } elseif ('UseConstFunction' == $group) {
                    $label   = 'allowed';
                    $element = 'Use const or use function syntax';

                } else {
                    $label = 'allowed';
                    $element = $group;
                }

                $output->writeln(
                    sprintf(
                        isset($templates[$group]) ? $templates[$group] : $genericTemplate,
                        PHP_EOL,
                        $element,
                        $status,
                        $label,
                        $status,
                        $values['version']
                    )
                );
                if ($output->isVerbose()) {
                    // prints each use location
                    $output->writeln(str_repeat('-', 79));
                    foreach ($values['spots'] as $spot) {
                        $output->writeln(
                            sprintf(
                                "%5d | %s",
                                $spot['line'],
                                $spot['file']
                            )
                        );
                    }
                    $output->writeln(str_repeat('-', 79));
                }
            }
        }
    }
}
