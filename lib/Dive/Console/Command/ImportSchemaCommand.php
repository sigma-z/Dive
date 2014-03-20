<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Dive\Console\Command;

use Dive\Connection\Connection;
use Dive\Connection\Driver\DriverFactory;
use Dive\Console\ConsoleException;
use Dive\Console\OutputWriterInterface;
use Dive\Schema\Schema;
use Dive\Schema\SchemaWriter;

/**
 * Class ImportSchemaCommand
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class ImportSchemaCommand extends Command
{

    /** @var OutputWriterInterface */
    private $outputWriter;

    /** @var string */
    private $configFile;


    /**
     * constructor
     */
    public function __construct()
    {
        $this->description = 'Importing the database schema into a Dive schema file (php format)';
        $this->requiredParams = array('schema-file' => 'Output file for Dive schema');
    }


    /**
     * @param \Dive\Console\OutputWriterInterface $outputWriter
     * @return bool
     */
    public function execute(OutputWriterInterface $outputWriter)
    {
        $this->outputWriter = $outputWriter;
        $this->configFile = dirname($_SERVER['SCRIPT_NAME']) . '/cli-config.php';

        /** @var Connection $conn */
        $conn = $this->getConnection();
        $outputSchemaFile = $this->getParam('schema-file');

        $schemaImporter = $conn->getDriver()->getSchemaImporter($conn);
        $definition = $schemaImporter->importDefinition();
        if (is_file($outputSchemaFile)) {
            /** @noinspection PhpIncludeInspection */
            $oldDefinition = include $outputSchemaFile;
            $definition = $this->mergeSchemaDefinitions($definition, $oldDefinition);
        }
        $this->writeSchemaFile($definition, $outputSchemaFile);

        return true;
    }


    /**
     * merges schema definitions
     *
     * @param array $definition
     * @param array $oldDefinition
     * @return array
     */
    private function mergeSchemaDefinitions(array $definition, array $oldDefinition)
    {
        if (empty($oldDefinition['tables'])) {
            return $definition;
        }
        foreach ($oldDefinition['tables'] as $tableName => $tableDefinition) {
            if (isset($tableDefinition['recordClass'])) {
                $definition['tables'][$tableName]['recordClass'] = $tableDefinition['recordClass'];
            }
        }
        return $definition;
    }


    /**
     * @return Connection
     */
    private function getConnection()
    {
        $config = $this->loadConnectionConfig();
        if (!isset($config['connection']['dsn'])) {
            $this->printSampleConfigAndThrowException('CLI config file is corrupt!');
        }
        $connConfig = $config['connection'];
        $dsn = $connConfig['dsn'];
        $driver = DriverFactory::createByDsn($dsn);
        $user = !empty($connConfig['user']) ? $connConfig['user'] : '';
        $password = !empty($connConfig['password']) ? $connConfig['password'] : '';

        return new Connection($driver, $dsn, $user, $password);
    }


    /**
     * @return array
     */
    private function loadConnectionConfig()
    {
        $configFile = dirname($_SERVER['SCRIPT_NAME']) . '/cli-config.php';
        if (!is_file($configFile)) {
            $this->printSampleConfigAndThrowException('Missing config file for cli!');
        }
        /** @noinspection PhpIncludeInspection */
        return include($configFile);
    }


    /**
     * @param  string $message
     * @throws \Dive\Console\ConsoleException
     */
    private function printSampleConfigAndThrowException($message)
    {
        $sampleConfigFile = <<<"HELP"
>>> ERROR: $message <<<
Use following sample as a template for the $this->configFile:

<?php
return array(
    'connection' => array(
        'dsn' => 'mysql:host=localhost;dbname=database',
        //'dsn' => 'sqlite:/path/to/database.db'
        'user' => '',
        'password' => ''
    )
);
HELP;
        $this->outputWriter->writeLine($sampleConfigFile, OutputWriterInterface::LEVEL_LESS_INFO);
        throw new ConsoleException($message);
    }


    /**
     * @param $definition
     * @param $outputSchemaFile
     */
    private function writeSchemaFile($definition, $outputSchemaFile)
    {
        $schemaWriter = new SchemaWriter(new Schema($definition));
        $schemaWriter->write($outputSchemaFile);

        $this->outputWriter->writeLine("Dive schema file has been written to: $outputSchemaFile");
    }
}
