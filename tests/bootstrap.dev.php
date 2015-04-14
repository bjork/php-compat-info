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
    protected $processors = array();

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

    /**
     * @var \DateTimeZone
     */
    protected static $timezone;

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

        if (!static::$timezone) {
            static::$timezone = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
        }

        $record = array(
            'message'  => (string) $message,
            'context'  => $context,
            'level'    => $level,
            'channel'  => $this->channel,
            'datetime' => \DateTime::createFromFormat(
                'U.u',
                sprintf('%.6F', microtime(true)),
                static::$timezone
            )->setTimezone(static::$timezone),
            'extra'    => array(),
        );

        $record = $this->processRecord($record);

        echo
            sprintf(
                '%s',
                $record['message']
            ),
            PHP_EOL
        ;
    }

    /**
     * Adds a processor in the stack.
     *
     * This code has been copied and adapted from Monolog
     *
     * @param  callable $callback
     * @return self
     */
    public function pushProcessor($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException(
                'Processors must be valid callables (callback or object with an __invoke method), '
                . var_export($callback, true)
                . ' given'
            );
        }
        array_unshift($this->processors, $callback);

        return $this;
    }

    /**
     * Processes a record.
     *
     * This code has been copied and adapted from Monolog
     *
     * @param  array $record
     * @return array
     */
    protected function processRecord(array $record)
    {
        if ($this->processors) {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record);
            }
        }

        return $record;
    }
}
