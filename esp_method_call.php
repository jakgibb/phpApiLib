<?php
namespace Esp;

require_once("esp_http_transaction.php");
require_once("esp_xmlmc_transaction.php");
require_once("esp_transport.php");
require_once("esp_xml_type_writer.php");
require_once("esp_exception.php");

/**
 * Description of ESPMethodCall
 * Invokes the ESP API
 *
 * @author TrevorH
 */
class MethodCall extends EspXmlmcTransaction
{

	protected $methodCall;
	protected $params;
	protected $startXml = '<methodCall>';
	protected $endXml = '</methodCall>';

	public function __construct($transportObj)
	{
		parent::__construct($transportObj->getServerPath());
		$this->methodCall = simplexml_load_string('<methodCall></methodCall>', null, LIBXML_NOERROR | LIBXML_NOWARNING);
	}

	public final function paramsObj()
	{
		if (!isset($this->params))
		{
			$pos = strpos($this->methodCall->asXML(), "<params>");
			if ($pos === false)
			{
				$this->methodCall->addChild('params');
			}
			$child        = $this->methodCall->children();
			$this->params = $child[0];
		}
		return $this->params;
	}

	public function sxml_append(\SimpleXMLElement $to, \SimpleXMLElement $from)
	{
		$toDom   = dom_import_simplexml($to);
		$fromDom = dom_import_simplexml($from);
		$toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
	}

	public function addParam($name, $value = null, $parent = null)
	{
		if ($name instanceof \Esp\XmlWriter)
		{
			if ($this->params == null)
			{
				$szXml = $this->methodCall->asXML();
				$pos   = strpos($szXml, "</params>");
				if ($pos === false)
				{
					$pos              = "<params>" . $name->getXmlAsString() . "</params>";
					$this->methodCall = simplexml_load_string($this->startXml . $pos . $this->endXml);
				}
				else
				{
					$fullXml          = substr($szXml, 0, $pos) . $name->getXmlAsString() . substr($szXml, $pos, strlen($szXml));
					$this->methodCall = simplexml_load_string($fullXml);
				}
			}
			else
			{
				$this->sxml_append($this->params, simplexml_load_string($name->getXmlAsString()));
			}
		}
		else if ($value == null)
		{
			$name = utf8_encode($name);
			$pos  = strpos($name, "<params>");
			if ($pos == false)
			{
				$name = "<params>" . $name . "</params>";
			}
			$this->methodCall = simplexml_load_string($this->startXml . $name . $this->endXml);
		}
		else if ($value instanceof \Esp\XmlWriter)
		{
			$nameStartTag     = '<' . $name . '>';
			$nameEndTag       = '</' . $name . '>';
			$this->methodCall = simplexml_load_string($this->startXml . $nameStartTag . $value->getXmlAsString() . $nameEndTag . $this->endXml);
		}
		else if (is_array($name))
		{
			/*
			 * Array of data where the key is the param name and the value is the param value
			 * or another array containing subParams
			 */
			if (is_null($parent))
				$parent = $this->paramsObj();
			foreach ($params as $key => $value)
			{
				if (is_array($value))
				{
					if ($key[0] === '@')
					{
						$key = ltrim($key, '@');
						foreach ($value as $item)
							$this->addParam(array(
								$key => $item
							), $parent);
					}
					else
					{
						$this->addParam($value, $parent->addChild($key));
					}
				}
				else
				{
					$parent->addChild($key, $value);
				}
			}
		}
		else if (!isset($parent))
		{
			if ($value instanceof DateTime)
			{
				$value = $value->format("Y-m-d H:i:sZ");
			}
			else
			{
				$value = htmlspecialchars($value);
				$value = utf8_encode($value);
			}
			$name = utf8_encode($name);
			$this->paramsObj()->addChild($name, $value);
			return $this->paramsObj()->$name;
		}
		else
		{
			$name  = utf8_encode($name);
			$value = utf8_encode($value);
			$parent->addChild($name, $value);
			return $parent->$name;
		}
	}

	public function addPasswordParam($name, $value = null, $parent = null)
	{
		if (!isset($parent))
		{
			$this->paramsObj()->addChild($name, base64_encode($value));
			return $this->paramsObj()->$name;
		}
		else
		{
			$parent->addChild($name, base64_encode($value));
			return $parent->$name;
		}
	}

	/**
	 * Returns param object as an simpleXmlelement
	 * @param string containing the param name
	 */
	public function getParam($paramObj)
	{
		if (!isset($this->paramsObj()->$paramObj))
		{
			$this->paramsObj()->addChild($paramObj);
		}
		return $this->paramsObj()->$paramObj;
	}

	public function setAPIKey($apiKey)
	{
		$this->setKey($apiKey);
	}

	public function invoke($service, $method)
	{
		$this->methodCall->addAttribute("service", $service);
		$this->methodCall->addAttribute("method", $method);
		$xml = $this->methodCall->asXML();
		$xml = str_replace('<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>', $xml);
		$this->setService($service . '/');
		$this->invokeRequest($xml);
		//Reset
		$this->methodCall = simplexml_load_string('<methodCall></methodCall>', null, LIBXML_NOERROR | LIBXML_NOWARNING);
		unset($this->params);
	}
}
