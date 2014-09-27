<?php
/**
 * Cnum.php
 *
 * @package MBSmsd
 * @subpackage Response
 * @author Max Boesing <max@kriegt.es>
 * @since 20121013 01:56
 */
namespace MBSmsd\Response;

/**
 * class Cnum
 *
 * @package MBSmsd
 * @subpackage Response
 * @author Max Boesing <max@kriegt.es>
 * @since 20121013 01:56
 */
class Cnum implements ResponseInterface
{
    protected   $raw;
    protected   $number             = ""; 
    protected   $processed          = false;

	/**
	 * Sets the RAW Response
	 *
	 * @param string 
	 * @return Cnum
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121013 01:57
	 */
    public function setRAWResponse( $response )
    {
        $this->raw = $response;
        return $this;
    }

	/**
	 * Process the RAW data
	 *
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121013 01:57
	 */
    protected function process()
    {
        if( empty($this->raw) ) {
            throw new \RuntimeException("Missing RAW AT+CNUM Response");
        }

        if( $this->processed ) {
            return;
        }

        $pos1 = strpos($this->raw,',')+2;
        $pos2 = strrpos($this->raw,',')-$pos1-1;
        $this->number = substr( $this->raw, $pos1, $pos2 );

        $this->processed = true;
    }

	/**
	 * Returns the response
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121013 01:57
	 */
    public function __toString()
    {
        $this->process();
        return $this->number;
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
