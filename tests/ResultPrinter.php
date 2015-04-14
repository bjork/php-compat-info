<?php

namespace Bartlett\Tests\CompatInfo;

use Bartlett\LoggerTestListenerTrait;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;

/**
 * Helper to detect phpunit switches, due to lack of implementation in custom printer classes
 * @see https://github.com/sebastianbergmann/phpunit/issues/1674
 */
trait GetOpt
{
    public function isVerbose()
    {
        list ($opts, $non_opts) = \PHPUnit_Util_Getopt::getopt(
            $_SERVER['argv'],
            'd:c:hv'
        );
        $key = array_search('--verbose', $non_opts);
        if ($key === false) {
            foreach ($opts as $opt) {
                $key = array_search('v', $opt);
                if (is_int($key)) {
                    return true;
                }
            }
            return false;
        }
        return is_int($key);
    }

    public function isDebug()
    {
        list ($opts, $non_opts) = \PHPUnit_Util_Getopt::getopt(
            $_SERVER['argv'],
            'd:c:hv'
        );
        $key = array_search('--debug', $non_opts);
        return is_int($key);
    }
}

class ResultPrinter extends \PHPUnit_Util_Printer implements \PHPUnit_Framework_TestListener
{
    use LoggerTestListenerTrait, LoggerAwareTrait, GetOpt;

    /**
     * {@inheritDoc}
     */
    public function __construct($out = null)
    {
        parent::__construct($out);

        if ($this->isDebug()) {
            $level = LogLevel::DEBUG;
        } elseif ($this->isVerbose()) {
            $level = LogLevel::INFO;
        } else {
            $level = LogLevel::NOTICE;
        }

        $console = new \Psr3ConsoleLogger('PHPUnitPrinterLogger', $level);
        $console->pushProcessor(
            function (array $record) {
                if (!array_key_exists('operation', $record['context'])) {
                    return $record;
                }
                $context = $record['context'];

                if ('startTestSuite' == $context['operation']) {
                    $suiteName = $context['suiteName'];
                    if (strpos($suiteName, 'ExtensionTest::') > 0) {
                        list ($className, $methodName) = explode('::', $suiteName);
                        $parts = explode('\\', $className);

                        $suiteName = sprintf('%s::%s', end($parts), $methodName);

                        $record['message'] = "TestSuite '$suiteName'";
                    }

                } elseif ('endTestSuite' == $context['operation']) {
                    $suiteName = $context['suiteName'];
                    if (strpos($suiteName, 'ExtensionTest::') > 0) {
                        $resultMessage  = sprintf('    %s. ', ($context['errorCount'] + $context['failureCount'] ? 'KO' : 'OK'));
                        $resultMessage .= "Tests: " . $context['testCount'] . ", ";
                        $resultMessage .= "Assertions: " . $context['assertionCount'];

                        if ($context['failureCount'] > 0) {
                            $resultMessage .= ", Failures: " . $context['failureCount'];
                        }

                        if ($context['errorCount'] > 0) {
                            $resultMessage .= ", Errors: " . $context['errorCount'];
                        }

                        if ($context['incompleteCount'] > 0) {
                            $resultMessage .= ", Incompleted: " . $context['incompleteCount'];
                        }

                        if ($context['skipCount'] > 0) {
                            $resultMessage .= ", Skipped: " . $context['skipCount'];
                        }

                        if ($context['riskyCount'] > 0) {
                            $resultMessage .= ", Risky: " . $context['riskyCount'];
                        }

                        $record['message'] = $resultMessage;
                    }
                }

                return $record;
            }
        );

        $this->setLogger($console);
    }
}
