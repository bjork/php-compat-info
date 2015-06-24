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
        $templates = array(
            'KeywordReserved'
                => '%sKeyword <info>%s</info> is <%s>%s</%s> since <info>%s</info>',
            'Deprecated'
                => '%sFunction <info>%s()</info> is <%s>%s</%s> since <info>%s</info>',
            'Removed'
                => '%sFunction <info>%s()</info> is <%s>%s</%s> since <info>%s</info>',
            'ShortOpenTag'
                => '%s<info>%s</info> syntax is <%s>%s</%s> since <info>%s</info>',
            'ShortArraySyntax'
                => '%s<info>%s</info> syntax is <%s>%s</%s> since <info>%s</info>',
            'ArrayDereferencingSyntax'
                => '%s<info>%s</info> syntax is <%s>%s</%s> since <info>%s</info>',
            'ClassMemberAccessOnInstantiation'
                => '%s<info>%s</info> syntax is <%s>%s</%s> since <info>%s</info>',
            'ConstSyntax'
                => '%s<info>%s</info> is <%s>%s</%s> since <info>%s</info>',
            'MagicMethods'
                => '%sMagic Method <info>%s</info> is <%s>%s</%s> since <info>%s</info>',
            'AnonymousFunction'
                => '%s<info>%s</info> is <%s>%s</%s> since <info>%s</info>',
        );

        foreach ($response as $group => $elements) {

            $output->writeln(
                sprintf('%s<info>Report of %s elements</info>%s', PHP_EOL, $group, PHP_EOL)
            );

            foreach ($elements as $element => $values) {
                if ('KeywordReserved' == $group) {
                    $status = 'error';
                    $label  = 'reserved';

                } elseif ('Deprecated' == $group) {
                    $status = 'warning';
                    $label  = 'deprecated';

                } elseif ('Removed' == $group) {
                    $status = 'error';
                    $label  = 'forbidden';

                } elseif ('ShortOpenTag' == $group) {
                    if (version_compare(PHP_VERSION, $values['version'], 'ge')) {
                        $status = 'info';
                    } else {
                        $status = 'warning';
                    }
                    $label   = 'always available';
                    $element = 'Short open tag';

                } elseif ('ShortArraySyntax' == $group) {
                    if (version_compare(PHP_VERSION, $values['version'], 'ge')) {
                        $status = 'info';
                    } else {
                        $status = 'error';
                    }
                    $label   = 'allowed';
                    $element = 'Short array';

                } elseif ('ArrayDereferencingSyntax' == $group) {
                    if (version_compare(PHP_VERSION, $values['version'], 'ge')) {
                        $status = 'info';
                    } else {
                        $status = 'error';
                    }
                    $label   = 'allowed';
                    $element = 'Array dereferencing';

                } elseif ('ClassMemberAccessOnInstantiation' == $group) {
                    if (version_compare(PHP_VERSION, $values['version'], 'ge')) {
                        $status = 'info';
                    } else {
                        $status = 'error';
                    }
                    $label   = 'allowed';
                    $element = 'Class member access on instantiation';

                } elseif ('ConstSyntax' == $group) {
                    if (version_compare(PHP_VERSION, $values['version'], 'ge')) {
                        $status = 'info';
                    } else {
                        $status = 'error';
                    }
                    $label   = 'allowed';
                    $element = 'Use of CONST keyword outside of a class';

                } elseif ('MagicMethods' == $group) {
                    if (version_compare(PHP_VERSION, $values['version'], 'ge')) {
                        $status = 'info';
                    } else {
                        $status = 'error';
                    }
                    $label = 'available';

                } elseif('AnonymousFunction' == $group) {
                    if (version_compare(PHP_VERSION, $values['version'], 'ge')) {
                        $status = 'info';
                    } else {
                        $status = 'error';
                    }
                    $label = 'allowed';

                    if ('this' == $element) {
                        $element = 'Use of $this inside a closure';
                    } elseif (in_array($element, array('self', 'static'))) {
                        $element = sprintf('Use of %s inside a closure', $element);
                    } else {
                        $element = 'Anonymous function';
                    }

                } else {
                    $status = 'error';
                    $label  = $group;
                }

                $output->writeln(
                    sprintf(
                        $templates[$group],
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
