<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Dive\Table\Behaviour;

use Dive\Record;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class Behaviour
 *
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
abstract class Behaviour implements EventSubscriberInterface
{

    /** @var array */
    protected $tableEventFields = array();


    /**
     * @param string        $tableName
     * @param string        $eventName
     * @param array|string  $fields
     */
    public function addTableEventFields($tableName, $eventName, $fields)
    {
        $this->tableEventFields[$tableName][$eventName] = (array)$fields;
    }


    /**
     * @param  string $tableName
     * @param  string $eventName
     * @return array
     */
    public function getTableEventFields($tableName, $eventName)
    {
        if (!empty($this->tableEventFields[$tableName][$eventName])) {
            return $this->tableEventFields[$tableName][$eventName];
        }
        return array();
    }


    /**
     * Removes table field events
     *
     * @param string $tableName
     * @param string $eventName
     */
    public function clearTableEventFields($tableName, $eventName = null)
    {
        if ($eventName) {
            unset($this->tableEventFields[$tableName][$eventName]);
        }
        else {
            unset($this->tableEventFields[$tableName]);
        }
    }


}
