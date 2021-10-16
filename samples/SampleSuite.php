<?php
/**
 * Passes a list of tests to be executed to PHPUnit and adds the custom SpiraTest Listener
 * 
 * @author		Inflectra Corporation
 * @version		2.3.1
 *
 */
 
 require_once 'PHPUnit/Framework.php';
 require_once 'PHPUnit/TextUI/ResultPrinter.php';
 require_once './SimpleTest.php';
 require_once '../SpiraListener/Listener.php';
 
 // Create a test suite that contains the tests
 // from the ArrayTest class
  $suite = new PHPUnit_Framework_TestSuite('SimpleTest');
 
 //Set the timezone identifier to match that used by the SpiraTest server
 date_default_timezone_set ("US/Eastern");
 
 //Create a new SpiraTest listener instance and specify the connection info
 $spiraListener = new SpiraListener_Listener('http://localhost/SpiraTeam', 'fredbloggs', 'fredbloggs', 1, 1, 1);
 $spiraListener->setBaseUrl ('http://localhost/SpiraTeam');
 
 // Create a test result and attach the SpiraTest listener
 // object as an observer to it (as well as the default console text listener)
 $result = new PHPUnit_Framework_TestResult;
 $textPrinter = new PHPUnit_TextUI_ResultPrinter;
 $result->addListener($textPrinter);
 $result->addListener($spiraListener);
 
 // Run the tests and print the results
 $result = $suite->run($result);
 $textPrinter->printResult($result);

 ?>