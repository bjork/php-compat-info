<?php

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

    /**
     * A list of accepted level for logs
     *
     * @var int[]
     */
    protected $acceptedLevels;

    /**
     * Console logger class constructor
     *
     * @param string $name  The logging channel
     * @param string $level The minimum logging level (PSR-3 format)
     */
    public function __construct($name = 'YourLogger', $level = LogLevel::DEBUG)
    {
        $this->channel = $name;
        $this->setLevel($level);
    }

    /**
     * Adds a log record at an arbitrary level.
     *
     * @param  mixed   $level   The log level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        if (!$this->isHandling($level)) {
            return;
        }

        if (!static::$timezone) {
            static::$timezone = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
        }

        $record = array(
            'message'  => (string) $message,
            'context'  => $context,
            'level'    => is_string($level) ? $this->toMonologLevel($level) : $level,
            'level_name' => is_int($level) ? self::$levels[$level] : strtoupper($level),
            'channel'  => $this->channel,
            'datetime' => \DateTime::createFromFormat(
                'U.u',
                sprintf('%.6F', microtime(true)),
                static::$timezone
            )->setTimezone(static::$timezone),
            'extra'     => array(),
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
     * Returns list of accepted log levels (in PSR-3 format)
     *
     * @return array
     */
    public function getAcceptedLevels()
    {
        $acceptedLevels = array_map(
            function ($level) {
                return strtolower(self::$levels[$level]);
            },
            array_flip($this->acceptedLevels)
        );
        return $acceptedLevels;
    }

    /**
     * Defines log levels that will be accepted.
     *
     * @param int|array $minLevelOrList A list of levels to accept or a minimum level if maxLevel is provided
     * @param int       $maxLevel       Maximum level to accept, only used if $minLevelOrList is not an array
     *
     * @return void
     */
    public function setAcceptedLevels($minLevelOrList = LogLevel::DEBUG, $maxLevel = LogLevel::EMERGENCY)
    {
        if (is_array($minLevelOrList)) {
            $acceptedLevels = array_map(array($this, 'toMonologLevel'), $minLevelOrList);
        } else {
            $minLevelOrList = $this->toMonologLevel($minLevelOrList);
            $maxLevel = $this->toMonologLevel($maxLevel);
            $acceptedLevels = array_values(
                array_filter(
                    array_keys(self::$levels),
                    function ($level) use ($minLevelOrList, $maxLevel) {
                        return $level >= $minLevelOrList && $level <= $maxLevel;
                    }
                )
            );
        }
        $this->acceptedLevels = array_flip($acceptedLevels);
    }

    /**
     * Checks whether the Logger listens on the given level
     *
     * @param  string $level PSR3 LogLevel format
     * @return boolean
     */
    public function isHandling($level)
    {
        return isset($this->acceptedLevels[$this->toMonologLevel($level)]);
    }

    /**
     * Sets minimum logging level at which this handler will be triggered.
     *
     * @param  string $level PSR3 LogLevel format
     * @return self
     */
    public function setLevel($level)
    {
        $this->level = array_search(strtoupper($level), self::$levels);
        return $this;
    }

    /**
     * Gets minimum logging level at which this handler will be triggered.
     *
     * @return string PSR3 LogLevel format
     */
    public function getLevel()
    {
        return strtolower(self::$levels[$this->level]);
    }

    /**
     * Handlers list.
     *
     * @return array
     */
    public function getHandlers()
    {
        return array($this);
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

    /**
     * Convert a PSR-3 log level to monolog integer format
     *
     * @param  string $level PSR3 LogLevel format
     * @return int
     */
    protected function toMonologLevel($level)
    {
        return array_search(strtoupper($level), self::$levels);
    }
}
