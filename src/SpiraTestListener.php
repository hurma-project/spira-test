<?php

namespace SpiraTest;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use PHPUnit\Runner\BaseTestRunner;
use Throwable;

/**
 * Listens during PHPUnit test executions and reports the results back to SpiraTest/Team
 *
 * @author    Inflectra Corporation
 * @version    3.2.0
 *
 */
class SpiraTestListener implements TestListener
{
  //General constants
  public const DEFAULT_TEST_RUNNER_NAME = "PHPUnit";

  //Internal variables
  protected int $startTime;

  //SpiraTest execution status constants
  public const EXECUTION_STATUS_ID_PASSED = 2;
  public const EXECUTION_STATUS_ID_FAILED = 1;
  public const EXECUTION_STATUS_ID_NOT_RUN = 3;
  public const EXECUTION_STATUS_ID_CAUTION = 6;
  public const EXECUTION_STATUS_ID_BLOCKED = 5;

  /* Constructor */

  public function __construct(string $spiraUrl, string $spiraUser, string $spiraPass, $projectId, $releaseId = -1, $testSetId = -1)
  {
    $this->setBaseUrl($spiraUrl);
    $this->setUserName($spiraUser);
    $this->setPassword($spiraPass);
    $this->setProjectId($projectId);
    $this->setReleaseId($releaseId);
    $this->setTestSetId($testSetId);
  }

  /* Class properties */

  /*
    The name of the test runner to report back to SpiraTest.
    If you are running a Selenium-RC web test, you might want to
    set it to Selenium to distinguish from a true PHPUnit test
  */
  protected string $testRunnerName = self::DEFAULT_TEST_RUNNER_NAME;

  public function getTestRunnerName(): string
  {
    return $this->testRunnerName;
  }

  public function setTestRunnerName(string $value): void
  {
    $this->testRunnerName = $value;
  }

  /*
    The base url of the Spira web service
  */
  protected string $baseUrl;

  public function getBaseUrl(): string
  {
    return $this->baseUrl;
  }

  public function setBaseUrl(string $value): void
  {
    $this->baseUrl = $value;
  }

  /*
    The user name of the Spira account accessing the web service
  */
  protected string $userName;

  public function getUserName(): string
  {
    return $this->userName;
  }

  public function setUserName(string $value): void
  {
    $this->userName = $value;
  }

  /*
    The password of the Spira account accessing the web service
  */
  protected string $password;

  public function getPassword(): string
  {
    return $this->password;
  }

  public function setPassword(string $value): void
  {
    $this->password = $value;
  }

  /*
    The ID of the project we're returning results against
  */
  protected int $projectId;

  public function getProjectId(): int
  {
    return $this->projectId;
  }

  public function setProjectId(int $value): void
  {
    $this->projectId = $value;
  }

  /*
  The ID of the release we're returning results against (optional)
*/
  protected int $releaseId = -1;

  public function getReleaseId(): int
  {
    return $this->releaseId;
  }

  public function setReleaseId(int $value): void
  {
    $this->releaseId = $value;
  }

  /*
  The ID of the test set we're returning results against (optional)
*/
  protected int $testSetId = -1;

  public function getTestSetId(): int
  {
    return $this->testSetId;
  }

  public function setTestSetId(int $value): void
  {
    $this->testSetId = $value;
  }

  /**
   * An error occurred.
   *
   * @param Test $test
   * @param \Throwable $e
   * @param float $time
   */
  public function addError(Test $test, \Throwable $e, float $time): void
  {
    //Not implemented, we just use endTest and check the status
  }

  /**
   * A failure occurred.
   *
   * @param Test $test
   * @param AssertionFailedError $e
   * @param float $time
   */
  public function addFailure(Test $test, AssertionFailedError $e, float $time): void
  {
    //Not implemented, we just use endTest and check the status
  }


  public function addWarning(Test $test, Warning $e, float $time): void
  {
    //Not implemented, we just use endTest and check the status
  }

  public function addRiskyTest(Test $test, Throwable $t, float $time): void
  {
    //Not implemented, we just use endTest and check the status
  }

