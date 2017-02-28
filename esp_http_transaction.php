<?php
/*
 * Base class for all ESP web server communication
 * NOTE: This class is abstract the invokeRequest method is implented in either the
 * esp_curl_transaction.php file or the esp_socket_transaction.php file depending on the
 * installed componenets
 */
namespace Esp;
class EspHttpTransaction
{
	//Request
	protected $requestURL;
	protected $requestHeaders;
	protected $queryParams;
	protected $service;

	//Response
	protected $responseHeader;
	protected $responseBody;
	//Session
	protected $sessionId;
	protected $sessionExpiry;
	protected $espSessionCookie;
	protected $sessionUpdated;
	protected $error;

	protected $sessionState;

	public function __construct($requestURL, $espSessionCookie = "ESPSessionState")
	{
		$this->requestURL       = $requestURL;
		$this->espSessionCookie = $espSessionCookie;
		$this->requestHeaders   = array();
		$this->queryParams      = array();
		$this->sessionUpdated   = false;
	}

	public function __destruct()
	{
		;
	}

	/**
	 * @param [string] $requestURL Top level url of the webserver to send the request to
	 */
	public function setRequestURL($requestURL)
	{
		$this->requestURL = $requestURL;
	}

	public function getRequestURL()
	{
		return $this->requestURL;
	}


	/**
	 * @param [string] $value Service on the request Url to send the request to (appended tov request url)
	 */
	public function setService($value)
	{
		$this->service = $value;
	}

	public function getService()
	{
		return $this->service;
	}

	/**
	 * @param [string] $value The session State for the Esp web transaction to commence
	 */
	public function setSessionState($value)
	{
		$this->sessionState = $value;
	}

	/**
	 * Will contain the last session state returned from the webserver, or the session state set in the users cookie
	 * @Return [string] The session state for the Esp web transaction
	 */
	public function getSessionState()
	{
		return $this->sessionState;
	}

	/**
	 * Add Query parameter to the request to be sent to the webserver
	 * @param string The param key
	 * @param string The param value
	 */
	public function addQueryParam($key, $value)
	{
		$this->queryParams[$key] = $value;
	}

	/**
	 * Add an array of Query parameter to the request to be sent to the webserver
	 * @param array $params - array of keys and values (eg addQueryParams($_GET))
	 */
	public function addQueryParams($params)
	{
		if (is_array($params))
		{
			foreach ($params as $key => $value)
			{
				$this->addQueryParam($key, $value);
			}
		}
	}

	/**
	 * Add Request headers to the next request
	 * @param [string] $headerName - Name of the header
	 * @param [string] $headerValue - Value of the header
	 */
	public function addRequestHeader($headerName, $headerValue)
	{
		$this->requestHeaders[] = $headerName . ": " . $headerValue;
	}

	public function getRequestHeader($headerName)
	{
		foreach ($this->requestHeaders as $key => $header)
		{
			$headerArray = explode(": ", $header);
			if ($headerArray[0] === $headerName)
			{
				return $headerArray[1];
			}
		}
		return null;
	}

	public function removeRequestHeader($headerName)
	{
		foreach ($this->requestHeaders as $key => $header)
		{
			$headerArray = explode(": ", $header);
			if ($headerArray[0] === $headerName)
			{
				unset($this->requestHeaders[$key]);
			}
		}
	}

	/**
	 * @Return [String] The Response from the web server
	 * */
	public function getResponseBody()
	{
		if ($this->responseOK() || !empty($this->responseBody))
		{
			return $this->responseBody;
		}
		else
		{
			return $this->getResponseStatus();
		}
	}

	/**
	 * @Return [boolean] True if the HTTP Request was a success (response head beings with 2)
	 * */
	public function responseOK()
	{
		$header = $this->getResponseHeaders();
		return (preg_match("/[1-2][0-9][0-9]/", $header[0]));
	}

	public function getResponseStatusCode()
	{
		$header = $this->getResponseHeaders();
		if (preg_match("/[0-9][0-9][0-9]/", $header[0], $code))
		{
			return (int) $code[0];
		}
		else
		{
			return 0;
		}
	}

	public function getResponseStatus()
	{
		$header = $this->getResponseHeaders();
		return $header[0];
	}

	public function getResponseHeaders()
	{
		if (!isset($this->responseHeader))
		{
			return null;
		}
		else
		{
			return $this->responseHeader;
		}
	}

