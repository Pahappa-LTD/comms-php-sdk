<?php

namespace PahappaLimited\EgoSmsSdk\Tests\v1\utils;

use PahappaLimited\EgoSmsSdk\v1\utils\NumberValidator;
use PHPUnit\Framework\TestCase;

class NumberValidatorTest extends TestCase
{
    public function testValidateNumbersWithValidNumbers()
    {
        $numbers = ['+256772123456', '0772123457', '256772123458'];
        $expected = ['256772123456', '256772123457', '256772123458'];
        $this->assertEquals($expected, NumberValidator::validateNumbers($numbers));
    }

    public function testValidateNumbersWithInvalidNumbers()
    {
        $numbers = ['123', 'not a number', '077212345'];
        $this->assertEmpty(NumberValidator::validateNumbers($numbers));
    }

    public function testValidateNumbersWithMixedNumbers()
    {
        $numbers = ['+256772123456', '123', '0772123457'];
        $expected = ['256772123456', '256772123457'];
        $this->assertEquals($expected, NumberValidator::validateNumbers($numbers));
    }

    public function testValidateNumbersWithEmptyArray()
    {
        $this->assertEmpty(NumberValidator::validateNumbers([]));
    }

    public function testValidateNumbersWithDuplicateNumbers()
    {
        $numbers = ['+256772123456', '0772123456'];
        $expected = ['256772123456'];
        $this->assertEquals($expected, NumberValidator::validateNumbers($numbers));
    }

    public function testValidateNumbersWithDifferentFormats()
    {
        $numbers = ['+256 772 123 456', '0772-123-457', ' 256772123458 '];
        $expected = ['256772123456', '256772123457', '256772123458'];
        $this->assertEquals($expected, NumberValidator::validateNumbers($numbers));
    }
}
