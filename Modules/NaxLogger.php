<?php

namespace NaxCrmBundle\Modules;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use NaxCrmBundle\Entity\LogItem;
use NaxCrmBundle\Entity\Manager;

class NaxLogger
{
    /**
     * Detailed debug information
     */
    const DEBUG = 100;

    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    const INFO = 200;

    /**
     * Uncommon events
     */
    const NOTICE = 250;

    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    const WARNING = 300;

    /**
     * Runtime errors
     */
    const ERROR = 400;

    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    const CRITICAL = 500;

    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    const ALERT = 550;

    /**
     * Urgent alert.
     */
    const EMERGENCY = 600;

    /**
     * Logging levels from syslog protocol defined in RFC 5424
     *
     * @var array $levels Logging levels
     */
    protected static $levels = array(
        self::DEBUG     => 'DEBUG',
        self::INFO      => 'INFO',
        self::NOTICE    => 'NOTICE',
        self::WARNING   => 'WARNING',
        self::ERROR     => 'ERROR',
        self::CRITICAL  => 'CRITICAL',
        self::ALERT     => 'ALERT',
        self::EMERGENCY => 'EMERGENCY',
    );

    private $em;

    /**
     * @var LogItem
     */
    public $logItem;

    public function __construct(EntityManager $entityManager) {
        $this->em = $entityManager;
    }

    public function createLog($channel, $message, $url, $payload = null, $level = self::INFO) {
        if (!in_array($level, array_keys(self::$levels))) {
            throw new \InvalidArgumentException('Invalid level');
        }
        $logItem = LogItem::createInst();
        $logItem->setChannel($channel);
        $logItem->setMessage($message);
        $logItem->setUrl($url);
        $logItem->setJsonPayload(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $logItem->setLevel($level);

        $this->logItem = $logItem;
    }

    public function saveLog() {
        $this->em->persist($this->logItem);
        $this->em->flush();
    }
}