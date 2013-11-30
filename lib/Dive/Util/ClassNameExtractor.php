<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Util;

/**
 * @author  Steven Nikolic <steven@nindoo.de>
 * @created 30.11.13
 * @link http://stackoverflow.com/a/11070559
 */
class ClassNameExtractor
{

    /**
     * @var string|null
     */
    private $subClassFilter = null;

    /**
     * @var array
     */
    private $tokens = array();

    /**
     * @var bool
     */
    private $dlm = false;

    /**
     * @var null|string
     */
    private $namespace = null;


    /**
     * gets class defined in file only if it extends a record
     * @param $fileName
     * @return string[]
     */
    public function getClasses($fileName)
    {
        // reset processing
        $this->namespace = null;
        $this->dlm = false;
        $this->tokens = array();
        $this->getTokenFromFile($fileName);

        // process
        $classes = array();
        foreach ($this->tokens as $i => $token) {
            $this->findNameSpace($i);
            if ($this->isClass($i)) {
                $fullClassName = "\\" . $this->namespace . "\\" . $this->tokens[$i][1];
                if ($this->acceptAsSubClass($fullClassName)) {
                    $classes[] = $fullClassName;
                }
            }
        }
        return $classes;
    }


    /**
     * filter by className(s)
     * @param array|string|null $className
     */
    public function setSubClassFilter($className = null)
    {
        if (is_array($className)) {
            $this->subClassFilter = $className;
        }
        else if ($className) {
            $this->subClassFilter = array($className);
        }
        else {
            $this->subClassFilter = null;
        }
    }


    /**
     * @param string $fullClassName
     * @return bool
     */
    private function acceptAsSubClass($fullClassName)
    {
        if (empty($this->subClassFilter)) {
            return true;
        }
        foreach ($this->subClassFilter as $filter) {
            if (is_subclass_of($fullClassName, $filter)) {
                return true;
            }
        }
        return false;
    }


    /**
     * @param int $i
     */
    private function findNameSpace($i)
    {
        $isPrependedByNameSpace = isset($this->tokens[$i - 2][1]) && ($this->tokens[$i - 2][1] == "phpnamespace" || $this->tokens[$i - 2][1] == "namespace");
        if ($isPrependedByNameSpace || ($this->dlm && isset($this->tokens[$i - 1][0]) && $this->tokens[$i - 1][0] == T_NS_SEPARATOR && $this->tokens[$i][0] == T_STRING)) {
            if (!$this->dlm) {
                $this->namespace = null;
            }
            if (isset($this->tokens[$i][1])) {
                if ($this->namespace) {
                    $this->namespace .= "\\";
                }
                $this->namespace .= $this->tokens[$i][1];
                $this->dlm = true;
            }
        }
        elseif ($this->dlm && isset($this->tokens[$i][0]) && $this->tokens[$i][0] != T_NS_SEPARATOR && $this->tokens[$i][0] != T_STRING) {
            $this->dlm = false;
        }
    }


    /**
     * @param int $position
     * @return bool
     */
    private function isClass($position)
    {
        $isPrependedByClass = (isset($this->tokens[$position - 2][0]) && $this->tokens[$position - 2][0] == T_CLASS)
            || (isset($this->tokens[$position - 2][1]) && $this->tokens[$position - 2][1] == "phpclass");
        return ($isPrependedByClass && $this->tokens[$position - 1][0] == T_WHITESPACE && $this->tokens[$position][0] == T_STRING);
    }


    /**
     * @param $fileName
     * @return array
     */
    private function getTokenFromFile($fileName)
    {
        $phpCode = file_get_contents($fileName);
        $this->tokens = token_get_all($phpCode);
    }

}