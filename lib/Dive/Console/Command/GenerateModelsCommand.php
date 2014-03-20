<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Dive\Console\Command;

use Dive\Console\ConsoleException;
use Dive\Console\OutputWriterInterface;
use Dive\Generator\Formatter\PhpClassFormatter;
use Dive\Generator\ModelGenerator;
use Dive\Schema\Schema;

/**
 * Class GenerateModelsCommand
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class GenerateModelsCommand extends Command
{

    /** @var OutputWriterInterface */
    private $outputWriter;

    /** @var ModelGenerator */
    private $modelGenerator;

    /** @var Schema */
    private $schema;


    /**
     * constructor
     */
    public function __construct()
    {
        $this->description = "Generates model (record) classes";
        $this->requiredParams = array(
            'schema-file' => 'Dive schema file (php)',
            'target-dir' => 'Target directory for model classes'
        );
        $this->optionalParams = array(
            'overwrite' => 'Flag to overwrite existing model classes, default if OFF',
            'license-file' => 'License text read from file will be the license comment in generated model classes',
            'eol' => 'Possible values: lf crlf cr (default is lf)',
            'create-date-format' => 'Default is d.m.y'
        );
    }


    /**
     * @param  \Dive\Console\OutputWriterInterface $outputWriter
     * @return bool
     */
    public function execute(OutputWriterInterface $outputWriter)
    {
        $this->outputWriter = $outputWriter;
        $this->schema = $this->getSchema();

        $this->createModelGenerator();

        $targetDirectory = $this->getParam('target-dir');
        $missingModels = $this->modelGenerator->getMissingModels($this->schema, $targetDirectory);
        $wroteModels = $this->writeModelFiles($missingModels, 'New model classes: ');

        if ($this->getBooleanParam('overwrite', false)) {
            $existingModels = $this->modelGenerator->getMissingModels($this->schema, $targetDirectory);
            $wroteExistingModels = $this->writeModelFiles($existingModels, 'Overwritten model classes: ');
            if ($wroteExistingModels) {
                $wroteModels = true;
            }
        }

        if (!$wroteModels) {
            $outputWriter->writeLine(
                "WARNING: No models have been generated!\n"
                . "  To generate model classes, you have to specify the key 'recordClass'\n"
                . "  for each table in your schema file!"
            );
        }

        return true;
    }


    /**
     * @return string
     */
    private function getCreatedOnDate()
    {
        $dateFormat = $this->getParam('create-date-format', 'd.m.y');
        return date($dateFormat);
    }


    /**
     * @return string
     */
    private function getEndOfLine()
    {
        $eol = strtolower($this->getParam('eol'));
        switch ($eol) {
            case 'crlf':
                return "\r\n";
            case 'cr':
                return "\r";
            default:
                return "\n";
        }
    }


    /**
     * @throws \Dive\Console\ConsoleException
     */
    private function createModelGenerator()
    {
        $formatter = new PhpClassFormatter();
        $modelGenerator = new ModelGenerator($formatter);

        $modelGenerator->setAuthor('Dive ModelGenerator')
            ->setEndOfLine($this->getEndOfLine());
        $createdOnDate = $this->getCreatedOnDate();
        if ($createdOnDate) {
            $modelGenerator->setCreatedOn($this->getCreatedOnDate());
        }
        $licenseFile = $this->getParam('license-file');
        if ($licenseFile) {
            if (!is_file($licenseFile)) {
                throw new ConsoleException("Could not open license file '$licenseFile'!");
            }
            $license = trim(file_get_contents($licenseFile));
            $modelGenerator->setLicense($license);
        }

        $this->modelGenerator = $modelGenerator;
    }


    /**
     * @return Schema
     * @throws \Dive\Console\ConsoleException
     */
    private function getSchema()
    {
        $schemaFile = $this->getParam('schema-file');
        if (!is_file($schemaFile)) {
            throw new ConsoleException("Could not open Dive schema file '$schemaFile'!");
        }
        /** @noinspection PhpIncludeInspection */
        $schemaDefinition = include $schemaFile;
        $schema = new Schema($schemaDefinition);
        return $schema;
    }


    /**
     * @param  array  $models
     * @param  string $message
     * @return bool
     */
    private function writeModelFiles(array $models, $message)
    {
        $targetDirectory = $this->getParam('target-dir');
        $numberOfMissingModels = count($models);
        if ($numberOfMissingModels > 0) {
            $this->modelGenerator->writeMissingModelFiles($this->schema, $targetDirectory);
            $this->outputWriter->writeLine(
                $message . $numberOfMissingModels, OutputWriterInterface::LEVEL_LESS_INFO
            );
            return true;
        }
        return false;
    }
}
