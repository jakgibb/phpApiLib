<?php
namespace Esp;

class EspXmlmcTransaction extends EspHttpTransaction
{
	const XMLMC_CONTENT_TYPE = 'text/xmlmc; charset=utf-8';
	const XMLMC_ACCEPT = 'text/xmlmc';
	const JSON_ACCEPT = 'text/json';

	protected $domObject;
	protected $bodyJSON;
	protected $xmlmcResponseOK;
	protected $types;
	protected $typeSchemas;
	protected $espConfig;
	protected $jsonOnly;

	public function __construct($requestUrl, $cookieName = "ESPSessionState")
	{
		parent::__construct($requestUrl, $cookieName);
		$this->addRequestHeader("Content-Type", self::XMLMC_CONTENT_TYPE);
		$this->jsonOnly = false;
	}

	public function xmlmcResponseOK()
	{
		if (!isset($this->xmlmcResponseOK))
		{
			if (!$this->jsonOnly)
			{
				if (parent::responseOK() && preg_match('/<methodCallResult.*?status="(.*)">/', $this->getResponseBody(), $status))
				{
					if ($status[1] == "ok")
					{
						$this->xmlmcResponseOK = true;
					}
					else
					{
						$this->xmlmcResponseOK = false;
					}
				}
				else
				{
					$this->xmlmcResponseOK = false;
				}
			}
			else
			{
				return substr($this->getResponseBody(), 0, 15) == '{"@status":true';
			}
		}
		return $this->xmlmcResponseOK;
	}

	public function getReturnParamAsString($path)
	{
		if (!isset($this->domObject))
		{
			$this->domObject = new \DOMDocument();
			$this->domObject->loadXML($this->getResponseBody());
		}
		$xpath    = new \DOMXPath($this->domObject);
		$elements = $xpath->query("//methodCallResult/params/" . $path);
		return $elements->item(0)->nodeValue;
	}
	public function getReturnParamCount($path)
	{
		if (!isset($this->domObject))
		{
			$this->domObject = new \DOMDocument();
			$this->domObject->loadXML($this->getResponseBody());
		}
		$xpath    = new \DOMXPath($this->domObject);
		$elements = $xpath->query("//methodCallResult/params/" . $path);
		return $elements->length;
	}
	public function getReturnParamAsStringArray($path)
	{
		if (!isset($this->domObject))
		{
			$this->domObject = new \DOMDocument();
			$this->domObject->loadXML($this->getResponseBody());
		}
		$xpath    = new \DOMXPath($this->domObject);
		$elements = $xpath->query("//methodCallResult/params/" . $path);
		$len      = $elements->length;
		for ($pos = 0; $pos < $len; $pos++)
		{
			$itemArray[$pos] = $elements->item($pos)->nodeValue;
		}
		return $itemArray;
	}
	public function getReturnParamsComplexType()
	{
		if (!isset($this->domObject))
		{
			$this->domObject = new \DOMDocument();
			$this->domObject->loadXML($this->getResponseBody());
		}
		return $this->domObject;
	}
	public function getReturnParamAsComplexType($path = null, $nodePosition = 0)
	{
		if (!isset($this->domObject))
		{
			$this->domObject = new \DOMDocument();
			$this->domObject->loadXML($this->getResponseBody());
		}
		if ($path == null)
		{
			return $this->domObject;
		}
		$xpath    = new \DOMXPath($this->domObject);
		$elements = $xpath->query("//methodCallResult/params/" . $path);
		$len      = $elements->length;
		//Create a new document
		$newDoc   = new \DOMDocument();
		// The node we want to import to a new document
		if ($nodePosition > 0)
		{
			$nodePosition = $nodePosition - 1;
		}
		if ($len > $nodePosition)
		{
			$node = $elements->item($nodePosition);
			// Import the node, and all its children, to the document
			$node = $newDoc->importNode($node, true);
			// And then append it to the "<root>" node
			$newDoc->appendChild($node);
		}

		return $newDoc;
	}

	public function hasSessionTimedOut()
	{
		$xml = $this->getResponseBody();
		return preg_match('!<methodCallResult.*?status="fail">.*?<code>0005</code>!si', $xml);
	}

	/**
	 * Add API key to request headers for API key session authorisation
	 * @param [string] apiKey - The API Key
	 */
	public function setKey($apiKey)
	{
		$this->addRequestHeader("Authorization", "ESP-APIKEY " . $apiKey);
	}

	public function invokeRequest($data = null, $method = 'POST')
	{
		$this->addRequestHeader("Accept", self::XMLMC_ACCEPT);
		unset($this->domObject);
		unset($this->bodyJSON);
		unset($this->xmlmcResponseOK);
		try
		{
			parent::invokeRequest($data, $method);
			if (!$this->xmlmcResponseOK())
			{
				throw (new RequestFailureException($this->getResponseBody()));
			}
		}
		catch (RequestFailureException $ex)
		{
			throw $ex;
		}
	}

	public function setService($value)
	{
		if (substr($value, -1) != '/')
		{
			$value .= '/';
		}
		$this->service = $value;
	}

	public function getError()
	{
		if (!parent::responseOk())
		{
			return parent::getError();
		}
		else if (!$this->xmlmcResponseOK())
		{
			if (!$this->bodyJSON)
			{
				try
				{
					return $this->methodCallResult()->state->error;
				}
				catch (Exception $e)
				{
					return EspException::SERVER_RESPONSE_INVALID;
				}
			}
			else
			{
				return EspException::JSON_REQUEST_ONLY;
			}
		}
		else
		{
			return null;
		}
	}
}