  /**
   * Incomplete test.
   *
   * @param Test $test
   * @param \Throwable $e
   * @param float $time
   */
  public function addIncompleteTest(Test $test, \Throwable $e, float $time): void
  {
    //Not implemented, we just use endTest and check the status
  }

  /**
   * Skipped test.
   *
   * @param Test $test
   * @param \Throwable $e
   * @param float $time
   * @since  Method available since Release 3.0.0
   */
  public function addSkippedTest(Test $test, \Throwable $e, float $time): void
  {
    //Not implemented, we just use endTest and check the status
  }

  /**
   * A test suite started.
   *
   * @param TestSuite $suite
   * @since  Method available since Release 2.2.0
   */
  public function startTestSuite(TestSuite $suite): void
  {
    //Do nothing
  }

  /**
   * A test suite ended.
   *
   * @param TestSuite $suite
   * @since  Method available since Release 2.2.0
   */
  public function endTestSuite(TestSuite $suite): void
  {
    //Let the user know that we've finished the whole suite
    printf("\nTest Suite '%s' sent to SpiraTest\n", $suite->getName());
  }

  /**
   * A test started.
   *
   * @param Test $test
   */
  public function startTest(Test $test): void
  {
    //Record the time it started
    $this->startTime = time();
  }

  /**
   * A test ended.
   *
   * @param Test $test
   * @param float $time
   */
  public function endTest(Test $test, float $time): void
  {
    //Get the full test name (includes the spira id appended)
    $testNameAndId = $test->getName();
    $testComponents = preg_split("__", $testNameAndId);
    if (count($testComponents) >= 2) {
      //extract the test case id from the name (separated by two underscores)
      $testName = $testComponents[0];
      $testCaseId = (integer)$testComponents[1];

      //Now convert the execution status into the values expected by SpiraTest
      $executionStatusId = self::EXECUTION_STATUS_ID_NOT_RUN;
      $assertCount = $test->getNumAssertions();
      $message = $test->getStatusMessage();
      $stackTrace = $test->getStatusMessage();
      $startDate = $this->startTime;
      $endDate = $this->startTime + $time;

      //If the test was in the warning situation, report as Blocked
      if ($test instanceof Warning) {
        $executionStatusId = self::EXECUTION_STATUS_ID_BLOCKED;
      } else {
        if ($test->getStatus() === BaseTestRunner::STATUS_SKIPPED) {
          $executionStatusId = self::EXECUTION_STATUS_ID_BLOCKED;
        }
        if ($test->getStatus() === BaseTestRunner::STATUS_INCOMPLETE) {
          $executionStatusId = self::EXECUTION_STATUS_ID_CAUTION;
        }
        if ($test->getStatus() === BaseTestRunner::STATUS_PASSED) {
          $executionStatusId = self::EXECUTION_STATUS_ID_PASSED;
        }
        if ($test->getStatus() === BaseTestRunner::STATUS_FAILURE || $test->getStatus() === BaseTestRunner::STATUS_ERROR) {
          $executionStatusId = self::EXECUTION_STATUS_ID_FAILED;
        }
      }

      //Send the results to SpiraTest
      $testRunId = $this->getImportExport()->recordAutomated(
          $testCaseId, $this->releaseId, $this->testSetId, $startDate, $endDate, $executionStatusId,
          $this->testRunnerName, $testName, $assertCount, $message, $stackTrace);

      //Display the message letting the user know that the results were sent
      printf("\nTest Case '%s' (TC000%d) sent to SpiraTest with status %d - Test Run (TR000%d).\n", $testName, $testCaseId, $executionStatusId, $testRunId);
    }
  }

  public function getImportExport(): ImportExport
  {
    static $importExport = null;
    if (!$importExport) {
      $importExport = new ImportExport();
      $importExport->setBaseUrl($this->baseUrl);
      $importExport->setUserName($this->userName);
      $importExport->setPassword($this->password);
      $importExport->setProjectId($this->projectId);
    }
    return $importExport;
  }
}

?>