<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Generator;


use Dive\Exception;
use Dive\Generator\Formatter\FormatterInterface;
use Dive\RecordManager;
use Dive\Relation\Relation;
use Dive\Schema\Schema;
use Dive\Util\ClassNameExtractor;
use Dive\Util\ModelFilterIterator;
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
     * @var FormatterInterface
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
     * @param RecordManager      $recordManager
     * @param FormatterInterface $formatter
     * @return ModelGenerator
     */
    public function __construct(RecordManager $recordManager, FormatterInterface $formatter)
    {
        $this->recordManager = $recordManager;
        $this->date = date('d.m.y');

        $this->formatter = $formatter;
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
     * @param Schema $schema
     * @param string $targetDirectory
     * @return string[]
     */
    public function getMissingModels(Schema $schema, $targetDirectory)
    {
        $existingModel = $this->getExistingModelClasses($targetDirectory);
        $neededModels = $this->getNeededModels($schema);
        return array_diff($neededModels, $existingModel);
    }


    /**
     * @param string $modelClassName
     * @param Schema $schema
     * @param string $extendedClass
     * @param string $collectionClass
     * @throws Exception
     * @return string
     */
    public function getContent(
        $modelClassName,
        Schema $schema,
        $extendedClass = '\\Dive\\Record',
        $collectionClass = '\\Dive\\Collection\\RecordCollection'
    ) {
        $usages = array();

        $tableName = $this->getTableName($modelClassName, $schema);
        if ($tableName === null) {
            throw new Exception("no table found for class $modelClassName");
        }
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
                $relatedRecordClassFull = $schema->getRecordClass($relatedTable);
                $relatedRecordClass = ClassNameExtractor::splitClass($relatedRecordClassFull);
                if ($isOneToOne) {
                    $type = $relatedRecordClass;
                }
                else {
                    $type = $relatedRecordClass . '[]|' . ClassNameExtractor::splitClass($collectionClass);
                    $usages[] = $collectionClass;
                }
                $fields[] = array($type, $key);
            }
        }


        return $this->formatter
            ->setUsages($usages)
            ->setExtendedFrom($extendedClass)
            ->setAnnotations()
            ->setAnnotation('author', $this->author)
            ->setAnnotation('created', $this->date)
            ->setProperties($fields)
            ->getFileContent($modelClassName);
    }


    /**
     * @param string $modelClassName
     * @param Schema $schema
     * @return string
     */
    private function getTableName($modelClassName, Schema $schema)
    {
        $tableNames = $schema->getTableNames();
        foreach ($tableNames as $tableName) {
            if ($modelClassName == $schema->getRecordClass($tableName)) {
                return $tableName;
            }
        }
        return null;
    }


    /**
     * @param string $type
     * @param string $key
     * @return string
     */
    private function translateType($type, $key)
    {
        if ($type == 'datetime') {
            return 'string';
        }
        if ($type == 'integer') {
            // blacklist integer and datetime
            if (substr($key, 0, 3) == 'is_' || substr($key, 0, 4) == 'has_') {
                return 'bool';
            }
            return 'string';
        }
        if ($type == 'decimal') {
            return 'float';
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
     * @return $this
     */
    public function setAuthor($author = '', $mail = null)
    {
        if ($mail !== null) {
            $author .= " <$mail>";
        }
        $this->author = $author;
        return $this;
    }


    /**
     * @param string $date
     * @return $this
     */
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }


    /**
     * @param string $className
     * @param Schema $schema
     * @param string $targetDirectory
     * @throws Exception
     */
    public function writeClassFile($className, Schema $schema, $targetDirectory)
    {
        $classFile = $this->getContent($className, $schema);
        $targetFile = $this->formatter->getTargetFileName($className, $targetDirectory);
        $write = file_put_contents($targetFile, $classFile, LOCK_EX);
        if ($write === false) {
            throw new Exception('file could not be written');
        }
    }


    /**
     * @param Schema $schema
     * @param string $targetDirectory
     */
    public function writeMissingModelFiles(Schema $schema, $targetDirectory)
    {
        $missingModels = $this->getMissingModels($schema, $targetDirectory);
        foreach ($missingModels as $missingModel) {
            $this->writeClassFile($missingModel, $schema, $targetDirectory);
        }
    }
}
