<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Dive\Console\Command;

use Dive\Console\Console;
use Dive\Console\ConsoleException;
use Dive\Console\OutputWriterInterface;

/**
 * Class Command
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
abstract class Command
{

    /** @var Console */
    protected $console;

    /** @var string */
    protected $description = '';

    /** @var array */
    protected $requiredParams = array();

    /** @var array */
    protected $optionalParams = array();

    /**@var array */
    protected $params = array();


    /**
     * @param \Dive\Console\OutputWriterInterface $outputWriter
     * @return bool
     */
    abstract public function execute(OutputWriterInterface $outputWriter);


    /**
     * @param Console $console
     */
    public function setConsole(Console $console)
    {
        $this->console = $console;
    }


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
    public function getUsage()
    {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $paramList = '';
        $requiredParams = $this->getRequiredParams();
        if ($requiredParams) {
            $paramList = '<' . implode('> <', array_keys($requiredParams)) . '>';
        }
        $optionalParams = $this->getOptionalParams();
        if ($optionalParams) {
            $paramList .= '[<' . implode('>] [<', array_keys($optionalParams)) . '>]';
        }
        $commandName = $this->getName();
        return
            $this->getDescription() . "\n\n"
            . "USAGE: $scriptName $commandName $paramList\n\n"
            . $this->getParamsDescription($requiredParams, 'Required params:')
            . $this->getParamsDescription($this->getOptionalParams(), 'Optional params:');
    }


    /**
     * @param  array  $params
     * @param  string $label
     * @return string
     */
    private function getParamsDescription(array $params, $label)
    {
        if ($params) {
            $string = "$label\n";
            foreach ($params as $paramName => $paramDescription) {
                $string .= "  $paramName: $paramDescription\n";
            }
            return $string;
        }
        return '';
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->getNameByClassName(get_class($this));
    }


    /**
     * @param  string $name
     * @return string
     */
    protected function getNameByClassName($name)
    {
        $pos = strrpos($name, '\\');
        if ($pos !== false) {
            $name = substr($name, $pos + 1);
        }
        if (substr($name, -7) == 'Command') {
            $name = substr($name, 0, -7);
        }
        return lcfirst($name);
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
     * @param  mixed  $default
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        return isset($this->params[$name]) ? $this->params[$name] : $default;
    }


    /**
     * @param  string $name
     * @param  bool   $default
     * @return bool
     */
    public function getBooleanParam($name, $default = null)
    {
        $value = $this->getParam($name, $default);
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return $this->isInputValueTrue($value);
        }
        if (is_int($value)) {
            return $value === 1;
        }
        return $default;
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


    /**
     * user interaction read from STDIN
     *
     * @param  string $message
     * @param  string $type
     * @return string
     */
    public function readInput($message, $type = 'string')
    {
        do {
            echo $message . ': ';
            $input = trim(fgets(STDIN));
        }
        while ($input === '');

        switch ($type) {
            case 'boolean':
                return $this->isInputValueTrue($input);
        }
        return $input;
    }


    /**
     * @param  string $input
     * @return bool
     */
    private function isInputValueTrue($input)
    {
        $input = strtolower($input);
        return $input === 'yes' || $input === 'on' || $input === 'true' || $input === '1';
    }

}
