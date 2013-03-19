<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 30.10.12
 */

namespace Dive\Connection;

use Dive\Event\Dispatcher;
use Dive\Event\DispatcherInterface;
use Dive\Expression;
use Dive\Logging\SqlLogger;
use Dive\Platform\PlatformInterface;
use Dive\Table;


class Connection
{

    const EVENT_PRE_CONNECT     = 'Dive.Connection.preConnect';
    const EVENT_POST_CONNECT    = 'Dive.Connection.postConnect';

    const EVENT_PRE_DISCONNECT  = 'Dive.Connection.preDisconnect';
    const EVENT_POST_DISCONNECT = 'Dive.Connection.postDisconnect';

    const EVENT_PRE_QUERY       = 'Dive.Connection.preQuery';
    const EVENT_POST_QUERY      = 'Dive.Connection.postQuery';

    const EVENT_PRE_EXEC        = 'Dive.Connection.preExec';
    const EVENT_POST_EXEC       = 'Dive.Connection.postExec';

    const EVENT_PRE_INSERT      = 'Dive.Connection.preInsert';
    const EVENT_POST_INSERT     = 'Dive.Connection.postInsert';

    const EVENT_PRE_UPDATE      = 'Dive.Connection.preUpdate';
    const EVENT_POST_UPDATE     = 'Dive.Connection.postUpdate';

    const EVENT_PRE_DELETE      = 'Dive.Connection.preDelete';
    const EVENT_POST_DELETE     = 'Dive.Connection.postDelete';


    /**
     * @var \PDO
     */
    private $dbh;
    /**
     * @var string
     */
    private $scheme = '';
    /**
     * @var string
     */
    private $user;
    /**
     * @var string
     */
    private $password;
    /**
     * @var Driver\DriverInterface
     */
    protected $driver = null;
    /**
     * @var PlatformInterface
     */
    protected $platform = null;
    /**
     * @var string
     */
    protected $dsn;
    /**
     * @var \Dive\Event\DispatcherInterface|\Dive\Event\Dispatcher
     */
    protected $eventDispatcher;
    /**
     * @var string
     */
    protected $encoding = PlatformInterface::ENC_UTF8;
    /**
     * @var SqlLogger
     */
    protected $sqlLogger;


    /**
     * constructor
     * @param   Driver\DriverInterface  $driver
     * @param   string                  $dsn
     * @param   string                  $user
     * @param   string                  $password
     * @param   \Dive\Event\DispatcherInterface|\Dive\Event\Dispatcher $eventDispatcher
     */
    public function __construct(
        Driver\DriverInterface $driver,
        $dsn,
        $user = '',
        $password = '',
        DispatcherInterface $eventDispatcher = null
    ) {
        $this->driver   = $driver;
        $this->platform = $driver->getPlatform();
        $this->scheme   = $this->getSchemeFromDsnOrThrowException($dsn);
        $this->dsn      = $dsn;
        $this->user     = $user;
        $this->password = $password;
        $this->eventDispatcher = $eventDispatcher;
    }


    /**
     * @return Driver\DriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }


    /**
     * @return \Dive\Platform\PlatformInterface
     */
    public function getPlatform()
    {
        return $this->platform;
    }


    /**
     * Gets string quote character
     *
     * @return string
     */
    public function getStringQuote()
    {
        return $this->platform->getStringQuote();
    }


    /**
     * Gets identifier quote character
     *
     * @return string
     */
    public function getIdentifierQuote()
    {
        return $this->platform->getIdentifierQuote();
    }


