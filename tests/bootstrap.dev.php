<?php

$baseDir   = dirname(__DIR__);
$vendorDir = $baseDir . '/vendor';

$loader = require_once $vendorDir . '/autoload.php';

require __DIR__ . '/Reference/GenericTest.php';
require __DIR__ . '/ResultPrinter.php';

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class Psr3ConsoleLogger extends AbstractLogger
{
    protected $channel;
    protected $level;

    /**
     * Logging levels from syslog protocol defined in RFC 5424
     *
     * @var array $levels Logging levels
     */
    protected static $levels = array(
        100 => 'DEBUG',
        200 => 'INFO',
        250 => 'NOTICE',
        300 => 'WARNING',
        400 => 'ERROR',
        500 => 'CRITICAL',
        550 => 'ALERT',
        600 => 'EMERGENCY',
    );

    public function __construct($name = 'YourLogger', $level = LogLevel::DEBUG)
    {
        $this->channel = $name;
        $this->level   = array_search(strtoupper($level), self::$levels);
    }

    public function log($level, $message, array $context = array())
    {
        if (array_search(strtoupper($level), self::$levels) < $this->level) {
            return;
        }

        if ($this->level == 100  // DEBUG
            && isset($context['operation'])
            && 'startTest' == $context['operation']
        ) {
            $describe = \PHPUnit_Util_Test::describe($context['test']);
            $pos      = strpos($describe, $context['testName']);
            $describe = substr($describe, $pos);
            $message  = str_replace($context['testName'], $describe, $message);
        }

        echo
            sprintf(
                '%s',
                $message
            ),
            PHP_EOL
        ;
    }
}
