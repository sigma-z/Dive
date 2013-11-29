<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive;


use Dive\Schema\Schema;
use Dive\Util\ModelFilterIterator;
use Dive\Util\StringExplode;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @author  Steven Nikolic <steven@nindoo.de>
 * @created 29.11.13
 */
class ModelGenerator
{

    /**
     * @var RecordManager|null
     */
    private $rm = null;


    /**
     * @param RecordManager $rm
     */
    public function __construct(RecordManager $rm)
    {
        $this->rm = $rm;
    }


    /**
     * @param string $targetDirectory
     * @return string[]
     */
    public function getExistingModelClasses($targetDirectory)
    {
        $dirReader = new RecursiveDirectoryIterator($targetDirectory);
        $filterIterator = new ModelFilterIterator($dirReader);
        $iteratorIterator = new RecursiveIteratorIterator($filterIterator);

        $classes = array();
        /** @var $model SplFileInfo */
        foreach ($iteratorIterator as $model) {
            $fileName = $model->getPath() . DIRECTORY_SEPARATOR . $model->getFilename();
            $classNameFromFile = $this->getRecordClassNameFromFile($fileName);
            if ($classNameFromFile) {
                $classes[] = $classNameFromFile;
            }

        }
        return $classes;
    }


    /**
     * gets class defined in file only if it extends a record
     * @param string $fileName
     * @return string|null
     */
    public function getRecordClassNameFromFile($fileName)
    {
        /** @noinspection PhpIncludeInspection */
        include $fileName;
        $declaredClasses = get_declared_classes();
        $className = "\\" . end($declaredClasses);
        /** @var $class Record */
        $class = new $className(new Table($this->rm, $className, $className, array()));
        if ($class instanceof Record) {
            return $className;
        }
        return null;
    }


    /**
     * @param Schema $schema
     * @return array
     */
    public function getNeededModels(Schema $schema)
    {
        $tableNames = $schema->getTableNames();
        foreach ($tableNames as $key => $tableName) {
            $tableNames[$key] = $schema->getRecordClass($tableName);
        }
        return $tableNames;
    }


    /**
     * @param string              $modelClassName
     * @param string              $date
     * @param string              $author
     * @param Schema $schema
     * @param string              $eol End of Line
     * @return string
     */
    public function createClassFile($modelClassName, $date, $author, Schema $schema, $eol = PHP_EOL)
    {
        $license = $this->getLicense($eol);
        list($className, $namespace) = $this->splitClassAndNamespace($modelClassName);
        $properties = $this->getProperties($modelClassName, $schema);
        $annotations = $this->getAnnotations($properties, $date, $author, $eol);
        return $this->formatClassFile($className, $namespace, $annotations, $license, $eol);
    }


    /**
     * @param string $eol
     * @return string
     */
    public function getLicense($eol)
    {
        return "This file is part of the Dive ORM framework." . $eol
            . "(c) Steffen Zeidler <sigma_z@sigma-scripts.de>" . $eol
            . $eol
            . "For the full copyright and license information, please view the LICENSE" . $eol
            . "file that was distributed with this source code.";
    }


    /**
     * @param $modelClassName
     * @return array
     */
    public function splitClassAndNamespace($modelClassName)
    {
        $classParts = explode('\\', $modelClassName);
        array_shift($classParts);
        $className = array_pop($classParts);
        $namespace = implode('\\', $classParts);
        return array($className, $namespace);
    }


    /**
     * @param string $modelClassName
     * @param Schema $schema
     * @return array
     */
    private function getProperties($modelClassName, Schema $schema)
    {
        $tableFields = $this->getTableFieldsByClassName($modelClassName, $schema);
        if (!$tableFields) {
            return array();
        }
        $fields = array();
        foreach ($tableFields as $key => $tableField) {
            $fields[] = array($tableField['type'], $key);
        }
        $fields[] = array('Article', 'Article');
        $fields[] = array('Tag', 'Tag');
        return $fields;
    }


    /**
     * @param string $modelClassName
     * @param Schema $schema
     * @return array
     */
    private function getTableFieldsByClassName($modelClassName, Schema $schema)
    {
        $tableNames = $schema->getTableNames();
        foreach ($tableNames as $tableName) {
            if ($modelClassName == $schema->getRecordClass($tableName)) {
               return $schema->getTableFields($tableName);
            }
        }
        return null;
    }


    /**
     * @param array  $properties
     * @param string $date
     * @param string $author
     * @param string $eol
     * @return string
     */
    public function getAnnotations(array $properties, $date, $author, $eol = PHP_EOL)
    {
        $annotations = array(
            array('author ', $author),
            array('created', $date),
        );
        $lines = array();
        foreach ($annotations as $annotation) {
            $name = $annotation[0];
            $type = $annotation[1];
            $lines[] = "@${name} {$type}";
        }
        $lines[] = '';
        foreach ($properties as $annotation) {
            $type = $annotation[0];
            $variable = $annotation[1];
            $lines[] = "@property {$type} \${$variable}";
        }
        return implode($eol, $lines);
    }


    /**
     * @param string $className
     * @param string $namespace
     * @param string $annotations
     * @param string $license
     * @param string $eol
     * @return string
     */
    private function formatClassFile($className, $namespace, $annotations, $license, $eol = PHP_EOL)
    {
        $useModelClassName = '\Dive\Record';
        list($useClass, $useNamespace) = $this->splitClassAndNamespace($useModelClassName);
        return '<?php' . $eol
            . $this->formatComment($license, false, $eol) . $eol
            . "namespace {$namespace};{$eol}{$eol}"
            . "use {$useNamespace}\\{$useClass};{$eol}{$eol}"
            . $this->formatComment($annotations, true, $eol)
            . "class {$className} extends {$useClass}{$eol}{{$eol}{$eol}}";
    }


    /**
     * @param string $text
     * @param bool   $isDocComment
     * @param string $eol
     * @return string
     */
    private function formatComment($text, $isDocComment = false, $eol = PHP_EOL)
    {
        $text = preg_replace('/^(.*?)$/m', ' * $1', $text);
        $text = StringExplode::trimMultiLines($text, 'rtrim', $eol);
        $commentStart = $isDocComment ? '**' : '*';
        return "/{$commentStart}{$eol}{$text}{$eol} */{$eol}";
    }
}
