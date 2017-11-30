<?php

namespace NaxCrmBundle\Modules;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use PDO;
use PDOStatement;
use Doctrine\DBAL\Connection;
use JMS\Serializer\Serializer;

/**
 * 
 * This class is a handler for Monolog, which can be used
 * to write records in a MySQL table
 *
 * Class MySQLHandler
 * @package wazaari\MysqlHandler
 */
class LogDbHandler extends AbstractProcessingHandler
{
    /**
     * @var bool defines whether the MySQL connection is been initialized
     */
    private $initialized = false;
    /**
     * @var PDO pdo object of database connection
     */
    protected $pdo;
    /**
     * @var PDOStatement statement to insert a new record
     */
    private $statement;
    /**
     * @var string the table to store the logs in
     */
    private $table = 'logs';

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * Constructor of this class, sets the PDO and calls parent constructor
     *
     * @param Connection $connection                  PDO Connector for the database
     * @param bool|int $level           Debug level which this handler should store
     * @param bool $bubble
     */
    public function __construct(
        Connection $connection = null,
        $serializer,
        $level = Logger::INFO,
        $bubble = true
    ) {
        $this->pdo = $connection->getWrappedConnection();
        $this->serializer = $serializer;
        parent::__construct($level, $bubble);
    }
    /**
     * Initializes this handler by creating the table if it not exists
     */
    private function initialize()
    {
        $this->statement = $this->pdo->prepare(
            'INSERT INTO `'.$this->table.'` (channel, level, message, time, json_payload)
            VALUES (:channel, :level, :message, :time, :json_payload)'
        );
        $this->initialized = true;
    }
    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  $record[]
     * @return void
     */
    protected function write(array $record)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        //'context' contains the array
        $contentArray =array(
            'channel' => $record['channel'],
            'level' => $record['level'],
            'message' => $record['message'],
            'time' => $record['datetime']->format('Y-m-d H:i:s'),
        );

        $contentArray['json_payload'] = isset($record['context']) ? $this->serializer->serialize($record['context'], 'json') : '';

        $this->statement->execute($contentArray);
    }
}