    /**
     * gets event dispatcher
     *
     * @return \Dive\Event\Dispatcher|\Dive\Event\DispatcherInterface
     */
    public function getEventDispatcher()
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = new Dispatcher();
        }
        return $this->eventDispatcher;
    }


    /**
     * Gets scheme from dsn
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }


    /**
     * Sets connection encoding
     *
     * @param string $encoding
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }


    /**
     * Gets connection encoding
     * Note: It's not read from database connection!
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }


    /**
     * Sets sql logger
     *
     * @param SqlLogger $sqlLogger
     */
    public function setSqlLogger(SqlLogger $sqlLogger)
    {
        $this->sqlLogger = $sqlLogger;
    }


    /**
     * Gets sql logger
     *
     * @return SqlLogger
     */
    public function getSqlLogger()
    {
        return $this->sqlLogger;
    }


    public function connect()
    {
        if (!$this->isConnected()) {
            $this->dispatchEvent(self::EVENT_PRE_CONNECT);
            $this->dbh = new \PDO($this->dsn, $this->user, $this->password);
            $encodingSql = $this->platform->getSetConnectionEncodingSql($this->encoding);
            $this->dbh->exec($encodingSql);
            $this->dispatchEvent(self::EVENT_POST_CONNECT);
        }
    }


    /**
     * disconnects connection
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            $this->dispatchEvent(self::EVENT_PRE_DISCONNECT);
            $this->dbh = null;
            $this->dispatchEvent(self::EVENT_POST_DISCONNECT);
        }
    }


    protected function dispatchEvent($eventName, $sql = '', array $params = array())
    {
        if ($this->eventDispatcher) {
            $connectEvent = new ConnectionEvent($this, $sql, $params);
            $this->eventDispatcher->dispatch($eventName, $connectEvent);
        }
    }


    protected function dispatchRowEvent($eventName, Table $table, array $fields = array(), array $identifier = array())
    {
        if ($this->eventDispatcher) {
            $connectEvent = new ConnectionRowEvent($this, $table, $fields, $identifier);
            $this->eventDispatcher->dispatch($eventName, $connectEvent);
        }
    }


    /**
     * @return bool
     */
    public function isConnected()
    {
        return null !== $this->dbh;
    }


    /**
     * @return \PDO
     */
    public function getDbh()
    {
        $this->connect();
        return $this->dbh;
    }


    public function getDsn()
    {
        return $this->dsn;
    }


    public function beginTransaction()
    {
        $this->getDbh()->beginTransaction();
    }


    public function commit()
    {
        $this->getDbh()->commit();
    }


    public function rollBack()
    {
        $this->getDbh()->rollBack();
    }


    /**
     * @param  string $sql
     * @param  array  $params
     * @return \PDOStatement
     */
    public function getStatement($sql, $params = array())
    {
        $this->connect();

        $this->dispatchEvent(self::EVENT_PRE_QUERY, $sql, $params);
        $this->startSqlLogger($sql, $params);

        if (!empty($params)) {
            $stmt = $this->prepare($sql);
            $stmt->execute($params);
        }
        else {
            $stmt = $this->dbh->query($sql);
        }

        $this->stopSqlLogger();
        $this->dispatchEvent(self::EVENT_POST_QUERY, $sql, $params);

        if ($stmt === false) {
            $this->throwErrorAsException($sql, $params);
        }
        return $stmt;
    }


    /**
     * prepares an sql statement
     *
     * @param  string $sql
     * @throws ConnectionException
     * @return \PDOStatement
     */
    protected function prepare($sql)
    {
        $stmt = $this->dbh->prepare($sql);
        if ($stmt === false) {
            throw new ConnectionException(
                "Sql execution failed: \"$sql\"! Reason: " . print_r($this->dbh->errorInfo(), true)
            );
        }
        return $stmt;
    }


    // TODO unittest? same like getStatement? should we really test "fetchAll"?
    public function query($sql, array $params = array(), $pdoFetchMode = \PDO::FETCH_ASSOC)
    {
        $stmt = $this->getStatement($sql, $params);
        $return = $stmt->fetchAll($pdoFetchMode);
        return $return;
    }


    // TODO unittest!
    public function exec($sql, array $params = array())
    {
        $this->connect();

        $this->dispatchEvent(self::EVENT_PRE_EXEC, $sql, $params);
        $this->startSqlLogger($sql, $params);

        if (!empty($params)) {
            $stmt = $this->prepare($sql);
            $stmt->execute($params);
            $return = $stmt->rowCount();
        }
        else {
            $return = $this->dbh->exec($sql);
        }

        $this->stopSqlLogger();
        $this->dispatchEvent(self::EVENT_POST_EXEC, $sql, $params);

        if ($return === false) {
            throw new ConnectionException(
                "Sql execution failed: \"$sql\"! Reason: " . print_r($this->dbh->errorInfo(), true)
            );
        }
        return $return;
    }


    /**
     * @param string $sql
     * @param array  $params
     */
    protected function startSqlLogger($sql, array $params)
    {
        if ($this->sqlLogger) {
            $this->sqlLogger->startQuery($sql, $params);
        }
    }


    protected function stopSqlLogger()
    {
        if ($this->sqlLogger) {
            $this->sqlLogger->stopQuery();
        }
    }


    /**
     * quotes value or expression for use in sql
     *
     * @param  string|Expression $value
     * @return string
     */
    public function quote($value)
    {
        return $this->platform->quote($value);
    }


    /**
     * quotes identifier for use in sql
     *
     * @param  string $identifier
     * @return string
     */
    public function quoteIdentifier($identifier)
    {
        return $this->platform->quoteIdentifier($identifier);
    }


    /**
     * gets scheme from data source name or throw exception
     *
     * @param  string $dsn
     * @throws \InvalidArgumentException
     * @return string
     */
    private function getSchemeFromDsnOrThrowException($dsn)
    {
        $pos = strpos($dsn, ':');
        if (false === $pos) {
            throw new \InvalidArgumentException(
                "Data source name '$dsn' must define a database scheme: ie. 'mysql:host=localhost;'!"
            );
        }
        $scheme = strtolower(substr($dsn, 0, $pos));
        if (!preg_match('/^[a-z0-9_]+$/', $scheme)) {
            throw new \InvalidArgumentException("Scheme seems to contain invalid characters! You gave me: $scheme");
        }
        return $scheme;
    }


    public function getDatabaseName()
    {
        return $this->driver->getDatabaseName($this);
    }


    public function disableForeignKeys()
    {
        $this->exec($this->platform->getDisableForeignKeyChecksSql());
    }


    public function enableForeignKeys()
    {
        $this->exec($this->platform->getEnableForeignKeyChecksSql());
    }


    /**
     * Insert row
     * TODO unit test it!
     *
     * @param Table $table
     * @param array $fields
     * @return int
     */
    public function insert(Table $table, array $fields)
    {
        $columns = array();
        $values = array();
        $params = array();
        $identifierFields = $table->getIdentifierAsArray();
        $identifier = array();
        foreach ($fields as $fieldName => $value) {
            if ($table->hasField($fieldName)) {
                $columns[] = $this->quoteIdentifier($fieldName);
                if ($value instanceof Expression) {
                    /** @var Expression $value */
                    $values[] = $value->getSql();
                }
                else {
                    $values[] = '?';
                    $params[] = $value;
                }
                if ($value !== null && in_array($identifier, $identifierFields)) {
                    $identifier[$fieldName] = $value;
                }
            }
        }
        // build query
        $query = 'INSERT INTO ' . $this->quoteIdentifier($table->getTableName())
            . ' (' . implode(', ', $columns) . ')'
            . ' VALUES (' . implode(', ', $values) . ')';

        if (count($identifier) !== count($identifierFields)) {
            $identifier = array();
        }

        $this->dispatchRowEvent(self::EVENT_PRE_INSERT, $table, $fields, $identifier);
        $affectedRows = $this->exec($query, $params);
        if (empty($identifier) && count($identifierFields) == 1) {
            $identifier[$identifierFields[0]] = $this->getLastInsertId($table->getTableName());
        }
        $this->dispatchRowEvent(self::EVENT_POST_INSERT, $table, $fields, $identifier);
        return $affectedRows;
    }


    /**
     * TODO database management systems have different support to get the last inserted id
     * gets last inserted id
     *
     * @param  string $tableName
     * @return string
     */
    public function getLastInsertId($tableName = null)
    {
        return $this->dbh->lastInsertId($tableName);
    }


    /**
     * Updates row
     * TODO unit test it!
     *
     * @param Table $table
     * @param array $fields
     * @param string|array $identifier
     * @return bool|int
     * @throws \InvalidArgumentException
     */
    public function update(Table $table, array $fields, $identifier)
    {
        if (empty($fields)) {
            return false;
        }

        if (!is_array($identifier)) {
            $identifier = array($identifier);
        }
        $this->throwExceptionIfIdentifierDoesNotMatchTableIdentifier($table, $identifier);

        $identifierFields = $table->getIdentifier();
        if (!is_array($identifierFields)) {
            $identifierFields = array($identifierFields);
        }
        foreach ($identifierFields as &$field) {
            $field = $this->quoteIdentifier($field);
        }

        $set = array();
        $params = array();
        foreach ($fields as $fieldName => $value) {
            if ($table->hasField($fieldName)) {
                $column = $this->quoteIdentifier($fieldName);
                if ($value instanceof Expression) {
                    /** @var Expression $value */
                    $set[] = $column . ' = ' . $value->getSql();
                }
                else {
                    $set[] = $column . ' = ?';
                    $params[] = $value;
                }
            }
        }
        // build query
        $query = 'UPDATE ' . $this->quoteIdentifier($table->getTableName())
            . ' SET ' . implode(', ', $set)
            . ' WHERE ' . implode(' = ? AND ', $identifierFields) . ' = ?';

        $params = array_merge($params, $identifier);

        $this->dispatchRowEvent(self::EVENT_PRE_UPDATE, $table, $fields, $identifier);
        $affectedRows = $this->exec($query, $params);
        $this->dispatchRowEvent(self::EVENT_POST_UPDATE, $table, $fields, $identifier);
        return $affectedRows;
    }


    /**
     * delete row
     * TODO unit test it!
     *
     * @param  Table         $table
     * @param  string|array  $identifier
     * @return int           affected rows
     * @throws \InvalidArgumentException
     */
    public function delete(Table $table, $identifier)
    {
        if (!is_array($identifier)) {
            $identifier = array($identifier);
        }
        $this->throwExceptionIfIdentifierDoesNotMatchTableIdentifier($table, $identifier);

        $identifierFields = $table->getIdentifier();
        if (!is_array($identifierFields)) {
            $identifierFields = array($identifierFields);
        }
        foreach ($identifierFields as &$field) {
            $field = $this->quoteIdentifier($field);
        }

        // build query
        $query = 'DELETE FROM ' . $this->quoteIdentifier($table->getTableName())
            . ' WHERE ' . implode(' = ? AND ', $identifierFields) . ' = ?';

        $this->dispatchRowEvent(self::EVENT_PRE_DELETE, $table, array(), $identifier);
        $affectedRows = $this->exec($query, $identifier);
        $this->dispatchRowEvent(self::EVENT_POST_DELETE, $table, array(), $identifier);
        return $affectedRows;
    }


    private static function throwExceptionIfIdentifierDoesNotMatchTableIdentifier(Table $table, array $identifier)
    {
        $identifierFields = $table->getIdentifier();
        if (!is_array($identifierFields)) {
            $identifierFields = array($identifierFields);
        }
        if (count($identifierFields) != count($identifier)) {
            throw new \InvalidArgumentException(
                'Identifier does not match table identifier (' . implode(', ', $identifierFields) . ')'
            );
        }
    }


    public function throwErrorAsException($sql, array $params)
    {
        $error = $this->dbh->errorInfo();
        if (is_array($error) && $error[0] !== '00000') {
            throw new ConnectionException(
                $error[0] . ' ' . $error[2]
                . "\nGiven sql: $sql"
                . "\nGiven params: " . print_r($params, true)
            );
        }
    }

}
