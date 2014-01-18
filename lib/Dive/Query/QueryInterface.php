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

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 10.02.13
 *
 * TODO questions
 * Should we rather provide a reset(Part*)() method and not provide add(Part*) methods?
 * Should we split this interface? And how?
 */
interface QueryInterface
{

    // <editor-fold desc="select part">
    /**
     * Sets select part of query
     *
     * @param  string        $select
     * @param  array|string  $params
     * @return $this
     */
    public function select($select, $params = array());


    /**
     * Add to select part of query
     *
     * @param  string        $select
     * @param  array|string  $params
     * @return $this
     */
    public function addSelect($select, $params = array());


    /**
     * Sets distinct flag for query
     *
     * @param  bool $distinct
     * @return $this
     */
    public function distinct($distinct = true);


    /**
     * Sets for update part for query
     *
     * @param  bool $forUpdate
     * @return $this
     */
    public function forUpdate($forUpdate = true);
    // </editor-fold>

    // <editor-fold desc="from part">
    /**
     * Sets from part for query
     *
     * @param  string        $from
     * @param  array|string  $params
     * @return $this
     */
    public function from($from, $params = array());


    /**
     * Adds left join for query
     *
     * @param  string        $leftJoin
     * @param  array|string  $params
     * @return $this
     */
    public function leftJoin($leftJoin, $params = array());


    /**
     * Adds left join with a specific on-clause
     *
     * @param  string $leftJoin
     * @param  string $onClause
     * @param  array  $params
     * @return $this
     */
    public function leftJoinOn($leftJoin, $onClause, $params = array());


    /**
     * Adds left join with an extended on-clause (a so called with-clause)
     * @param  string $leftJoin
     * @param  string $withClause
     * @param  array  $params
     * @return $this
     */
    public function leftJoinWith($leftJoin, $withClause, $params = array());
    // </editor-fold>

    // <editor-fold desc="where part">
    /**
     * Sets where part for query
     *
     * @param   string       $expr
     * @param   array|string $params
     * @return  $this
     */
    public function where($expr, $params = array());


    /**
     * Adds where part connected with logical AND for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function andWhere($expr, $params = array());


    /**
     * Adds where part connected with logical OR for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function orWhere($expr, $params = array());


    /**
     * Sets where part with IN clause for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function whereIn($expr, $params = array());


    /**
     * Sets where part with NOT IN clause for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function whereNotIn($expr, $params = array());


    /**
     * Adds where part with IN clause connected with logical AND for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function andWhereIn($expr, $params = array());


    /**
     * Adds where part with NOT IN clause connected with logical AND for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function andWhereNotIn($expr, $params = array());


    /**
     * Adds where part with IN clause connected with logical OR for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function orWhereIn($expr, $params = array());


    /**
     * Adds where part with NOT IN clause connected with logical OR for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function orWhereNotIn($expr, $params = array());
    // </editor-fold>

    // <editor-fold desc="group by part">
    /**
     * Sets group by part for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function groupBy($expr, $params = array());


    /**
     * Adds group by part for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function addGroupBy($expr, $params = array());
    // </editor-fold>

    // <editor-fold desc="having part">
    /**
     * Sets having part for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function having($expr, $params = array());


    /**
     * Adds having part connected with logical AND for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function andHaving($expr, $params = array());


    /**
     * Adds having part connected with logical OR for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function orHaving($expr, $params = array());
    // </editor-fold>

    // <editor-fold desc="order by part">
    /**
     * Set order by part for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function orderBy($expr, $params = array());


    /**
     * Adds order by part for query
     *
     * @param  string       $expr
     * @param  array|string $params
     * @return $this
     */
    public function addOrderBy($expr, $params = array());
    // </editor-fold>

    // <editor-fold desc="limit/offset">
    /**
     * Sets query limit
     *
     * @param  int $limit
     * @return $this
     */
    public function limit($limit);

    /**
     * Sets query offset
     *
     * @param  int $offset
     * @return $this
     */
    public function offset($offset);

    /**
     * Gets query limit
     *
     * @return int
     */
    public function getLimit();

    /**
     * Gets query offset
     *
     * @return int
     */
    public function getOffset();
    // </editor-fold>


    // <editor-fold desc="core">
    public function getSql();

    /**
     * Gets query params for a given query part
     *
     * @param  string $part
     * @return array
     */
    public function getParams($part);


    /**
     * Gets all query params as flattened array
     *
     * @return array
     */
    public function getParamsFlattened();


    /**
     * Sets query params for a given query part
     *
     * @param  string $part
     * @param  array  $params
     * @return $this
     */
    public function setParams($part, array $params);


    /**
     * Gets query parts
     *
     * @return array[]
     */
    public function getQueryParts();


    /**
     * Gets a specific query part
     *
     * @param  string $part
     * @return array
     */
    public function getQueryPart($part);


    /**
     * Returns true, if query has a query component for the given query alias
     *
     * @param  string $alias
     * @return bool
     */
    public function hasQueryComponent($alias);


    /**
     * Gets query component by query alias
     *
     * @param  string $alias
     * @return array
     */
    public function getQueryComponent($alias);


    /**
     * Gets query components
     *
     * @return array
     */
    public function getQueryComponents();

    /**
     * Gets root query alias
     *
     * @return string
     */
    public function getRootAlias();


    /**
     * Gets root table
     *
     * @return \Dive\Table
     */
    public function getRootTable();


    /**
     * Gets record manager
     *
     * @return RecordManager
     */
    public function getRecordManager();
    // </editor-fold>

}
