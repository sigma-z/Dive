<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\TestSuite;

use Dive\Platform\PlatformInterface;
use Dive\Record\Generator\RecordGenerator;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 23.12.13
 */
abstract class ConstraintTestCase extends TestCase
{

    /** @var RecordGenerator */
    protected static $recordGenerator;


    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $tableRows = TableRowsProvider::provideTableRows();
        $rm = self::createDefaultRecordManager();
        self::$recordGenerator = self::saveTableRows($rm, $tableRows);
    }


    /**
     * @param  string $tableName
     * @param  string $constraintType
     * @param  array  $constraints
     * @return \Dive\RecordManager
     */
    protected function getRecordManagerWithOverWrittenConstraints($tableName, $constraintType, array $constraints)
    {
        $schemaDefinition = self::getSchemaDefinition();
        self::processSchemaConstraints($schemaDefinition, $tableName, $constraintType, $constraints);
        $rm = self::createDefaultRecordManager($schemaDefinition);
        return $rm;
    }


    /**
     * @param array  $schemaDefinition
     * @param string $tableName
     * @param string $constraintType
     * @param array  $constraints
     * @param array  $processedTables
     */
    private static function processSchemaConstraints(
        &$schemaDefinition,
        $tableName,
        $constraintType,
        array $constraints,
        array &$processedTables = array()
    )
    {
        if (in_array($tableName, $processedTables) || empty($constraints)) {
            return;
        }

        $constraint = array_shift($constraints);
        if ($constraint == PlatformInterface::CASCADE && empty($constraints)) {
            $constraints[] = PlatformInterface::CASCADE;
        }
        $processedTables[] = $tableName;
        foreach ($schemaDefinition['relations'] as &$relation) {
            if ($relation['refTable'] == $tableName) {
                $relation[$constraintType] = $constraint;
                if ($relation['owningTable'] != $tableName) {
                    self::processSchemaConstraints(
                        $schemaDefinition, $relation['owningTable'], $constraintType, $constraints, $processedTables
                    );
                }
            }
        }
    }

}
