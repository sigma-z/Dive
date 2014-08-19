<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Dive\Test\Util;

use Dive\Schema\DataTypeMapper\DataTypeMapper;
use Dive\TestSuite\TestCase;
use Dive\Util\FieldValuesGenerator;

/**
 * @author Steven Nikolic <steven@nindoo.de>
 * @created 25.10.13
 */
class FieldValuesGeneratorTest extends TestCase
{
    /**
     * @var FieldValuesGenerator
     */
    private $generator;


    protected function setUp()
    {
        parent::setUp();

        $this->generator = new FieldValuesGenerator();
    }


    /**
     * @dataProvider provideGetRandomFieldValue
     *
     * @param array $fieldDefinition
     * @param int   $minLength
     * @param int   $maxLength
     */
    public function testGetRandomFieldValue(array $fieldDefinition, $minLength, $maxLength)
    {
        $actual = $this->generator->getRandomFieldValue($fieldDefinition);
        $length = mb_strlen($actual);

        if ($minLength === $maxLength) {
            $this->assertEquals($minLength, $length);
        }
        else {
            $this->assertGreaterThanOrEqual($minLength, $length);
            $this->assertLessThanOrEqual($maxLength, $length);
        }
    }


    /**
     * @return array[]
     */
    public function provideGetRandomFieldValue()
    {
        return array(
            array(array('type' => DataTypeMapper::OTYPE_STRING, 'length' => '10'), 0, 10),
            array(array('type' => DataTypeMapper::OTYPE_INTEGER), 0, 10),
            array(array('type' => DataTypeMapper::OTYPE_DECIMAL), 0, 10),
            array(array('type' => DataTypeMapper::OTYPE_DATETIME), 19, 19),
            array(array('type' => 'nothing'), 1, 1),
        );
    }


    /**
     * @dataProvider provideTypeAliases
     *
     * @param array $inputFields
     * @param string $type
     * @param string $aliasFunction
     */
    public function testTypeAliases(array $inputFields, $type, $aliasFunction)
    {
        $recordData = array();
        $notAliasedActual = $this->generator->getRandomRecordData($inputFields, $recordData, $type);
        $aliasedActual = $this->generator->$aliasFunction($inputFields, $recordData);
        $this->assertInternalType('array', $notAliasedActual);
        $this->assertInternalType('array', $aliasedActual);
        foreach ($notAliasedActual as $key => $value) {
            $this->assertArrayHasKey($key, $aliasedActual);
        }
        $this->assertEquals(array_keys($notAliasedActual), array_keys($aliasedActual));
    }


    /**
     * @return array[]
     */
    public function provideTypeAliases()
    {
        $inputFields = array(
            array('type' => DataTypeMapper::OTYPE_STRING, 'length' => 10, 'nullable' => true),
            array('type' => DataTypeMapper::OTYPE_STRING, 'length' => 10, 'nullable' => false),
            array('type' => DataTypeMapper::OTYPE_STRING, 'length' => 10, 'nullable' => true, 'autoIncrement' => true),
            array('type' => DataTypeMapper::OTYPE_STRING, 'length' => 10, 'nullable' => true, 'autoIncrement' => false),
            array('type' => DataTypeMapper::OTYPE_STRING, 'length' => 10, 'nullable' => false, 'autoIncrement' => true),
            array('type' => DataTypeMapper::OTYPE_STRING, 'length' => 10, 'nullable' => false, 'autoIncrement' => false),
            array('type' => DataTypeMapper::OTYPE_STRING, 'length' => 10, 'autoIncrement' => true),
            array('type' => DataTypeMapper::OTYPE_STRING, 'length' => 10, 'autoIncrement' => false),
        );

        return array(
            array(
                'inputFields' => $inputFields,
                'type' => FieldValuesGenerator::REQUIRED,
                'aliasFunction' => 'getRequiredRandomRecordData'
            ),
            array(
                'inputFields' => $inputFields,
                'type' => FieldValuesGenerator::REQUIRED_AND_AUTOINCREMENT,
                'aliasFunction' => 'getRequiredRandomRecordDataWithAutoIncrementFields'
            ),
            array(
                'inputFields' => $inputFields,
                'type' => FieldValuesGenerator::MAXIMAL_WITHOUT_AUTOINCREMENT,
                'aliasFunction' => 'getMaximalRandomRecordDataWithoutAutoIncrementFields'
            ),
            array(
                'inputFields' => $inputFields,
                'type' => FieldValuesGenerator::REQUIRED_AND_AUTOINCREMENT,
                'aliasFunction' => 'getMaximalRandomRecordData'
            )
        );
    }


    public function testGenerateValidFieldValues()
    {
        $rm = self::createDefaultRecordManager();
        $table = $rm->getTable('data_types');
        $fieldValues = $this->generator->getRandomRecordData(
            $table->getFields(),
            array(),
            FieldValuesGenerator::MAXIMAL_WITHOUT_AUTOINCREMENT
        );

        $dataTypeMapper = $rm->getDriver()->getDataTypeMapper();
        foreach ($fieldValues as $fieldName => $value) {
            $field = $table->getField($fieldName);
            $fieldValidator = $dataTypeMapper->getOrmTypeInstance($field['type']);
            $this->assertTrue($fieldValidator->validateType($value, $field), "Field type for '$fieldName' is not valid!");
            $this->assertTrue($fieldValidator->validateLength($value, $field), "Field length for '$fieldName' is not valid!");
        }
    }

}
