<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Validation;

use Dive\RecordManager;
use Dive\TestSuite\Record\Record;
use Dive\TestSuite\TestCase;
use Dive\Validation\RecordValidator;
use Dive\Validation\ValidationContainer;
use Dive\Validation\ValidatorInterface;

/**
 * Class ValidationContainerTest
 *
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 06.06.2014
 */
class ValidationContainerTest extends TestCase
{

    /** @var ValidationContainer */
    private $container = null;

    /** @var ValidatorInterface */
    private $validator;

    /** @var RecordManager */
    private $rm;

    /** @var Record */
    private $record;


    public function testConfigureContainer()
    {
        $this->givenIHaveAContainer();
        $this->whenIAddAValidator();
        $this->thenItShouldContainValidator();
    }


    /**
     * @expectedException \Dive\Validation\ValidationException
     */
    public function testItThrowsAnExceptionWhenWeWantToAccessAnNotExistingValidator()
    {
        $this->givenIHaveAContainer();
        $this->whenIAccessTheValidator();
    }


    public function testCanValidateToTrue()
    {
        $this->givenIHaveAContainer();
        $this->givenIHaveAMockedRecord();
        $this->whenIAddAnAlwaysSuccessfulValidator();
        $this->thenTheContainerShouldValidateARecordAsSuccessful();
    }


    public function testCanValidateToFalse()
    {
        $this->givenIHaveAContainer();
        $this->givenIHaveAMockedRecord();
        $this->whenIAddAnAlwaysFailingValidator();
        $this->thenTheContainerShouldValidateARecordAsFailing();
    }


    public function testFieldTypeValidatesToFalse()
    {
        $this->givenIHaveAConfiguredContainer();
        $this->givenIHaveAUserRecordWithData(array('username' => true, 'password' => '123'));
        $this->thenTheContainerShouldValidateARecordAsFailing();
    }


    public function testFieldTypeValidatesToTrue()
    {
        $this->givenIHaveAConfiguredContainer();
        $this->givenIHaveDisabledTheCheck(RecordValidator::CODE_FIELD_NOTNULL);
        $this->givenIHaveDisabledTheCheck(RecordValidator::CODE_FIELD_TYPE);
        $this->givenIHaveAUserRecordWithData(array('username' => true));
        $this->thenTheContainerShouldValidateARecordAsSuccessful();
    }


    public function testFieldLengthValidatesToFalse()
    {
        $this->givenIHaveAConfiguredContainer();
        $this->givenIHaveAUserRecordWithData(array('username' => str_repeat('a', 100), 'password' => '123'));
        $this->thenTheContainerShouldValidateARecordAsFailing();
    }


    public function testFieldLengthValidatesToTrue()
    {
        $this->givenIHaveAConfiguredContainer();
        $this->givenIHaveDisabledTheCheck(RecordValidator::CODE_FIELD_LENGTH);
        $this->givenIHaveAUserRecordWithData(array('username' => str_repeat('a', 100), 'password' => '123'));
        $this->thenTheContainerShouldValidateARecordAsSuccessful();
    }


    public function testUniqueConstraintValidatesToFalse()
    {
        $this->givenIHaveAConfiguredContainer();
        $this->givenIHaveAStoredUserRecordWithData(array('username' => 'John', 'password' => 'my-secret'));
        $this->givenIHaveAUserRecordWithData(array('username' => 'John', 'password' => 'my-secret'));
        $this->thenTheContainerShouldValidateARecordAsFailing();
    }


    public function testUniqueConstraintValidatesToTrue()
    {
        $this->givenIHaveAConfiguredContainer();
        $this->givenIHaveAStoredUserRecordWithData(array('username' => 'John', 'password' => 'my-secret'));
        $this->givenIHaveAUserRecordWithData(array('username' => 'John', 'password' => 'my-secret'));
        $this->givenIHaveDisabledTheCheck(RecordValidator::CODE_RECORD_UNIQUE);
        $this->thenTheContainerShouldValidateARecordAsSuccessful();
    }


    private function givenIHaveAContainer()
    {
        $this->container = new ValidationContainer();
    }


    private function whenIAddAValidator()
    {
        $this->validator = $this->getMockedValidator();
        $this->addValidatorToContainer('name', $this->validator);
    }


    private function whenIAddAnAlwaysSuccessfulValidator()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ValidatorInterface $validator */
        $validator = $this->getMockedValidator();
        $validator->expects($this->any())->method('validate')->will($this->returnValue(true));
        $this->addValidatorToContainer('successfulValidator', $validator);
    }


    private function whenIAddAnAlwaysFailingValidator()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ValidatorInterface $validator */
        $validator = $this->getMockedValidator();
        $validator->expects($this->any())->method('validate')->will($this->returnValue(false));
        $this->addValidatorToContainer('failingValidator', $validator);
    }


    private function thenItShouldContainValidator()
    {
        $this->whenIAccessTheValidator();
        $this->assertNotNull($this->validator);
        $this->assertInstanceOf(RecordValidator::class, $this->validator);
    }


    /**
     * @return ValidatorInterface
     */
    private function getMockedValidator()
    {
        return $this->getMock(RecordValidator::class);
    }


    private function whenIAccessTheValidator()
    {
        $this->validator = $this->container->getValidator('name');
    }


    /**
     * @return \Dive\Record
     */
    private function getMockedRecord()
    {
        return $this->getMock(Record::class, null, array(), '', false);
    }


    private function thenTheContainerShouldValidateARecordAsSuccessful()
    {
        $this->assertTrue($this->validateRecord());
    }


    private function thenTheContainerShouldValidateARecordAsFailing()
    {
        $this->assertFalse($this->validateRecord());
    }


    /**
     * @param string          $name
     * @param RecordValidator $validator
     */
    private function addValidatorToContainer($name, RecordValidator $validator)
    {
        $this->container->addValidator($name, $validator);
    }


    /**
     * @return bool
     */
    private function validateRecord()
    {
        return $this->container->validate($this->record);
    }


    private function givenIHaveAConfiguredContainer()
    {
        $this->rm = self::createDefaultRecordManager();
        $this->container = $this->rm->getRecordValidationContainer();
    }


    private function givenIHaveAMockedRecord()
    {
        $this->record = $this->getMockedRecord();
    }


    /**
     * @param array $userData
     */
    private function givenIHaveAUserRecordWithData(array $userData)
    {
        $this->record = $this->rm->getOrCreateRecord('user', $userData);
    }


    /**
     * @param string $check
     */
    private function givenIHaveDisabledTheCheck($check)
    {
        $this->container->addDisabledCheck($check);
    }


    /**
     * @param array $userData
     */
    private function givenIHaveAStoredUserRecordWithData($userData)
    {
        self::saveTableRows($this->rm, array('user' => array($userData)));
    }

}
