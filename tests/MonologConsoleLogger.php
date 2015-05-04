<?php

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FilterHandler;
use Monolog\Formatter\LineFormatter;

class MonologConsoleLogger extends Logger
{
    /**
     * Console logger class constructor
     *
     * @param string $name  The logging channel
     * @param string $level The minimum logging level
     */
    public function __construct($name = 'YourLogger', $level = Logger::DEBUG)
    {
        $stream = new RotatingFileHandler(__DIR__ . '/phpunit-phpcompatinfo.log', 30);
        $stream->setFilenameFormat('{filename}-{date}', 'Ymd');

        $console = new StreamHandler('php://stdout');
        $console->setFormatter(new LineFormatter("%message%\n", null, true));

        $filter = new FilterHandler($console);

        $handlers = array($filter, $stream);

        parent::__construct($name, $handlers);
    }

    /**
     * Returns list of accepted log levels
     *
     * @return array
     */
    public function getAcceptedLevels()
    {
        $handlers = $this->getHandlers();
        foreach ($handlers as &$handler) {
            if ($handler instanceof FilterHandler) {
                return $handler->getAcceptedLevels();
            }
        }
        return array();
    }

    /**
     * Defines log levels that will be accepted.
     *
     * @param int|array $minLevelOrList A list of levels to accept or a minimum level if maxLevel is provided
     * @param int       $maxLevel       Maximum level to accept, only used if $minLevelOrList is not an array
     *
     * @return void
     */
    public function setAcceptedLevels($minLevelOrList = Logger::DEBUG, $maxLevel = Logger::EMERGENCY)
    {
        $handlers = $this->getHandlers();
        foreach ($handlers as &$handler) {
            if ($handler instanceof FilterHandler) {
                $handler->setAcceptedLevels($minLevelOrList, $maxLevel);
                break;
            }
        }
    }
}
