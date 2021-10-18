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
  // General constants
  public const DEFAULT_TEST_RUNNER_NAME = "PHPUnit";

  // Internal variables
  protected int $startTime;

  // SpiraTest execution status constants
  public const EXECUTION_STATUS_ID_FAILED = 1;
  public const EXECUTION_STATUS_ID_PASSED = 2;
  public const EXECUTION_STATUS_ID_NOT_RUN = 3;
  public const EXECUTION_STATUS_ID_BLOCKED = 5;
  public const EXECUTION_STATUS_ID_CAUTION = 6;

  public function __construct(
      ?string $baseUrl = null, ?string $userName = null, ?string $password = null,
      ?int $projectId = null, ?int $releaseId = null, ?int $testSetId = null
  ) {
    $this->setBaseUrl($baseUrl ?: $_ENV['SPIRA_URL']);
    $this->setUserName($userName ?: $_ENV['SPIRA_USER']);
    $this->setPassword($password ?: $_ENV['SPIRA_PASSWORD']);
    $this->setProjectId($projectId ?: $_ENV['SPIRA_PROJECT_ID']);
    $this->setReleaseId($releaseId ?: $_ENV['SPIRA_RELEASE_ID'] ?? -1);
    $this->setTestSetId($testSetId ?: $_ENV['SPIRA_TEST_SET_ID'] ?? -1);
  }

  protected string $testRunnerName = self::DEFAULT_TEST_RUNNER_NAME;

  /**
   * The name of the test runner to report back to SpiraTest.
   * If you are running a Selenium-RC web test, you might want to
   * set it to Selenium to distinguish from a true PHPUnit test
   */
  public function getTestRunnerName(): string
  {
    return $this->testRunnerName;
  }

  public function setTestRunnerName(string $value): void
  {
    $this->testRunnerName = $value;
  }

  protected ?string $baseUrl;

  /**
   * The base url of the Spira web service
   */
  public function getBaseUrl(): string
  {
    return $this->baseUrl;
  }

  public function setBaseUrl(string $value): void
  {
    $this->baseUrl = $value;
  }

  protected ?string $userName;

  /**
   * The user name of the Spira account accessing the web service
   */
  public function getUserName(): string
  {
    return $this->userName;
  }

  public function setUserName(string $value): void
  {
    $this->userName = $value;
  }

  protected ?string $password;

  /**
   * The password of the Spira account accessing the web service
   */
  public function getPassword(): string
  {
    return $this->password;
  }

  public function setPassword(string $value): void
  {
    $this->password = $value;
  }

  protected ?int $projectId;

  /**
   * The ID of the project we're returning results against
   */
  public function getProjectId(): int
  {
    return $this->projectId;
  }

  public function setProjectId(int $value): void
  {
    $this->projectId = $value;
  }

  protected ?int $releaseId;

  /**
   * The ID of the release we're returning results against (optional)
   */
  public function getReleaseId(): int
  {
    return $this->releaseId;
  }

  public function setReleaseId(int $value): void
  {
    $this->releaseId = $value;
  }

  protected ?int $testSetId;

  /**
   * The ID of the test set we're returning results against (optional)
   */
  public function getTestSetId(): int
  {
    return $this->testSetId;
  }

  public function setTestSetId(int $value): void
  {
    $this->testSetId = $value;
  }

  public function addError(Test $test, \Throwable $e, float $time): void
  {
    //Not implemented, we just use endTest and check the status
  }

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

  public function addIncompleteTest(Test $test, \Throwable $e, float $time): void
  {
    //Not implemented, we just use endTest and check the status
  }

  public function addSkippedTest(Test $test, \Throwable $e, float $time): void
  {
    //Not implemented, we just use endTest and check the status
  }

  public function startTestSuite(TestSuite $suite): void
  {
    //Not implemented, we just use endTest and check the status
  }

  public function endTestSuite(TestSuite $suite): void
  {
    //Not implemented, we just use endTest and check the status
  }

  public function startTest(Test $test): void
  {
    //Record the time it started
    $this->startTime = time();
  }

  public function endTest(Test $test, float $time): void
  {
    if (!$this->baseUrl && !$this->userName && !$this->password && !$this->projectId) {
      return;
    }

    //Get the full test name (includes the spira id appended)
    $testNameAndId = $test->getName();
    $testComponents = explode('__', $testNameAndId);
    if (count($testComponents) < 2) {
      return;
    }

    //extract the test case id from the name (separated by two underscores)
    $testName = $testComponents[0];
    $testCaseId = str_starts_with(strtolower($testComponents[1]), 'tc')
        ? (integer)substr($testComponents[1], 2)
        : -1;
    if ($testCaseId <= 0) {
      return;
    }

    //Now convert the execution status into the values expected by SpiraTest
    $assertCount = $test->getNumAssertions();
    $message = $test->getStatusMessage();
    $stackTrace = $test->getStatusMessage();
    $startDate = $this->startTime;
    $endDate = $this->startTime + $time;

    //If the test was in the warning situation, report as Blocked
    if ($test instanceof Warning) {
      $executionStatusId = self::EXECUTION_STATUS_ID_BLOCKED;
    } else {
      $executionStatusId = match ($test->getStatus()) {
        BaseTestRunner::STATUS_SKIPPED => self::EXECUTION_STATUS_ID_BLOCKED,
        BaseTestRunner::STATUS_INCOMPLETE => self::EXECUTION_STATUS_ID_CAUTION,
        BaseTestRunner::STATUS_PASSED => self::EXECUTION_STATUS_ID_PASSED,
        BaseTestRunner::STATUS_FAILURE => self::EXECUTION_STATUS_ID_FAILED,
        BaseTestRunner::STATUS_ERROR => self::EXECUTION_STATUS_ID_FAILED,
        default => self::EXECUTION_STATUS_ID_NOT_RUN,
      };
    }

    //Send the results to SpiraTest
    $testRunId = $this->getImportExport()->recordAutomated(
        $testCaseId, $this->releaseId, $this->testSetId, $startDate, $endDate, $executionStatusId,
        $this->testRunnerName, $testName, $assertCount, $message, $stackTrace);

    //Display the message letting the user know that the results were sent
    printf("\nTest Case '%s' (TC:%d) sent to SpiraTest with status %d - Test Run (TR:%d).\n", $testName, $testCaseId, $executionStatusId, $testRunId);
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
