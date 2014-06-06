<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Validation;

use Dive\TestSuite\TestCase;
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

    /**
     * @var ValidationContainer
     */
    private $container = null;

    /** @var ValidatorInterface */
    private $validator;


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
        $this->whenIAddAnAlwaysSuccessfulValidator();
        $this->thenTheContainerShouldValidateARecordAsSuccessful();
    }


    public function testCanValidateToFalse()
    {
        $this->givenIHaveAContainer();
        $this->whenIAddAnAlwaysFailingValidator();
        $this->thenTheContainerShouldValidateARecordAsFailing();
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
        /** @var $validator \PHPUnit_Framework_MockObject_MockObject */
        $validator = $this->getMockedValidator();
        $validator->expects($this->any())->method('validate')->will($this->returnValue(true));
        $this->addValidatorToContainer('successfulValidator', $validator);
    }


    private function whenIAddAnAlwaysFailingValidator()
    {
        /** @var $validator \PHPUnit_Framework_MockObject_MockObject */
        $validator = $this->getMockedValidator();
        $validator->expects($this->any())->method('validate')->will($this->returnValue(false));
        $this->addValidatorToContainer('failingValidator', $validator);
    }


    private function thenItShouldContainValidator()
    {
        $this->whenIAccessTheValidator();
        $this->assertNotNull($this->validator);
        $this->assertInstanceOf('\Dive\Validation\ValidatorInterface', $this->validator);
    }


    /**
     * @return ValidatorInterface
     */
    private function getMockedValidator()
    {
        return $this->getMock('\Dive\Validation\ValidatorInterface');
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
        return $this->getMock('\Dive\Record', array(), array(), '', false);
    }


    private function thenTheContainerShouldValidateARecordAsSuccessful()
    {
        $this->assertTrue($this->getValidationResultForMockedRecord());
    }


    private function thenTheContainerShouldValidateARecordAsFailing()
    {
        $this->assertFalse($this->getValidationResultForMockedRecord());
    }


    /**
     * @param $name
     * @param $validator
     */
    private function addValidatorToContainer($name, $validator)
    {
        $this->container->addValidator($name, $validator);
    }


    /**
     * @return bool
     */
    private function getValidationResultForMockedRecord()
    {
        return $this->container->validate($this->getMockedRecord());
    }



}