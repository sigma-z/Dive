<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\TestSuite;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 30.10.12
 */

class ClassLoader
{

    const NAMESPACE_SEPARATOR = '\\';

    /**
     * class namespaces as hash (namespaces as keys and directories as values)
     * @var array
     */
    private $namespaces = array();
    /**
     * namespace omissions as array
     * @var array
     */
    private $namespaceOmissions = array();


    /**
     * Registers this ClassLoader on the SPL autoload stack.
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }


    /**
     * Removes this ClassLoader from the SPL autoload stack.
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }


    /**
     * sets directories for a namespace
     *
     * @param   string  $namespace
     * @param   array   $directory
     * @throws \InvalidArgumentException
     */
    public function setNamespace($namespace, $directory)
    {
        if (!is_readable($directory)) {
            throw new \InvalidArgumentException("Directory: '$directory' does not exist or is not readable!");
        }
        if ($namespace[0] == self::NAMESPACE_SEPARATOR) {
            $namespace = substr($namespace, 1);
        }
        $this->namespaces[$namespace] = $directory;
    }


    /**
     * set namespace to be suppressed
     *
     * @param string $omittedNamespace
     */
    public function setNamespaceOmission($omittedNamespace)
    {
        if ($omittedNamespace[0] == self::NAMESPACE_SEPARATOR) {
            $omittedNamespace = substr($omittedNamespace, 1);
        }
        if (!in_array($omittedNamespace, $this->namespaceOmissions)) {
            $this->namespaceOmissions[] = $omittedNamespace;
        }
    }


    /**
     * Loads the given class or interface.
     *
     * @param   string  $class
     * @return  boolean TRUE if the class has been successfully loaded, FALSE otherwise.
     */
    public function loadClass($class)
    {
        if (($classFile = $this->getClassFile($class))) {
            /** @noinspection PhpIncludeInspection */
            require $classFile;
            return class_exists($class, false);
        }
        return false;
    }


    /**
     * gets class file for a given class name
     *
     * @param   string $class
     * @return  string class file with path
     */
    private function getClassFile($class)
    {
        if (self::NAMESPACE_SEPARATOR == $class[0]) {
            $class = substr($class, 1);
        }

        $pos = strrpos($class, self::NAMESPACE_SEPARATOR);
        if ($pos === false) {
            return false;
        }

        // namespace of class
        $classNamespace = substr($class, 0, $pos);
        // class base name
        $className = substr($class, $pos + 1);

        foreach ($this->namespaces as $namespace => $directory) {
            if (0 === strpos($classNamespace, $namespace)) {
                $path = $this->getClassPath($classNamespace);
                $file = $directory . DIRECTORY_SEPARATOR . $path . $className . '.php';

                if (file_exists($file)) {
                    return $file;
                }
                return false;
            }
        }
        return false;
    }


    /**
     * gets relative path to class by class namespace
     *
     * @param string $classNamespace
     *
     * @return string
     */
    private function getClassPath($classNamespace)
    {
        $path = $classNamespace;
        // if namespace should be omitted, remove it from class namespace
        foreach ($this->namespaceOmissions as $namespaceOmission) {
            if (0 === strpos($classNamespace, $namespaceOmission)) {
                $path = substr($classNamespace, strlen($namespaceOmission) + 1);
                break;
            }
        }
        // replace namespace separator with directory separator
        if (!empty($path)) {
            $path = str_replace(self::NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR, $path);
            $path .= DIRECTORY_SEPARATOR;
        }
        return $path;
    }

}
