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
use Dive\Validation\ErrorStack;

/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 05.07.2014
 */
class ErrorStackTest extends TestCase
{

    /** @var ErrorStack */
    private $errorStack;


    public function testInitialErrorStackHasNoErrors()
    {
        $this->givenIHaveAnErrorStack();
        $this->thenItShouldHaveNoErrors();
        $this->thenItShouldHaveErrorCountOf(0);
    }


    public function testAddError()
    {
        $this->givenIHaveAnErrorStack();
        $this->whenIAddAnErrorOnField_withCode('first_field', 'invalid');
        $this->whenIAddAnErrorOnField_withCode('second_field', 'invalid');
        $this->whenIAddAnErrorOnField_withCode('first_field', 'notnull');
        $this->thenItShouldHaveErrorCountOf(2);
        $this->thenField_shouldHaveErrorsWithCode('second_field', array('invalid'));
        $this->thenField_shouldHaveErrorsWithCode('first_field', array('invalid', 'notnull'));
        $this->thenField_shouldHaveErrorsWithCode('third_field', array());
    }


    public function testRemoveFieldErrorCode()
    {
        $this->givenIHaveAnErrorStack();
        $this->whenIAddAnErrorOnField_withCode('first_field', 'invalid');
        $this->whenIAddAnErrorOnField_withCode('first_field', 'notnull');
        $this->whenIRemoveErrorOnField_withCode('first_field', 'invalid');
        $this->thenField_shouldHaveErrorsWithCode('first_field', array('notnull'));
    }


    public function testRemoveErrorsForField()
    {
        $this->givenIHaveAnErrorStack();
        $this->whenIAddAnErrorOnField_withCode('first_field', 'invalid');
        $this->whenIAddAnErrorOnField_withCode('first_field', 'notnull');
        $this->whenIRemoveErrorsOnField('first_field');
        $this->thenField_shouldHaveErrorsWithCode('first_field', array());
        $this->thenItShouldHaveNoErrors();
    }


    public function testRemoveErrorCodeInFields()
    {
        $this->givenIHaveAnErrorStack();
        $this->whenIAddAnErrorOnField_withCode('first_field', 'invalid');
        $this->whenIAddAnErrorOnField_withCode('second_field', 'invalid');
        $this->whenIAddAnErrorOnField_withCode('first_field', 'notnull');
        $this->whenIRemoveErrorCode_inAllFields('invalid');
        $this->thenField_shouldHaveErrorsWithCode('first_field', array('notnull'));
        $this->thenField_shouldHaveErrorsWithCode('second_field', array());
    }


    private function givenIHaveAnErrorStack()
    {
        $this->errorStack = new ErrorStack();
    }


    private function thenItShouldHaveNoErrors()
    {
        $this->assertTrue($this->errorStack->isEmpty());
    }


    /**
     * @param int $count
     */
    private function thenItShouldHaveErrorCountOf($count)
    {
        $this->assertEquals($count, $this->errorStack->count());
    }


    /**
     * @param string $fieldName
     * @param string $errorCode
     */
    private function whenIAddAnErrorOnField_withCode($fieldName, $errorCode)
    {
        $this->errorStack->add($fieldName, $errorCode);
    }


    /**
     * @param string $fieldName
     * @param array  $errorCodes
     */
    private function thenField_shouldHaveErrorsWithCode($fieldName, array $errorCodes)
    {
        $this->assertEquals($errorCodes, array_values($this->errorStack->get($fieldName)));
    }


    /**
     * @param string $fieldName
     * @param string $errorCode
     */
    private function whenIRemoveErrorOnField_withCode($fieldName, $errorCode)
    {
        $this->errorStack->remove($fieldName, $errorCode);
    }


    /**
     * @param string $fieldName
     */
    private function whenIRemoveErrorsOnField($fieldName)
    {
        $this->errorStack->remove($fieldName);
    }


    /**
     * @param string $errorCode
     */
    private function whenIRemoveErrorCode_inAllFields($errorCode)
    {
        $this->errorStack->removeErrorCodeInFields($errorCode);
    }
}
