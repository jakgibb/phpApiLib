<?php
namespace Esp;

/**
 * Define a custom exception class
 */
class RequestFailureException extends \Exception
{
	// Redefine the exception so message isn't optional
	public function __construct($message = "Unknown error", $code = 0, Exception $previous = null)
	{
		// some code

		// make sure everything is assigned properly
		parent::__construct($message, $code, $previous);
	}

	// custom string representation of object
	public function __toString()
	{
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

	public function getResponse()
	{
		//Return the RESPONSE as DOM object.
		$domObject = new \DOMDocument();
		$domObject->loadXML($this->message);
		return $domObject;
	}
}

/**
 * Define a custom exception class
 */
class TransportFailureException extends \Exception
{
	// Redefine the exception so message isn't optional
	public function __construct($message = "Unknown error", $code = 0, Exception $previous = null)
	{
		// some code

		// make sure everything is assigned properly
		parent::__construct($message, $code, $previous);
	}

	// custom string representation of object
	public function __toString()
	{
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

}
