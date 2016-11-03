<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Query;

use Dive\RecordManager;
use Dive\Connection\Connection;
use Dive\Util\StringExplode;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 10.02.13
 */
class Query implements QueryInterface, QueryHydrationInterface
{
    const PART_SELECT       = 'select';
    const PART_DISTINCT     = 'distinct';
    const PART_FOR_UPDATE   = 'forUpdate';
    const PART_FROM         = 'from';
    const PART_WHERE        = 'where';
    const PART_GROUP_BY     = 'groupBy';
    const PART_HAVING       = 'having';
    const PART_ORDER_BY     = 'orderBy';


    /**
     * @var \Dive\RecordManager
     */
    protected $rm;

    /**
     * @var QueryParser
     */
    protected $parser;

    /**
     * @var array
     */
    protected $queryParts = array(
        self::PART_SELECT     => array(),
        self::PART_DISTINCT   => false,
        self::PART_FOR_UPDATE => false,
        self::PART_FROM       => array(),
        //'set'       => array(), // TODO only for update
        self::PART_WHERE      => array(),
        self::PART_GROUP_BY   => array(),
        self::PART_HAVING     => array(),
        self::PART_ORDER_BY   => array()
    );

    /**
     * @var array
     */
    protected $sqlParts = array(
        self::PART_SELECT     => '',
        self::PART_FROM       => '',
        self::PART_WHERE      => '',
        self::PART_GROUP_BY   => '',
        self::PART_HAVING     => '',
        self::PART_ORDER_BY   => '',
        self::PART_FOR_UPDATE => ''
    );

    /**
     * @var array
     */
    protected $params = array(
        self::PART_SELECT   => array(),
        self::PART_FROM     => array(),
        //'set'       => array(), // TODO only for update
        self::PART_WHERE    => array(),
        self::PART_GROUP_BY  => array(),
        self::PART_HAVING   => array(),
        self::PART_ORDER_BY => array()
    );

    /**
     * @var array
     */
    protected $queryComponents = array();
    /**
     * @var string
     */
    protected $rootAlias = '';
    /**
     * @var string
     */
    protected $sql;
    /**
     * @var bool
     */
    protected $isDirty = true;
    /**
     * @var int
     */
    protected $offset = 0;
    /**
     * @var int
     */
    protected $limit = 0;
    /**
     * @var string
     */
    protected $fetchMode = RecordManager::FETCH_RECORD_COLLECTION;


    /**
     * @param RecordManager $recordManager
     * @param QueryParser   $parser
     */
    public function __construct(RecordManager $recordManager, QueryParser $parser = null)
    {
        $this->rm = $recordManager;
        if (null === $parser) {
            $parser = new QueryParser();
        }
        $this->parser = $parser;
    }


