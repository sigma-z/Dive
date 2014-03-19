<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Dive\Console\Command;

use Dive\Console\ConsoleException;

/**
 * Class Command
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
abstract class Command
{

    /** @var string */
    protected $description = '';

    /** @var array */
    protected $requiredParams = array();

    /** @var array */
    protected $optionalParams = array();

    /**@var array */
    protected $params = array();


    /**
     * @return bool
     */
    abstract public function execute();


    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }


    /**
     * @return array
     */
    public function getRequiredParams()
    {
        return $this->requiredParams;
    }


    /**
     * @return array
     */
    public function getOptionalParams()
    {
        return $this->optionalParams;
    }


    /**
     * @return string
     */
    public function getName()
    {
        $name = get_class($this);
        $pos = strrpos($name, '\\');
        if ($pos !== false) {
            $name = substr($name, $pos);
        }
        return $name;
    }


    /**
     * @param string $name
     * @param mixed  $value
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }


    /**
     * @param  string $name
     * @return mixed
     */
    public function getParam($name)
    {
        return isset($this->params[$name]) ? $this->params[$name] : null;
    }


    /**
     * @param  array $params
     * @throws \Dive\Console\ConsoleException
     */
    public function setParams(array $params)
    {
        foreach ($this->requiredParams as $name => $value) {
            if (!array_key_exists($name, $params)) {
                $commandName = $this->getName();
                throw new ConsoleException("Missing required parameter '$name' for command '$commandName'!");
            }
        }
        $this->params = $params;
    }


    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

}
