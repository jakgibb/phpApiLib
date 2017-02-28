<?php
declare (encoding = 'utf-8');

include "espapi_php/esp_method_call.php";

class TestOfEspMethods
{

	const ESP_ADDRESS = "https://eurapi.hornbill.com/yourinstancename/";
	const API_KEY = "yourAPIKey";

	function buildResponse($strMethod, $strService, $strType, $strResponse = "")
	{
		$arrReturn = array(
			"method" => $strMethod,
			"service" => $strService,
			"type" => $strType,
			"response" => $strResponse
		);
		return $arrReturn;
	}

	function testSimpleType()
	{
		$arrReturn     = array();
		$the_transport = new \Esp\Transport(self::ESP_ADDRESS, "xmlmc", "dav");
		$mc            = new \Esp\MethodCall($the_transport);

		$mc->addParam("userId", "admin");
		$mc->addPasswordParam("password", "password");
		$mc->invoke("session", "userLogon");
		if (is_null($mc->getReturnParamAsString("sessionId")))
		{
			array_push($arrReturn, $this->buildResponse("session", "userLogon", "ERROR", $mc->getError()));
		}
		else
		{
			array_push($arrReturn, $this->buildResponse("session", "userLogon", "SUCCESS", $mc->getReturnParamAsString("sessionId")));
		}
		$mc->invoke("session", "userLogoff");
		if ($mc->getError())
		{
			array_push($arrReturn, $this->buildResponse("session", "userLogoff", "ERROR", $mc->getError()));
		}
		else
		{
			array_push($arrReturn, $this->buildResponse("session", "userLogoff", "SUCCESS"));
		}
		return $arrReturn;
	}

	/*
	*	Use API key to generate session instead of session::userLogon & session::userLogoff
	*/
	function testAPIKeySession()
	{
		$arrReturn        = array();
		$strCheckSpelling = "Hornbill ! $ % ^  * ( ) _ + @ ~ . , / ' ' < > & \" 'Service Manager' Spelling Mitsake";

		$the_transport = new \Esp\Transport(self::ESP_ADDRESS, "xmlmc", "dav");
		$mc            = new \Esp\MethodCall($the_transport);

		$mc->setAPIKey(self::API_KEY);
		$mc->invoke("session", "getSessionInfo");
		$strSessionID = $mc->getReturnParamAsString("sessionId");

		array_push($arrReturn, $this->buildResponse("session", "getSessionInfo", "DEBUG", "Session ID: " . $strSessionID));

		return $arrReturn;
	}

	function testReturnParamAsCountAndArray()
	{
		$arrReturn        = array();
		$strCheckSpelling = "Hornbill ! $ % ^  * ( ) _ + @ ~ . , / ' ' < > & \" 'Service Manager' Spelling Mitsake";
		$the_transport    = new \Esp\Transport(self::ESP_ADDRESS, "xmlmc", "dav");
		$mc               = new \Esp\MethodCall($the_transport);
		$mc->setAPIKey(self::API_KEY);

		$mc->addParam("sentenceText", $strCheckSpelling);
		$mc->addParam("language", "en-GB");
		$mc->addParam("suggestWords", "true");

		$mc->invoke("utility", "spellCheck");

		array_push($arrReturn, $this->buildResponse("utility", "spellCheck", "DEBUG", "String to Spell Check: " . $strCheckSpelling));

		//Get returned param count
		$count = $mc->getReturnParamCount("spellCheckErrorItem");
		if ($count != 2)
		{
			array_push($arrReturn, $this->buildResponse("utility", "spellCheck", "ERROR", "Count of spelling mistakes: " . $count));
		}
		else
		{
			array_push($arrReturn, $this->buildResponse("utility", "spellCheck", "SUCCESS", "Count of spelling mistakes: " . $count));
		}
		//Get complex returned param
		$xmldom      = $mc->getReturnParamAsComplexType("spellCheckErrorItem", 1);
		$strMistake1 = $xmldom->textContent;
		array_push($arrReturn, $this->buildResponse("utility", "spellCheck", "SUCCESS", "First spelling mistake & suggestions: " . $strMistake1));

		$xmldom      = $mc->getReturnParamAsComplexType("spellCheckErrorItem", 2);
		$strMistake2 = $xmldom->textContent;
		array_push($arrReturn, $this->buildResponse("utility", "spellCheck", "SUCCESS", "Second spelling mistake & suggestions: " . $strMistake2));

		return $arrReturn;
	}

	function testBuildXMLAndComplexType()
	{
		$arrReturn     = array();
		$the_transport = new \Esp\Transport(self::ESP_ADDRESS, "xmlmc", "dav");
		$mc            = new \Esp\MethodCall($the_transport);
		$mc->setAPIKey(self::API_KEY);

		$doc = new \Esp\XmlWriter();
		$mc->addParam("severityLevel", "all");
		$doc->openElement("logMessageGroup");
		$doc->textElement("general", "true");
		$doc->textElement("system", "true");
		$doc->textElement("process", "true");
		$doc->textElement("security", "true");
		$doc->textElement("comms", "true");
		$doc->textElement("database", "true");
		$doc->textElement("sql", "true");
		$doc->textElement("perf", "true");
		$doc->textElement("scripts", "true");
		$doc->closeElement("logMessageGroup");
		$mc->addParam($doc);
		$mc->addParam("enableResultXmlSchemaValidation", "true");
		$mc->addParam("enableDatabaseSecurityHinting", "true");

		$mc->invoke("session", "setDiagnosticsLevel");
		if ($mc->getError())
		{
			array_push($arrReturn, $this->buildResponse("session", "setDiagnosticsLevel", "ERROR", $mc->getError()));
		}
		else
		{
			array_push($arrReturn, $this->buildResponse("session", "setDiagnosticsLevel", "SUCCESS"));
		}

		return $arrReturn;
	}

