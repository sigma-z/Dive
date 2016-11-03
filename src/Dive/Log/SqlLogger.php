<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Log;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 18.03.13
 */
class SqlLogger
{

    /**
     * @var string[]
     */
    private $queries = array();
    /**
     * @var bool
     */
    private $enabled = true;
    /**
     * @var bool
     */
    private $echo = false;
    /**
     * @var int
     */
    private $current = -1;
    /**
     * @var string
     */
    private $startTime;


    /**
     * @param string $sql
     * @param array  $params
     */
    public function startQuery($sql, array $params = array())
    {
        if (!$this->enabled) {
            return;
        }
        $this->startTime = microtime(true);
        $this->queries[++$this->current] = array(
            'sql' => $sql,
            'params' => $params,
            'executionTime' => 0.0
        );
    }


    public function stopQuery()
    {
        if (!$this->enabled) {
            return;
        }
        $this->queries[$this->current]['executionTime'] = microtime(true) - $this->startTime;
        if ($this->echo) {
            $this->dumpQuery($this->current);
        }
    }


    /**
     * @param int $pos
     */
    public function dumpQuery($pos)
    {
        if (!$this->getCount()) {
            echo "no queries logged yet";
        }
        else if (!isset($this->queries[$pos])) {
            echo "Query $pos was not logged";
        }
        else {
            echo "SQL [$pos]\n";
            echo $this->queries[$pos]['sql'] . "\n";
            echo "Params:\n";
            var_dump($this->queries[$pos]['params']);
            echo 'executed in ' . number_format($this->queries[$pos]['executionTime'], 5);
        }
        echo "\n\n";
    }


    public function clear()
    {
        $this->queries = array();
        $this->current = 0;
    }


    /**
     * @return string[]
     */
    public function getQueries()
    {
        return $this->queries;
    }


    /**
     * @param bool $echoOutput
     */
    public function setEchoOutput($echoOutput = true)
    {
        $this->echo = $echoOutput;
    }


    /**
     * @param bool $disable
     */
    public function setDisabled($disable = true)
    {
        $this->enabled = ($disable !== true);
    }


    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }


    /**
     * @return int
     */
    public function getCount()
    {
        return $this->current + 1;
    }


    /**
     * @return float
     */
    public function getLastQueryExecutionTime()
    {
        if (!$this->getCount()) {
            return 0.0;
        }
        return $this->queries[$this->current]['executionTime'];
    }


    /**
     * @return float
     */
    public function getOverallQueryExectuionTime()
    {
        $time = 0.0;
        for ($pos = 0; $pos <= $this->current; $pos++) {
            $time += $this->queries[$pos]['executionTime'];
        }
        return $time;
    }
}
