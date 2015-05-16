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
 * @since    Class available since Release 4.2.0
 */
class MigrationOutputFormatter extends OutputFormatter
{
    protected $counters = array();

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

        // prints details of deprecated and removed elements (function or constant)
        $this->printBody($output, $response);

        // prints summary
        $this->printFooter($output);
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
        foreach ($response as $group => $elements) {
            if (empty($elements['constants']) && empty($elements['functions'])) {
                $output->writeln(sprintf('%s<warning>No %s elements found</warning>', PHP_EOL, $group));
                continue;
            }
            ksort($elements['constants']);
            ksort($elements['functions']);

            $this->counters[$group] = array(
                'constants' => 0,
                'functions' => 0,
            );

            foreach ($elements as $context => $items) {
                $this->counters[$group][$context] = count($items);

                if ('constants' == $context) {
                    $template = '%sConstant <info>%s</info> is <%s>%s</%s> since <info>%s</info>';
                } else {
                    $template = '%sFunction <info>%s()</info> is <%s>%s</%s> since <info>%s</info>';
                }

                foreach ($items as $element => $values) {
                    $status = ($group == 'deprecated') ? 'warning' : 'error';
                    $output->writeln(
                        sprintf(
                            $template,
                            PHP_EOL,
                            $element,
                            $status,
                            $group,
                            $status,
                            $values['version']
                        )
                    );
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

    /**
     * Prints summary of elements detected
     *
     * @param OutputInterface $output Console Output concrete instance
     *
     * @return void
     */
    protected function printFooter(OutputInterface $output)
    {
        foreach ($this->counters as $group => $counters) {
            $$group = '';

            foreach ($counters as $context => $count) {
                if ($count > 0) {
                    $$group .= sprintf(' and %d %s', $counters[$context], $context);
                }
            }
            $$group .= " $group";
            $$group  = ltrim($$group, ' and ');
        }

        if (!isset($forbidden)) {
            $forbidden = '';
        }
        $forbidden .= ', ';

        if (!isset($deprecated)) {
            $deprecated = '';
        }
        $deprecated .= ', ';

        $results = trim($forbidden . $deprecated, ', ');
        if (empty($results)) {
            $results = 'nothing';
        }

        $output->writeln(
            sprintf(
                '%s<php>Found %s</php>',
                PHP_EOL,
                $results
            )
        );
    }
}