	public function getResponseHeader($headerName)
	{
		$headers = $this->getResponseHeaders();
		foreach ($headers as $header)
		{
			if (strpos($header, $headerName) === 0)
			{
				return trim(subStr($header, (strlen($headerName) + 1)));
			}
		}
		return null;
	}

	/**
	 * Send the current request to the web server
	 * @param [String] data to be posted in the body of the request
	 * @param [String] http method
	 * @return [String] The response body
	 * */
	public function invokeRequest($data = null, $method = 'POST')
	{
		$this->error = null;
		//init curl
		$ch          = curl_init();

		/*
		Un-comment curl_setopt below to avoid peer cert check - JUST FOR TESTING!
		THIS SHOULD NOT BE USED IN PRODUCTION - A CERTIFICATE SHOULD BE APPLIED AND BOTH
		APACHE & PHP CORRECTLY CONFIGURED RATHER THAN AVOIDING THE CURL PEER CERT VERIFICATION
		*/
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		/*
		END OF AVOID CERT CHECKING
		*/

		//set url
		if (isset($this->service))
		{
			$url = $this->requestURL . '/' . $this->service;
		}
		else
		{
			$url = $this->requestURL;
		}
		if (isset($this->queryParams) && sizeof($this->queryParams) > 0)
		{
			$sep = "?";
			foreach ($this->queryParams as $queryKey => $queryValue)
			{
				$url = $url . $sep . urlencode($queryKey) . "=" . urlencode($queryValue);
				$sep = "&";
			}
		}

		try
		{
			curl_setopt($ch, CURLOPT_URL, $url);

			//set session state
			$sessionState = $this->getSessionState();
			if (isset($sessionState))
			{
				curl_setopt($ch, CURLOPT_COOKIE, $this->espSessionCookie . "=" . $sessionState);
			}
			//Set the method (note webdav uses custom requests)

			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			if ($method == "post")
				curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);

			// 5 second connection timeout
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			// return the transfer as a string
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			// return the headers
			curl_setopt($ch, CURLOPT_HEADER, true);

			// Figure out the client IP address
			$ip = "";
			if (!empty($_SERVER['HTTP_CLIENT_IP']))
			{
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			}
			elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
			{
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
			else
			{
				$ip = $_SERVER['REMOTE_ADDR'];
			}

			//Set Request Headers
			$reqHeaders = $this->requestHeaders;

			// We only need to send the forwarded-for header if our client IP address is
			// different to our server-ip address
			if ($_SERVER["SERVER_ADDR"] != $ip)
				$reqHeaders[] = "X-Forwarded-For: " . $ip;

			if (isset($data))
			{
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				$reqHeaders[] = 'Content-Length: ' . strlen($data);
			}

			curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);

			// This will ensure our server knows about the client user agent
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);

			//Execute the request
			$response    = curl_exec($ch);
			$curlError   = curl_error($ch);
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			curl_close($ch);
			$headers            = substr($response, 0, $header_size);
			$this->responseBody = substr($response, $header_size);
			if (isset($headers))
			{
				// Get our headers into an array
				$this->responseHeader = explode("\r\n", $headers);
			}
			if ($response === false)
			{
				//Error handling
				throw (new TransportFailureException("Transport operation failed. " . $url . ":" . $curlError));
			}
			else if ($this->responseOK() == 0)
			{
				//Error handling
				throw (new TransportFailureException("Transport operation failed. " . $this->responseBody));
			}

		}
		catch (TransportFailureException $ex)
		{
			throw $ex;
		}
		//Store Cookie
		$this->storeResponseSessionCookie();

		return $this->responseBody;
	}

	protected function storeResponseSessionCookie()
	{
		$this->sessionUpdated = false;
		foreach ($this->getResponseHeaders() as $value)
		{
			if (preg_match('~^Set-Cookie:\s+.*' . $this->espSessionCookie . '=([^;]*)~mi', $value, $cookie))
			{
				// if (preg_match("/Set-Cookie: " . $this->espSessionCookie . "=(.*?)($|;)(.*?expires=(.*?)($|;)|.*)/si", $value, $cookie)) {
				$this->setSessionState($cookie[1]);
				$this->sessionUpdated = true;
				break;
			}
		}
	}

	public function sessionUpdated()
	{
		return $this->sessionUpdated;
	}

	public function getSessionCookieName()
	{
		return $this->espSessionCookie;
	}

	public function getError()
	{
		if (isset($error))
		{
			return $error;
		}
		else if (!$this->responseOk())
		{
			return "Request Failed, server responded : " . $this->getResponseStatus();
		}
		else
		{
			return null;
		}
	}

}
