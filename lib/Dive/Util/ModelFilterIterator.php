<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Util;


use RecursiveFilterIterator;
use RecursiveIterator;

/**
 * @author  Steven Nikolic <steven@nindoo.de>
 * @created 29.11.13
 */
class ModelFilterIterator extends RecursiveFilterIterator
{

    /**
     * @var string
     */
    private $pathNameFilterExpression = "/.+/";

    /**
     * @var string
     */
    private $fileNameAllowExpression = "/.+/";

    /**
     * @var string[]
     */
    private $hiddenDirectories = array('.svn', '.git');

    /**
     * @var string[]
     */
    private $allowedExtensions = array('.php');


    /**
     * @param RecursiveIterator $iterator
     */
    public function __construct(RecursiveIterator $iterator)
    {
        $this->setPathNameFilter();
        $this->setAllowedExtensions();

        parent::__construct($iterator);
    }


    /**
     * @param array $hiddenDirectories
     */
    public function setPathNameFilter(array $hiddenDirectories = null)
    {
        $hiddenDirectories = $hiddenDirectories == null ? $this->hiddenDirectories : $hiddenDirectories;
        $separator = DIRECTORY_SEPARATOR;
        $hiddenDirectories = implode('|', $hiddenDirectories);

        $this->pathNameFilterExpression = "/\\{$separator}({$hiddenDirectories})/";
    }


    /**
     * @param array $allowedExtensions
     */
    public function setAllowedExtensions(array $allowedExtensions = null)
    {
        $allowedExtensions = $allowedExtensions == null ? $this->allowedExtensions : $allowedExtensions;
        $allowedExtensions = implode('|', $allowedExtensions);
        $this->fileNameAllowExpression = "/[^.]+({$allowedExtensions})/";
    }


    /**
     * @return bool
     */
    public function accept()
    {
        return !(preg_match($this->pathNameFilterExpression, $this->current()->getPathname()))
            && preg_match($this->fileNameAllowExpression, $this->current()->getFileName());
    }

}