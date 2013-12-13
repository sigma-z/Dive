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
     * @var string[]
     */
    private $classes = array();

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
    private $nameSpacePartsFollowing = false;

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
        $this->nameSpacePartsFollowing = false;
        $this->classes = array();
        $this->tokens = $this->getTokensFromFile($fileName);

        // process
        foreach ($this->tokens as $index => $token) {
            $this->processToken($index);
        }
        return $this->classes;
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
     * @param int $index
     */
    private function processToken($index)
    {
        // extract namespace
        if ($this->isTokenContent($index - 2, 'phpnamespace')
            || $this->isTokenContent($index - 2, 'namespace')
            || ($this->nameSpacePartsFollowing
                && $this->isTokenIdentifier($index - 1, T_NS_SEPARATOR)
                && $this->isTokenIdentifier($index, T_STRING)
            )
        ) {
            if (!$this->nameSpacePartsFollowing) {
                $this->namespace = null;
            }
            if (isset($this->tokens[$index][1])) {
                if ($this->namespace) {
                    $this->namespace .= "\\";
                }
                $this->namespace .= $this->tokens[$index][1];
                $this->nameSpacePartsFollowing = true;
            }
        }
        else if ($this->nameSpacePartsFollowing
            && !$this->isTokenIdentifier($index, T_NS_SEPARATOR)
            && !$this->isTokenIdentifier($index, T_STRING)
        ) {
            $this->nameSpacePartsFollowing = false;
        }

        // extract class
        if ($this->isTokenIdentifier($index - 1, T_WHITESPACE)
            && $this->isTokenIdentifier($index, T_STRING)
            && ($this->isTokenIdentifier($index - 2, T_CLASS) || $this->isTokenContent($index - 2, 'phpclass'))
            && isset($this->tokens[$index][1])
        ) {
            $fullClassName = "\\" . $this->namespace . "\\" . $this->tokens[$index][1];
            if ($this->acceptAsSubClass($fullClassName)) {
                $this->classes[] = $fullClassName;
            }
        }
    }


    /**
     * @param int $index
     * @param string $content
     * @return bool
     */
    private function isTokenContent($index, $content)
    {
        return isset($this->tokens[$index][1]) && $this->tokens[$index][1] == $content;
    }


    /**
     * @param int $index
     * @param int $identifier
     * @return bool
     */
    private function isTokenIdentifier($index, $identifier)
    {
        return isset($this->tokens[$index][0]) && $this->tokens[$index][0] == $identifier;
    }


    /**
     * @param string $fileName
     * @return array
     */
    public function getTokensFromFile($fileName)
    {
        $source = file_get_contents($fileName);
        return token_get_all($source);
    }


    /**
     * @param string $fullClass
     * @return string
     */
    public static function splitClass($fullClass)
    {
        $classParts = explode('\\', $fullClass);
        array_shift($classParts);
        $class = array_pop($classParts);
        return $class;
    }


    /**
     * @param string $fullClass
     * @return string
     */
    public static function splitNamespace($fullClass)
    {
        $classParts = explode('\\', $fullClass);
        array_shift($classParts);
        array_pop($classParts);
        $namespace = implode('\\', $classParts);
        return $namespace;
    }
}