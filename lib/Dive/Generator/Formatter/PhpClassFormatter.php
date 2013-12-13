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
use Dive\Util\ClassNameExtractor;
use Dive\Util\StringExplode;

/**
 * @author  Steven Nikolic <steven@nindoo.de>
 * @created 06.12.13
 */
class PhpClassFormatter implements FormatterInterface
{

    const FILE_EXTENSION = 'php';

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
    public function getFileContent($fullClass, $content = null)
    {
        $class = ClassNameExtractor::splitClass($fullClass);
        if (!$class) {
            throw new Exception("no className found in: $fullClass");
        }

        $text = "<?php" . $this->eol;
        if ($this->fileComment) {
            $fileComment = $this->formatBlockComment($this->fileComment);
            $text .= $fileComment . $this->eol . $this->eol;
        }
        $namespace = ClassNameExtractor::splitNamespace($fullClass);
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
                $useClass = ClassNameExtractor::splitClass($usage);
                $useNameSpace = ClassNameExtractor::splitNamespace($usage);
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
            $extendFromClass = ClassNameExtractor::splitClass($this->extendFromClass);
            $text .= " extends {$extendFromClass}";
        }
        $content = $content ? : $this->eol . $this->eol;
        $text .= $this->eol . '{' . $content . '}';
        return $text;
    }


    /**
     * @param string $fullClass
     * @param string $targetDirectory
     * @return string
     */
    public function getTargetFileName($fullClass, $targetDirectory)
    {
        $className = ClassNameExtractor::splitClass($fullClass);
        return $targetDirectory . DIRECTORY_SEPARATOR . $className . '.' . self::FILE_EXTENSION;
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
                $lines[] = $this->formatAnnotationParam($name, $type, null, $maxLength);
            }
        }
        if ($this->annotations && $this->properties) {
            $lines[] = '';
        }
        if ($this->properties) {
            $maxLength = $this->getMaxKeyStrLength($this->properties);
            foreach ($this->properties as $annotation) {
                $lines[] = $this->formatAnnotationParam("property", $annotation[0], $annotation[1], $maxLength);
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
    private function formatAnnotationParam($name, $type, $variable = null, $length = 0)
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
    public function setExtendedFrom($extendedFromClass)
    {
        $this->extendFromClass = $extendedFromClass;
        return $this;
    }


    /**
     * @param string[] $usages
     * @return $this
     */
    public function setUsages(array $usages)
    {
        $this->usages = $usages;
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
    public function setAnnotation($name, $value)
    {
        $this->annotations[$name] = $value;
        return $this;
    }


    /**
     * @param array $annotations
     * @return $this
     */
    public function setAnnotations($annotations = array())
    {
        $this->annotations = $annotations;
        return $this;
    }
}