    /**
     * @return \Dive\RecordManager
     */
    public function getRecordManager()
    {
        return $this->rm;
    }


    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->rm->getConnection();
    }


    /**
     * @param  int $limit
     * @param  int $offset
     * @return $this
     */
    public function limitOffset($limit, $offset)
    {
        $this->limit($limit);
        $this->offset($offset);
        return $this;
    }


    /**
     * Sets select part of query
     *
     * @param  string        $select
     * @param  array|string  $params
     * @return $this
     */
    public function select($select, $params = array())
    {
        $this->setQueryPart(self::PART_SELECT, $select, $params);
        return $this;
    }


    /**
     * Add to select part of query
     *
     * @param  string        $select
     * @param  array|string  $params
     * @return $this
     */
    public function addSelect($select, $params = array())
    {
        $this->addQueryPart(self::PART_SELECT, $select, $params);
        return $this;
    }


    /**
     * Sets distinct flag for query
     *
     * @param  bool $distinct
     * @return $this
     */
    public function distinct($distinct = true)
    {
        $this->queryParts[self::PART_DISTINCT] = (bool)$distinct;
        return $this;
    }


    /**
     * Sets for update part for query
     *
     * @param  bool $forUpdate
     * @return $this
     */
    public function forUpdate($forUpdate = true)
    {
        $this->queryParts[self::PART_FOR_UPDATE] = (bool)$forUpdate;
        return $this;
    }


    /**
     * Sets from part for query
     *
     * @param  string        $from
     * @param  array|string  $params
     * @throws QueryException
     * @return $this
     */
    public function from($from, $params = array())
    {
        $pos = strpos($from, ' ');
        if ($pos === false) {
            throw new QueryException("Missing alias in query from '$from'!");
        }
        list($tableName, $alias) = StringExplode::explodeAt($from, $pos);
        $alias = trim($alias);
        $this->rootAlias = $alias;
        $this->queryComponents[$alias] = array(
            'table' => $tableName
        );
        $quote = $this->getRecordManager()->getConnection()->getIdentifierQuote();
        $from = $quote . $tableName . $quote . ' ' . $alias;
        $this->setQueryPart(self::PART_FROM, $from, $params);
        return $this;
    }


    /**
     * Adds left join for query
     *
     * @param  string        $leftJoin
     * @param  array|string  $params
     * @return $this
     */
    public function leftJoin($leftJoin, $params = array())
    {
        $joinDef = $this->parseLeftJoin($leftJoin);
        $alias = $joinDef['alias'];
        $this->queryComponents[$alias] = $joinDef;
        $leftJoin = $this->getLeftJoinByDefinition($joinDef);
        $this->addQueryPart(self::PART_FROM, 'LEFT JOIN ' . $leftJoin, $params);
        return $this;
    }


    /**
     * Adds left join with a specific on-clause
     *
     * @param  string $leftJoin
     * @param  string $onClause
     * @param  array  $params
     * @return $this
     */
    public function leftJoinOn($leftJoin, $onClause, $params = array())
    {
        $joinDef = $this->parseLeftJoin($leftJoin, array('onClause' => $onClause));
        $alias = $joinDef['alias'];
        $this->queryComponents[$alias] = $joinDef;
        $leftJoin = $this->getLeftJoinByDefinition($joinDef);
        $this->addQueryPart(self::PART_FROM, 'LEFT JOIN ' . $leftJoin, $params);
        return $this;
    }


    /**
     * Adds left join with an extended on-clause (a so called with-clause)
     *
     * @param  string $leftJoin
     * @param  string $withClause
     * @param  array  $params
     * @return $this
     */
    public function leftJoinWith($leftJoin, $withClause, $params = array())
    {
        $joinDef = $this->parseLeftJoin($leftJoin, array('withClause' => $withClause));
        $alias = $joinDef['alias'];
        $this->queryComponents[$alias] = $joinDef;
        $leftJoin = $this->getLeftJoinByDefinition($joinDef);
        $this->addQueryPart(self::PART_FROM, 'LEFT JOIN ' . $leftJoin, $params);
        return $this;
    }


    /**
     * parses left join definition and adds query component
     *
     * @param  string $leftJoin
     * @param  array  $addDefinition
     * @return array
     * @throws QueryException
     */
    protected function parseLeftJoin($leftJoin, $addDefinition = array())
    {
        $joinDef = $this->parser->parseLeftJoin($leftJoin, $this);
        $parentAlias = $joinDef['parent'];
        if ($addDefinition) {
            $joinDef = array_merge($joinDef, $addDefinition);
        }
        $parentTableName = $this->queryComponents[$parentAlias]['table'];
        $parentTable = $this->rm->getTable($parentTableName);
        $relationName = $joinDef['relation'];
        $relation = $parentTable->getRelation($relationName);
        $joinDef['table'] = $relation->getJoinTableName($relationName);
        return $joinDef;
    }


    /**
     * @param array $definition
     * @return string
     */
    protected function getLeftJoinByDefinition(array $definition)
    {
        $conn = $this->rm->getConnection();
        $quote = $conn->getIdentifierQuote();
        if (!isset($definition['onClause'])) {
            $parentAlias = $definition['parent'];
            $alias = $definition['alias'];
            $relationName = $definition['relation'];
            $parentTableName = $this->queryComponents[$parentAlias]['table'];
            $parentTable = $this->rm->getTable($parentTableName);
            $relation = $parentTable->getRelation($relationName);
            $onClause = $relation->getJoinOnCondition($relationName, $parentAlias, $alias, $quote);
            if (isset($definition['withClause'])) {
                $onClause .= ' AND ' . $definition['withClause'];
            }
        }
        else {
            $onClause = $definition['onClause'];
        }
        return $conn->quoteIdentifier($definition['table'])
            . ' ' . $conn->quoteIdentifier($definition['alias'])
            . ' ON ' . $onClause;
    }


    /**
     * Sets where part for query
     *
     * @param   string       $expr
     * @param   array|string $params
     * @return  $this
     */
    public function where($expr, $params = array())
    {
        $this->setQueryPart(self::PART_WHERE, $expr, $params);
        return $this;
    }


    /**
     * Adds where part connected with logical AND for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function andWhere($expr, $params = array())
    {
        $this->addWherePart($expr, $params);
        return $this;
    }


    /**
     * Adds where part connected with logical OR for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function orWhere($expr, $params = array())
    {
        $this->addWherePart($expr, $params, 'OR');
        return $this;
    }


    /**
     * Sets where part with IN clause for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function whereIn($expr, $params = array())
    {
        $this->removeQueryPart(self::PART_WHERE);
        $this->addWhereInClause($expr, $params);
        return $this;
    }


    /**
     * Sets where part with NOT IN clause for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function whereNotIn($expr, $params = array())
    {
        $this->removeQueryPart(self::PART_WHERE);
        $this->addWhereInClause($expr, $params, true);
        return $this;
    }


    /**
     * Adds where part with IN clause connected with logical AND for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function andWhereIn($expr, $params = array())
    {
        $this->addWhereInClause($expr, $params);
        return $this;
    }


    /**
     * Adds where part with NOT IN clause connected with logical AND for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function andWhereNotIn($expr, $params = array())
    {
        $this->addWhereInClause($expr, $params, true);
        return $this;
    }


    /**
     * Adds where part with IN clause connected with logical OR for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function orWhereIn($expr, $params = array())
    {
        $this->addWhereInClause($expr, $params, false, 'OR');
        return $this;
    }


    /**
     * Adds where part with NOT IN clause connected with logical OR for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function orWhereNotIn($expr, $params = array())
    {
        $this->addWhereInClause($expr, $params, true, 'OR');
        return $this;
    }


    /**
     * Adds query where part
     *
     * @param string        $expr
     * @param array|string  $params
     * @param string        $logicalGlue
     */
    protected function addWherePart($expr, $params, $logicalGlue = 'AND')
    {
        if (empty($this->queryParts[self::PART_WHERE])) {
            $this->setQueryPart(self::PART_WHERE, $expr, $params);
        }
        else {
            $this->addQueryPart(self::PART_WHERE, $logicalGlue . ' ' . $expr, $params);
        }
    }


    /**
     * adds where in clause
     *
     * @param string        $expr
     * @param array|string  $params
     * @param bool          $notIn
     * @param string        $logicalGlue
     */
    protected function addWhereInClause($expr, $params, $notIn = false, $logicalGlue = 'AND')
    {
        if (empty($params)) {
            // TODO IN-clause must be not empty!!
            //throw new QueryException('Missing params for WHERE IN clause');
        }
        if (!is_array($params)) {
            $params = array($params);
        }
        $marks = substr(str_repeat('?,', count($params)), 0, -1);
        $expr .= ($notIn ? ' NOT ' : '') . ' IN (' . $marks . ')';

        $this->addWherePart($expr, $params, $logicalGlue);
    }


    /**
     * Sets group by part for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function groupBy($expr, $params = array())
    {
        $this->setQueryPart(self::PART_GROUP_BY, $expr, $params);
        return $this;
    }


    /**
     * Adds group by part for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function addGroupBy($expr, $params = array())
    {
        $this->addQueryPart(self::PART_GROUP_BY, $expr, $params);
        return $this;
    }


    /**
     * Sets having part for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function having($expr, $params = array())
    {
        $this->setQueryPart(self::PART_HAVING, $expr, $params);
        return $this;
    }


    /**
     * Adds having part connected with logical AND for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function andHaving($expr, $params = array())
    {
        if (!empty($this->queryParts[self::PART_HAVING])) {
            $expr = ' AND ' . $expr;
        }
        $this->addQueryPart(self::PART_HAVING, $expr, $params);
        return $this;
    }


    /**
     * Adds having part connected with logical OR for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function orHaving($expr, $params = array())
    {
        if (!empty($this->queryParts[self::PART_HAVING])) {
            $expr = ' OR ' . $expr;
        }
        $this->addQueryPart(self::PART_HAVING, $expr, $params);
        return $this;
    }


    /**
     * Set order by part for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function orderBy($expr, $params = array())
    {
        $this->setQueryPart(self::PART_ORDER_BY, $expr, $params);
        return $this;
    }


    /**
     * Adds order by part for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function addOrderBy($expr, $params = array())
    {
        $this->addQueryPart(self::PART_ORDER_BY, $expr, $params);
        return $this;
    }


    /**
     * Sets query limit
     *
     * @param  int $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }


    /**
     * Sets query offset
     *
     * @param  int $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }


    /**
     * Gets query limit
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }


    /**
     * Gets query offset
     *
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }


    /**
     * @return Query
     */
    public function copy()
    {
       return clone $this;
    }


    public function __clone()
    {
        $this->isDirty = true;
        $this->sqlParts = array();
        $this->sql = '';
        $this->parser = clone $this->parser;
    }


    /**
     * return int
     */
    public function countByPk()
    {
        $query = $this->copy();

        $query->removeQueryPart(self::PART_ORDER_BY);
        $query->removeQueryPart(self::PART_GROUP_BY);
        $query->removeQueryPart(self::PART_HAVING);
        $query->limitOffset(0, 0);

        $rootAlias = $query->getRootAlias();
        $idExpression = $query->getRootTable()->getIdentifierQueryExpression($rootAlias);

        $query->select("COUNT(DISTINCT $idExpression)");
        return (int)$query->fetchSingleScalar();
    }


    /**
     * @return int
     */
    public function count()
    {
        $query = $this->copy();

        $query->removeQueryPart(self::PART_ORDER_BY);
        $query->limitOffset(0, 0);

        $queryParts = $query->getQueryParts();

        if (empty($queryParts[self::PART_GROUP_BY])) {
            $query->select('COUNT(*)');
            return (int)$query->fetchSingleScalar();
        }

        $sql = $query->getSql();
        $sql = 'SELECT COUNT(*) FROM (' . $sql . ') __tmp';

        // build pdo statement
        $stmt = $this->getConnection()->getStatement($sql, $query->getParamsFlattened());
        $hydrator = $this->rm->getHydrator(RecordManager::FETCH_SINGLE_SCALAR);
        $hydrator->setStatement($stmt);
        return (int)$hydrator->getResult($query->getRootTable());
    }


    /**
     * @return bool
     * @throws QueryException
     */
    public function hasResult()
    {
        $query = $this->copy();
        $query->removeQueryPart(self::PART_ORDER_BY);
        $query->select('(1+0)');
        $query->limit(1);
        $result = $query->fetchSingleScalar();
        return $result !== false;
    }


    /**
     * Gets fetch mode
     *
     * @return string
     */
    public function getFetchMode()
    {
        return $this->fetchMode;
    }


    /**
     * Sets fetch mode
     *
     * @param  string $fetchMode
     * @return $this
     */
    public function setFetchMode($fetchMode)
    {
        $this->fetchMode = $fetchMode;
        return $this;
    }


    /**
     * Executes the query and returns hydrated result
     *
     * @param  string $fetchMode
     * @return mixed
     */
    public function execute($fetchMode = null)
    {
        if (null === $fetchMode) {
            $fetchMode = $this->getFetchMode();
        }
        // parsed sql statement
        $sql = $this->getSql();
        // build pdo statement
        $stmt = $this->getConnection()->getStatement($sql, $this->getParamsFlattened());
        $hydrator = $this->rm->getHydrator($fetchMode);
        $hydrator->setStatement($stmt);
        return $hydrator->getResult($this->getRootTable());
    }


    /**
     * Fetches the query result as array
     *
     * @return array
     */
    public function fetchArray()
    {
        return $this->execute(RecordManager::FETCH_ARRAY);
    }


    /**
     * Fetches the query result as record collection
     *
     * @return \Dive\Collection\RecordCollection
     */
    public function fetchObjects()
    {
        return $this->execute(RecordManager::FETCH_RECORD_COLLECTION);
    }


    /**
     * Fetches the query result as record instance
     *
     * @return \Dive\Record|bool    Returns false, if result is empty
     */
    public function fetchOneAsObject()
    {
        return $this->execute(RecordManager::FETCH_RECORD);
    }


    /**
     * Fetches the query result as single array
     *
     * @return array|bool   Returns false, if result is empty
     */
    public function fetchOneAsArray()
    {
        return $this->execute(RecordManager::FETCH_SINGLE_ARRAY);
    }


    /**
     * Fetches the query result as single scalar
     *
     * @return string|bool  Returns false, if result is empty
     */
    public function fetchSingleScalar()
    {
        return $this->execute(RecordManager::FETCH_SINGLE_SCALAR);
    }


    /**
     * Fetches the query result as array of scalars
     *
     * @return array
     */
    public function fetchScalars()
    {
        return $this->execute(RecordManager::FETCH_SCALARS);
    }


    /**
     * @return string
     */
    public function getSql()
    {
        $this->parse();
        return $this->getLimitedSql($this->sql);
    }


    /**
     * TODO what about database portability here?
     *
     * @param  string $sql
     * @return string
     */
    private function getLimitedSql($sql)
    {
        if ($this->limit > 0) {
            $sql .= "\n" . ' LIMIT ' . $this->limit;
            if ($this->offset > 0) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }
        return $sql;
    }


    protected function parse()
    {
        if (!$this->isDirty) {
            return;
        }
        $this->sqlParts = $this->parser->parseQuery($this);
        $this->sql = implode("\n", $this->sqlParts);
        $this->isDirty = false;
    }


    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getSql();
    }


    /**
     * Gets query params for a given query part
     *
     * @param  string $part
     * @return array
     */
    public function getParams($part)
    {
        if (isset($this->params[$part])) {
            return $this->params[$part];
        }
        return array();
    }


    /**
     * Gets query params flattened
     * @param array $params
     * @return array
     */
    public function getParamsFlattened(array $params = null)
    {
        if ($params === null) {
            $params = $this->params;
        }
        $flattenParams = array();
        foreach ($params as $partParams) {
            if (!empty($partParams)) {
                $flattenParams = array_merge($flattenParams, $partParams);
            }
        }
        return $flattenParams;
    }


    /**
     * Sets query params for a given part
     *
     * @param  string $part
     * @param  array  $params
     * @return $this
     */
    public function setParams($part, array $params)
    {
        $this->params[$part] = $params;
        return $this;
    }


    /**
     * Gets query part
     *
     * @param  string $part
     * @return array|bool
     * @throws QueryException
     */
    public function getQueryPart($part)
    {
        if (!isset($this->queryParts[$part])) {
            throw new QueryException("Query part '$part' is not defined!");
        }
        return $this->queryParts[$part];
    }


    /**
     * @return array
     */
    public function getQueryParts()
    {
        return $this->queryParts;
    }


    /**
     * Removes query part
     *
     * @param  string $part
     * @throws QueryException
     * @return $this
     */
    public function removeQueryPart($part)
    {
        if (!isset($this->queryParts[$part])) {
            throw new QueryException("Query part '$part' is not defined/supported!");
        }

        $default = in_array($part, [self::PART_FOR_UPDATE, self::PART_DISTINCT], true) ? false : array();
        $this->queryParts[$part] = $default;
        unset($this->params[$part]);
        return $this;
    }


    /**
     * Sets query part
     *
     * @param string        $part
     * @param string        $partValue
     * @param array|mixed   $params
     */
    protected function setQueryPart($part, $partValue, $params)
    {
        if (!is_array($params)) {
            $params = [$params];
        }

        $this->queryParts[$part] = [$partValue];
        $this->params[$part] = $params;
        $this->isDirty = true;
    }


    /**
     * Adds query part
     *
     * @param string        $part
     * @param string        $partValue
     * @param array|mixed   $params
     */
    protected function addQueryPart($part, $partValue, $params)
    {
        $this->queryParts[$part][] = $partValue;
        $this->isDirty = true;

        if (!empty($params)) {
            if (is_array($params)) {
                $this->params[$part] = array_merge($this->params[$part], $params);
            }
            else {
                $this->params[$part][] = $params;
            }
        }
    }


    /**
     * Returns true, if query has a query component for the given query alias
     *
     * @param  string $alias
     * @return bool
     */
    public function hasQueryComponent($alias)
    {
        return isset($this->queryComponents[$alias]);
    }


    /**
     * Gets query component by query alias
     *
     * @param  string $alias
     * @throws QueryException
     * @return array
     */
    public function getQueryComponent($alias)
    {
        if ($this->hasQueryComponent($alias)) {
            return $this->queryComponents[$alias];
        }
        throw new QueryException("No query component for '$alias' defined!");
    }


    /**
     * Gets query components
     *
     * @return array
     */
    public function getQueryComponents()
    {
        return $this->queryComponents;
    }


    /**
     * Gets root query alias
     *
     * @return string
     */
    public function getRootAlias()
    {
        return $this->rootAlias;
    }


    /**
     * Gets root table
     *
     * @return \Dive\Table
     * @throws QueryException
     */
    public function getRootTable()
    {
        $alias = $this->rootAlias;
        if ($alias === null || !isset($this->queryComponents[$alias])) {
            throw new QueryException("Root table is not defined, yet!");
        }
        $tableName = $this->queryComponents[$alias]['table'];
        return $this->rm->getTable($tableName);
    }

}
