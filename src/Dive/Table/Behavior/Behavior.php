<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Dive\Table\Behavior;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class Behavior
 *
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
abstract class Behavior implements EventSubscriberInterface
{

    /** @var array[] */
    protected $tableConfigs = array();


    /**
     * @param string $tableName
     * @param array  $config
     */
    public function setTableConfig($tableName, array $config)
    {
        $this->tableConfigs[$tableName] = $config;
    }


    /**
     * @param  string $tableName
     * @return array
     */
    public function getTableConfig($tableName)
    {
        return isset($this->tableConfigs[$tableName]) ? $this->tableConfigs[$tableName] : null;
    }


    /**
     * @param  string $tableName
     * @param  string $configName
     * @param  mixed  $value
     */
    public function setTableConfigValue($tableName, $configName, $value)
    {
        $this->tableConfigs[$tableName][$configName] = $value;
    }


    /**
     * @param  string $tableName
     * @param  string $configName
     */
    public function unsetTableConfigValue($tableName, $configName)
    {
        unset($this->tableConfigs[$tableName][$configName]);
    }


    /**
     * @param  string $tableName
     * @param  string $configName
     * @return mixed
     */
    public function getTableConfigValue($tableName, $configName)
    {
        return isset($this->tableConfigs[$tableName][$configName]) ? $this->tableConfigs[$tableName][$configName] : null;
    }


}
