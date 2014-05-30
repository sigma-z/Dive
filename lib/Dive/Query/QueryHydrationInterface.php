<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Query;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 01.03.13
 */
interface QueryHydrationInterface
{

    /**
     * @return int
     */
    public function count();


    /**
     * @return int
     */
    public function countByPk();


    /**
     * @return bool
     */
    public function hasResult();


    /**
     * Gets fetch mode
     *
     * @return string
     */
    public function getFetchMode();


    /**
     * Sets fetch mode
     *
     * @param  string $fetchMode
     * @return $this
     */
    public function setFetchMode($fetchMode);


    /**
     * Executes the query and returns hydrated result
     *
     * @param  string $fetchMode
     * @return mixed
     */
    public function execute($fetchMode = null);


    /**
     * Fetches the query result as record collection
     *
     * @return \Dive\Collection\RecordCollection
     */
    public function fetchObjects();


    /**
     * Fetches the query result as record instance
     *
     * @return \Dive\Record|bool   Returns false, if result is empty
     */
    public function fetchOneAsObject();


    /**
     * Fetches the query result as array
     *
     * @return array
     */
    public function fetchArray();


    /**
     * Fetches the query result as single array
     *
     * @return array|bool   Returns false, if result is empty
     */
    public function fetchOneAsArray();


    /**
     * Fetches the query result as single scalar
     *
     * @return string|bool   Returns false, if result is empty
     */
    public function fetchSingleScalar();


    /**
     * Fetches the query result as array of scalars
     *
     * @return array
     */
    public function fetchScalars();

}