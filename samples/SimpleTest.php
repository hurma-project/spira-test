<?php
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Some simple tests using the ability to return results back to SpiraTest
 * 
 * @author		Inflectra Corporation
 * @version		2.3.0
 *
 */
 
class SimpleTest extends PHPUnit_Framework_TestCase
{
	protected $fValue1;
	protected $fValue2;

	/**
	 * Sets up the unit test
	 */
	protected function setUp()
	{
		$this->fValue1= 2;
		$this->fValue2= 3;
	}

	/**
	 * Tests the addition of the two values
	 */
	public function testAdd__2()
	{
		$result = $this->fValue1 + $this->fValue2;

		// forced failure result == 5
		$this->assertTrue ($result == 6);
	}

	/**
	 * Tests division by zero
	 */
	public function testDivideByZero__3()
	{
		$zero = 0;
		$result = 8 / $zero;
		$result++; // avoid warning for not using result
	}

	/**
	 * Tests two equal values
	 */
	public function testEquals__4()
	{
		$this->assertEquals(12, 12);
		$this->assertEquals(12.0, 12.0);
    $num1 = 12;
    $num2 = 12;
		$this->assertEquals($num1, $num2);

		$this->assertEquals("Size", 12, 13);
		$this->assertEquals("Capacity", 12.0, 11.99, 0.0);
	}

	/**
	 * Tests success
	 */
	public function testSuccess__5()
	{
		//Successful test
		$this->assertEquals(12, 12);
	}
}
?>
