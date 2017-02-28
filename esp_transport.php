<?php
namespace Esp;

class Transport
{

	protected $_tServer;
	protected $_tXmlmc;
	protected $_tDav;

	public function __construct($server, $xmlmc, $dav)
	{
		$this->_tServer = $server;
		$this->_tXmlmc  = $xmlmc;
		$this->_tDav    = $dav;
	}

	public function getServerPath()
	{
		return $this->_tServer . $this->_tXmlmc;
	}

  public function getXmlmcPath()
	{
		return $this->_tXmlmc;
	}

	public function getDavPath()
	{
		return $this->_tDav;
	}
}
