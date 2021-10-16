<?php
/**
 * Provides a facade for recording automated results against SpiraTest
 * 
 * @author		Inflectra Corporation
 * @version		3.2.0
 *
 */
 
 class SpiraListener_ImportExport
 {
  /* Class constants */
  
  //define the web-service namespace and URL suffix constants
	const WEB_SERVICE_NAMESPACE = "http://www.inflectra.com/SpiraTest/Services/v2.2/";
	const WEB_SERVICE_URL_SUFFIX = "/Services/v2_2/ImportExport.asmx";

  /* Class properties */
  
  /*
    The base url of the Spira web service
  */
  protected $baseUrl;
  public function getBaseUrl ()
  {
    return $this->baseUrl;
  }
  public function setBaseUrl ($value)
  {
    $this->baseUrl = $value;
  }

  /*
    The user name of the Spira account accessing the web service
  */
  protected $userName;
  public function getUserName ()
  {
    return $this->userName;
  }
  public function setUserName ($value)
  {
    $this->userName = $value;
  }

  /*
    The password of the Spira account accessing the web service
  */
  protected $password;
  public function getPassword ()
  {
    return $this->password;
  }
  public function setPassword ($value)
  {
    $this->password = $value;
  }
  
  /*
    The ID of the project we're returning results against
  */
  protected $projectId;
  public function getProjectId ()
  {
    return $this->projectId;
  }
  public function setProjectId ($value)
  {
    $this->projectId = $value;
  }
  
  /* Function that actually records the results in SpiraTest */
  public function recordAutomated($testCaseId, $releaseId, $testSetId, $startDate, $endDate, $executionStatusId, $testRunnerName, $testName, $assertCount, $message, $stackTrace)
  { 
    //Instantiante the SOAP client class
    $url = $this->baseUrl . SpiraListener_ImportExport::WEB_SERVICE_URL_SUFFIX;
    $namespace = SpiraListener_ImportExport::WEB_SERVICE_NAMESPACE;
    $clientOptions = array(
      "location" => $url,
      "uri" => $namespace,
      "trace" => 0,
      "exceptions" => 0,
      "use" => SOAP_LITERAL,
      "style" => SOAP_RPC
      );
    $soapClient = new SoapClient(null, $clientOptions);
      
    //Now call the test run method
    $options = array(
      "soapaction" => $namespace . "TestRun_RecordAutomated2",
      );
   
    //For PHP soap calls to work correctly against .NET, need to prefix with namespace alias (ns1)
    $nsPrefix = "ns1:";
    
    $params = array(
      new SoapParam ($this->userName, $nsPrefix . "userName"),
      new SoapParam ($this->password, $nsPrefix . "password"),
      new SoapParam ($this->projectId, $nsPrefix . "projectId"),
      new SoapParam (-1, $nsPrefix . "testerUserId"),
      new SoapParam ($testCaseId, $nsPrefix . "testCaseId"),
      new SoapParam (date(DateTime::W3C, $startDate), $nsPrefix . "startDate"),
      new SoapParam (date(DateTime::W3C, $endDate), $nsPrefix . "endDate"),
      new SoapParam ($executionStatusId, $nsPrefix . "executionStatusId"),
      new SoapParam ($testRunnerName, $nsPrefix . "runnerName"),
      new SoapParam ($testName, $nsPrefix . "runnerTestName"),
      new SoapParam ($assertCount, $nsPrefix . "runnerAssertCount"),
      new SoapParam ($message, $nsPrefix . "runnerMessage"),
      new SoapParam ($stackTrace, $nsPrefix . "runnerStackTrace")
      );

    //testSetId and releaseId are nullable so need to convert -1 values to NULLs
    if ($releaseId > 0)
    {
      $params[] = new SoapParam ($releaseId, $nsPrefix . "releaseId");
    }
    if ($testSetId > 0)
    {
      $params[] = new SoapParam ($testSetId, $nsPrefix . "testSetId");
    }
      
    $testRunId = $soapClient->__soapCall("TestRun_RecordAutomated2", $params, $options);
    
    //Used for debugging only - requires trace=true set during soap-client instantiation
   /* 
    $fp = fopen('SoapRequest.xml', 'w');
    //fprintf($fp, "%s\n", $soapClient->__getLastRequestHeaders());
    fprintf($fp, "%s\n", $soapClient->__getLastRequest());
    fclose($fp);
    $fp = fopen('SoapResponse.xml', 'w');
    fprintf($fp, "%s\n", $soapClient->__getLastResponse());
    fclose($fp);*/
        
    return $testRunId;
  }
 }