	function testReturnXML()
	{
		$arrReturn     = array();
		$the_transport = new \Esp\Transport(self::ESP_ADDRESS, "xmlmc", "dav");
		$mc            = new \Esp\MethodCall($the_transport);

		$mc->setAPIKey(self::API_KEY);

		$mc->invoke("session", "getSessionInfo");
		if ($mc->getError())
		{
			array_push($arrReturn, $this->buildResponse("session", "getSessionInfo", "ERROR", $mc->getError()));
		}
		else
		{
			array_push($arrReturn, $this->buildResponse("session", "getSessionInfo", "SUCCESS"));
		}

		$xmldom   = $mc->getReturnParamsComplexType();
		$nElement = $xmldom->getElementsByTagName("isGuestSession");
		array_push($arrReturn, $this->buildResponse("session", "getSessionInfo", "DEBUG", "isGuestSession: " . $nElement->item(0)->textContent));

		$nElement = $xmldom->getElementsByTagName("sessionId");
		array_push($arrReturn, $this->buildResponse("session", "getSessionInfo", "DEBUG", "sessionId: " . $nElement->item(0)->textContent));

		$xmldom   = $mc->getReturnParamAsComplexType("currentLanguage");
		$nElement = $xmldom->getElementsByTagName("language");
		array_push($arrReturn, $this->buildResponse("session", "getSessionInfo", "DEBUG", "currentLanguage: " . $nElement->item(0)->textContent));

		return $arrReturn;
	}

	function testException()
	{
		$arrReturn     = array();
		$the_transport = new \Esp\Transport(self::ESP_ADDRESS, "xmlmc", "dav");
		$mc            = new \Esp\MethodCall($the_transport);

		$mc->addParam("userId", "admin");
		$mc->addPasswordParam("password", "password");
		try
		{
			$mc->invoke("session", "userLogn");
		}
		catch (Esp\TransportFailureException $ex)
		{
			array_push($arrReturn, $this->buildResponse("session", "userLogn", "ERROR", "Transport Exception Failure"));
		}
		catch (Esp\RequestFailureException $ex)
		{
			array_push($arrReturn, $this->buildResponse("session", "userLogn", "SUCCESS", "Request Failure as expected as userLogn service does not exist"));
		}
		return $arrReturn;
	}

	function test_i18n()
	{
		$arrReturn     = array();
		$the_transport = new \Esp\Transport(self::ESP_ADDRESS, "xmlmc", "dav");
		$mc            = new \Esp\MethodCall($the_transport);
		$mc->setAPIKey(self::API_KEY);-

		$strGerman = "Dies ist eine Rechtschreibprufung";
		array_push($arrReturn, $this->buildResponse("utility", "spellCheck", "DEBUG", "German string to Spell Check: " . $strGerman));
		$mc->addParam("sentenceText", $strGerman);
		$mc->addParam("language", "de-DE");
		$mc->addParam("suggestWords", "true");
		$mc->invoke("utility", "spellCheck");

		$count = $mc->getReturnParamCount("spellCheckErrorItem");
		if ($count != 1)
		{
			array_push($arrReturn, $this->buildResponse("utility", "spellCheck", "ERROR", "Count of spelling mistakes: " . $count));
		}
		else
		{
			array_push($arrReturn, $this->buildResponse("utility", "spellCheck", "SUCCESS", "Count of spelling mistakes: " . $count));
		}
		$xmldom      = $mc->getReturnParamAsComplexType("spellCheckErrorItem", 1);
		$strMistake1 = $xmldom->textContent;
		array_push($arrReturn, $this->buildResponse("utility", "spellCheck", "SUCCESS", "Spelling mistake & suggestions: " . $strMistake1));

		return $arrReturn;
	}

}

//Start running all the test cases to produce the report.
$test = new TestOfEspMethods();

//testSimpleType
echo ("<b>testSimpleType</b>");
echo ("<pre>");
print_r($test->testSimpleType());
echo ("</pre>");

//testAPIKeySession
echo ("<b>testAPIKeySession</b>");
echo ("<pre>");
print_r($test->testAPIKeySession());
echo ("</pre>");

//testReturnParamAsCountAndArray
echo ("<b>testReturnParamAsCountAndArray</b>");
echo ("<pre>");
print_r($test->testReturnParamAsCountAndArray());
echo ("</pre>");

//testBuildXMLAndComplexType
echo ("<b>testBuildXMLAndComplexType</b>");
echo ("<pre>");
print_r($test->testBuildXMLAndComplexType());
echo ("</pre>");

//testReturnXML
echo ("<b>testReturnXML</b>");
echo ("<pre>");
print_r($test->testReturnXML());
echo ("</pre>");

//testException
echo ("<b>testException</b>");
echo ("<pre>");
print_r($test->testException());
echo ("</pre>");

//test_i18n
echo ("<b>test_i18n</b>");
echo ("<pre>");
print_r($test->test_i18n());
echo ("</pre>");
