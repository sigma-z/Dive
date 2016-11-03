<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Dive\Generator\Formatter;

use Dive\Exception;

/**
 * @author  Steven Nikolic <steven@nindoo.de>
 * @created 13.12.13
 */
interface FormatterInterface
{

    /**
     * @param string[] $usages
     * @return $this
     */
    public function setUsages(array $usages);


    /**
     * @param string $extendedFromClass
     * @return $this
     */
    public function setExtendedFrom($extendedFromClass);


    /**
     * @param array $annotations
     * @return $this
     */
    public function setAnnotations($annotations = array());


    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setAnnotation($name, $value);


    /**
     * @param $properties
     * @return $this
     */
    public function setProperties($properties);


    /**
     * @param string      $fullClass
     * @param string|null $content
     * @throws Exception
     * @return string
     */
    public function getFileContent($fullClass, $content = null);


    /**
     * @param $eol
     * @return $this
     */
    public function setEndOfLine($eol);


    /**
     * @param $fileComment
     * @return $this
     */
    public function setFileComment($fileComment);

    /**
     * @param string $fullClass
     * @param string $targetDirectory
     * @return string
     */
    public function getTargetFileName($fullClass, $targetDirectory);

}