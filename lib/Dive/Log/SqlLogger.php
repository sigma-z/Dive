<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 18.03.13
 */


namespace Dive\Log;


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


    public function startQuery($sql, array $params = array())
    {
        if (!$this->enabled) {
            return;
        }
        $this->startTime = microtime(true);
        $this->queries[++$this->current] = array(
            'sql' => $sql,
            'params' => $params,
            'executionTime' => 0
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


    public function dumpQuery($pos)
    {
        $queryData = $this->queries[$pos];
        echo "SQL [$pos]\n";
        echo $queryData['sql'] . "\n";
        echo "Params:\n";
        var_dump($queryData['params']);
        echo 'executed in ' . number_format($queryData['executionTime'], 5) . "\n\n";
    }


    public function clear()
    {
        $this->queries = array();
        $this->current = 0;
    }


    public function getQueries()
    {
        return $this->queries;
    }


    public function setEchoOutput($echoOutput = true)
    {
        $this->echo = $echoOutput;
    }


    public function disable($disable = true)
    {
        $this->enabled = ($disable !== true);
    }


    public function enabled()
    {
        return $this->enabled;
    }


    public function count()
    {
        return $this->current + 1;
    }

}
