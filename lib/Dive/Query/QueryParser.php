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
 * Date: 22.02.13
 */

namespace Dive\Query;


use Dive\Util\StringExplode;

class QueryParser
{

    /**
     * Parses left join
     * @example: a.SampleRelationName b will be parsed to parentAlias: a, relationName: SampleRelationName, alias: b
     *
     * @param  string         $leftJoin
     * @param  QueryInterface $query
     * @throws QueryException
     * @return array
     */
    public function parseLeftJoin($leftJoin, QueryInterface $query)
    {
        $posParentAlias = strpos($leftJoin, '.');
        if ($posParentAlias === false) {
            throw new QueryException("Left join $leftJoin misses parent alias!");
        }
        $posAlias = strpos($leftJoin, ' ');
        if ($posAlias === false) {
            throw new QueryException("Left join $leftJoin misses alias!");
        }
        $explodePositions = array($posParentAlias, $posAlias);
        list($parentAlias, $relationName, $alias) = StringExplode::explodeAt($leftJoin, $explodePositions);

        if (!$query->hasQueryComponent($parentAlias)) {
            throw new QueryException("Missing parent alias '$parentAlias' in query!");
        }
        if ($query->hasQueryComponent($alias)) {
            throw new QueryException("Duplicate alias '$alias' in query!");
        }
        $parent = $query->getQueryComponent($parentAlias);
        $parentTableName = $parent['table'];
        $parentTable = $query->getRecordManager()->getTable($parentTableName);
        $relation = $parentTable->getRelation($relationName);

        return array(
            'parent' => $parentAlias,
            'alias' => $alias,
            'relation' => $relationName,
            'table' => $relation->getJoinTableName($relationName)
        );
    }


    /**
     * Parses query
     *
     * @param  \Dive\Query\QueryInterface $query
     * @return string
     */
    public function parseQuery(QueryInterface $query)
    {
        $sqlParts =  $this->getSqlParts($query);
        $sql = implode("\n", $sqlParts);

        return $sql;
    }


    protected function getSqlParts(QueryInterface $query)
    {
        // query parts
        $queryParts = $query->getQueryParts();

        // query select
        $select = 'SELECT ' . $this->parseQuerySelect($query);
        // query from
        $from   = '  FROM ' . implode("\n", $queryParts['from']);
        $sqlParts = array(
            'select' => $select,
            'from' => $from
        );

        if (!empty($queryParts['where'])) {
            $sqlParts['where'] = ' WHERE ' . implode(' AND ', $queryParts['where']);
        }
        if (!empty($queryParts['groupBy'])) {
            $sqlParts['groupBy'] = ' GROUP BY ' . implode(', ', $queryParts['groupBy']);
        }
        if (!empty($queryParts['having'])) {
            $sqlParts['having'] = ' HAVING ' . implode(' AND ', $queryParts['having']);
        }
        if (!empty($queryParts['orderBy'])) {
            $sqlParts['orderBy'] = ' ORDER BY ' . implode(', ', $queryParts['orderBy']);
        }
        if ($queryParts['forUpdate'] === true) {
            $sqlParts['forUpdate'] = 'FOR UPDATE ';
        }
        return $sqlParts;
    }


    /**
     * Parses query select
     *
     * @param  QueryInterface $query
     * @return string
     */
    private function parseQuerySelect(QueryInterface $query)
    {
        $selectParts = $query->getQueryPart('select');
        $select = '';
        if (empty($selectParts)) {
            $rootAlias = $query->getRootAlias();
            $rootTable = $query->getRootTable();
            $rm = $query->getRecordManager();
            $conn = $rm->getConnection();
            foreach ($rootTable->getFields() as $fieldName => $def) {
                $select .= $conn->quoteIdentifier($rootAlias) . '.' . $conn->quoteIdentifier($fieldName) . ', ';
            }
            if ($select) {
                $select = substr($select, 0, -2);
            }
        }
        else {
            $select = implode(', ', $selectParts);
        }
        if ($query->getQueryPart('distinct') === true) {
            $select = 'DISTINCT ' . $select;
        }
        return $select;
    }

}