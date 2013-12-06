<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Util;


use Dive\Exception;

/**
 * @author  Steven Nikolic <steven@nindoo.de>
 * @created 06.12.13
 */
class PhpFormatter
{

    /**
     * @var string[]
     */
    public $usages = null;

    /**
     * @var null|string
     */
    private $extendFromClass = null;

    /**
     * @var null|string
     */
    private $fileComment = null;

    /**
     * @var string[]
     */
    private $annotations = array();

    /**
     * @var string[]
     */
    private $properties = array();

    /**
     * @var string
     */
    private $eol = PHP_EOL;


    /**
     * @param string      $fullClass
     * @param string|null $content
     * @throws Exception
     * @return string
     */
    public function getClassFile($fullClass, $content = null)
    {
        list($class, $namespace) = $this->splitClassAndNamespace($fullClass);
        if (!$class) {
            throw new Exception("no className found in: $fullClass");
        }

        $text = "<?php" . $this->eol;
        if ($this->fileComment) {
            $fileComment = $this->formatBlockComment($this->fileComment);
            $text .= $fileComment . $this->eol . $this->eol;
        }
        if ($namespace) {
            $text .= "namespace {$namespace};" . $this->eol . $this->eol;
        }

        if ($this->usages || $this->extendFromClass) {
            $usages = $this->usages;
            if ($this->extendFromClass) {
                array_unshift($usages, $this->extendFromClass);
            }
            $usages = array_unique($usages);
            foreach ($usages as $usage) {
                list($useClass, $useNameSpace) = $this->splitClassAndNamespace($usage);
                $text .= "use {$useNameSpace}\\{$useClass};" . $this->eol;
            }
            $text .= $this->eol;
        }
        $formatAnnotations = $this->formatAnnotations();
        if ($formatAnnotations) {
            $textAnnotationsComment = $this->formatDocComment($formatAnnotations);
            $text .= $textAnnotationsComment . $this->eol;
        }
        $text .= "class {$class}";
        if ($this->extendFromClass) {
            list($extendFromClass) = $this->splitClassAndNamespace($this->extendFromClass);
            $text .= " extends {$extendFromClass}";
        }
        $content = $content ? : $this->eol . $this->eol;
        $text .= $this->eol . '{' . $content . '}';
        return $text;
    }


    /**
     * @param string $modelClass
     * @return string[]
     */
    public function splitClassAndNamespace($modelClass)
    {
        $classParts = explode('\\', $modelClass);
        array_shift($classParts);
        $class = array_pop($classParts);
        $namespace = implode('\\', $classParts);
        return array($class, $namespace);
    }


    /**
     * @param string $text
     * @return string
     */
    public function formatBlockComment($text)
    {
        return $this->formatComment($text, "/" . '*' . $this->eol, ' *', $this->eol . ' */');
    }


    /**
     * @param string $text
     * @param string $prefix
     * @param string $inlineCommentStart
     * @param string $suffix
     * @return string
     */
    private function formatComment($text, $prefix = '//', $inlineCommentStart = '//', $suffix = '')
    {
        $text = preg_replace('/^(.*?)$/m', $inlineCommentStart . ' $1', $text);
        $text = StringExplode::trimMultiLines($text, 'rTrim', $this->eol);
        return "{$prefix}{$text}{$suffix}";
    }


    /**
     * @return string
     */
    public function formatAnnotations()
    {
        $lines = array();
        if ($this->annotations) {
            $maxLength = $this->getMaxKeyStrLength($this->annotations);
            foreach ($this->annotations as $name => $type) {
                $lines[] = $this->formatAnnotation($name, $type, null, $maxLength);
            }
        }
        if ($this->annotations && $this->properties) {
            $lines[] = '';
        }
        if ($this->properties) {
            $maxLength = $this->getMaxKeyStrLength($this->properties);
            foreach ($this->properties as $annotation) {
                $lines[] = $this->formatAnnotation("property", $annotation[0], $annotation[1], $maxLength);
            }
        }
        return implode($this->eol, $lines);
    }



    /**
     * @param array $annotations
     * @return int
     */
    private function getMaxKeyStrLength(array $annotations)
    {
        $maxLength = 0;
        foreach ($annotations as $name => $type) {
            $length = strlen($name);
            if ($length > $maxLength) {
                $maxLength = $length;
            }
        }
        return $maxLength;
    }


    /**
     * @param string      $name
     * @param string      $type
     * @param string|null $variable
     * @param int         $length
     * @return string
     */
    private function formatAnnotation($name, $type, $variable = null, $length = 0)
    {
        if ($length) {
            $name = str_pad($name, $length, ' ', STR_PAD_RIGHT);
        }
        $text = "@{$name} {$type}";
        if ($variable) {
            $text .= " \${$variable}";
        }
        return $text;
    }


    /**
     * @param string $text
     * @return string
     */
    public function formatDocComment($text)
    {
        return $this->formatComment($text, "/" . '**' . $this->eol, ' *', $this->eol . ' */');
    }


    /**
     * @param string $extendedFromClass
     * @return $this
     */
    public function setExtendFrom($extendedFromClass)
    {
        $this->extendFromClass = $extendedFromClass;
        return $this;
    }


    /**
     * @return $this
     */
    public function resetUsages()
    {
        $this->usages = array();
        return $this;
    }


    /**
     * @param string[] $usages
     * @return $this
     */
    public function addUsages(array $usages)
    {
        $this->usages = array_merge($this->usages, $usages);
        return $this;
    }


    /**
     * @param $properties
     * @return $this
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
        return $this;
    }


    /**
     * @param $fileComment
     * @return $this
     */
    public function setFileComment($fileComment)
    {
        $this->fileComment = $fileComment;
        return $this;
    }


    /**
     * @param $eol
     * @return $this
     */
    public function setEndOfLine($eol)
    {
        $this->eol = $eol;
        return $this;
    }


    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function addAnnotation($name, $value)
    {
        $this->annotations[$name] = $value;
        return $this;
    }


    /**
     * @return $this
     */
    public function resetAnnotations()
    {
        $this->annotations = array();
        return $this;
    }
}