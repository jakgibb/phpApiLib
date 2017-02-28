<?php
namespace Esp;

class XmlWriter
{

	protected $writer;
	protected $xmlContent;

	public function __construct()
	{
		$this->xmlContent = null;
		$this->writer     = xmlwriter_open_memory();
		xmlwriter_set_indent($this->writer, TRUE);
		xmlwriter_set_indent_string($this->writer, '    ');
	}

	public function openElement($szName)
	{
		xmlwriter_start_element($this->writer, $szName);
	}
	public function closeElement($szName)
	{
		xmlwriter_end_element($this->writer);
	}
	public function textElement($szName, $szValue)
	{
		xmlwriter_write_element($this->writer, $szName, $szValue);
	}
	public function getXmlAsString()
	{
		if ($this->xmlContent == null)
		{
			$this->xmlContent = xmlwriter_flush($this->writer);
		}
		return $this->xmlContent;
	}
}
