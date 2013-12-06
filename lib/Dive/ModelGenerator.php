<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive;


use Dive\Relation\Relation;
use Dive\Schema\Schema;
use Dive\Util\ClassNameExtractor;
use Dive\Util\ModelFilterIterator;
use Dive\Util\PhpFormatter;
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
     * @var PhpFormatter
     */
    private $formatter = null;

    /**
     * @var string
     */
    private $date = '';

    /**
     * @var string
     */
    private $author = '';

    /**
     * @var RecordManager|null
     */
    private $recordManager = null;


    /**
     * @param RecordManager $recordManager
     * @return ModelGenerator
     */
    public function __construct(RecordManager $recordManager)
    {
        $this->recordManager = $recordManager;
        $this->date = date('d.m.y');

        $this->formatter = new PhpFormatter();
    }


    /**
     * @param string $targetDirectory
     * @throws Exception
     * @return string[]
     */
    public function getExistingModelClasses($targetDirectory)
    {
        if (!is_dir($targetDirectory)) {
            throw new Exception("target directory not accessible: $targetDirectory");
        }
        $dirReader = new RecursiveDirectoryIterator($targetDirectory);
        $filterIterator = new ModelFilterIterator($dirReader);
        $iteratorIterator = new RecursiveIteratorIterator($filterIterator);
        $extractor = new ClassNameExtractor();
        $extractor->setSubClassFilter('\Dive\Record');

        $classes = array();
        /** @var $model SplFileInfo */
        foreach ($iteratorIterator as $model) {
            $fileName = $model->getPath() . DIRECTORY_SEPARATOR . $model->getFilename();
            $classNameFromFile = $extractor->getClasses($fileName);
            $classes = array_merge($classes, $classNameFromFile);
        }
        return $classes;
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
     * @param string $modelClassName
     * @param Schema $schema
     * @param string $extendedClass
     * @param string $collectionClass
     * @return string
     */
    public function createClassFile(
        $modelClassName,
        Schema $schema,
        $extendedClass = '\\Dive\\Record',
        $collectionClass = '\\Dive\\Collection\\RecordCollection'
    ) {
        $addUses = array();

        $tableName = $this->getTableName($modelClassName, $schema);
        $tableFields = $schema->getTableFields($tableName);
        $fields = array();
        foreach ($tableFields as $key => $tableField) {
            $type = $this->translateType($tableField['type'], $key);
            $fields[] = array($type, $key);
        }


        $tableRelations = $schema->getTableRelations($tableName);
        foreach ($tableRelations as $relationType => $relations) {
            foreach ($relations as $tableRelation) {
                if ($relationType == 'owning') {
                    $key = $tableRelation['refAlias'];
                    $relatedTable = $tableRelation['refTable'];
                    $isOneToOne = true;
                }
                else {
                    $key = $tableRelation['owningAlias'];
                    $relatedTable = $tableRelation['owningTable'];
                    $isOneToOne = $tableRelation['type'] == Relation::ONE_TO_ONE;
                }
                $relatedRecordClass = $schema->getRecordClass($relatedTable);
                list($relatedRecordClass) = $this->formatter->splitClassAndNamespace($relatedRecordClass);
                if ($isOneToOne) {
                    $type = $relatedRecordClass;
                }
                else {
                    list($collectionClassName) = $this->formatter->splitClassAndNamespace($collectionClass);
                    $type = $relatedRecordClass . '[]|' . $collectionClassName;
                    $addUses[] = $collectionClass;
                }
                $fields[] = array($type, $key);
            }
        }


        return $this->formatter
            ->resetUsages()
            ->addUsages($addUses)
            ->setExtendFrom($extendedClass)
            ->resetAnnotations()
            ->addAnnotation('author', $this->author)
            ->addAnnotation('created', $this->date)
            ->setProperties($fields)
            ->getClassFile($modelClassName);
    }


    /**
     * @param string $modelClassName
     * @param Schema $schema
     * @return array
     */
    private function getTableName($modelClassName, Schema $schema)
    {
        $tableNames = $schema->getTableNames();
        foreach ($tableNames as $tableName) {
            if ($modelClassName == $schema->getRecordClass($tableName)) {
                return $tableName;
            }
        }
        return array();
    }


    /**
     * @param string $type
     * @param string $key
     * @return string
     */
    private function translateType($type, $key)
    {
        if ($type == 'integer' || $type == 'datetime') {
            // blacklist integer and datetime
            if (substr($key, 0, 3) == 'is_' || substr($key, 0, 4) == 'has_') {
                return 'bool';
            }
            return 'string';
        }
        return $type;
    }


    /**
     * @param string $license
     * @return $this
     */
    public function setLicense($license = '')
    {
        $this->formatter->setFileComment($license);
        return $this;
    }


    /**
     * @param string $eol
     * @return $this
     */
    public function setEndOfLine($eol = PHP_EOL)
    {
        $this->formatter->setEndOfLine($eol);
        return $this;
    }


    /**
     * @param string $author
     * @param string $mail
     */
    public function setAuthor($author = '', $mail = null)
    {
        if ($mail !== null) {
            $author .= " <$mail>";
        }
        $this->author = $author;
    }


    /**
     * @param string $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }
}
