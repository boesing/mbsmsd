<?php
/**
 * Cusd.php
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20120918 15:26
 */
namespace MBSmsd\Request;
use MBSmsd\Request\AbstractRequest AS Request;
use MBSmsd\Converter\Pdu AS PDUConverter;

/**
 * class Cusd
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20120918 15:26
 */
class Cusd extends Request implements DelayedResponseInterface, ValidationInterface, AlternateRequestInterface
{
    protected   $code               = "*100#";
    protected   $converted          = false;
    protected   $isAlternateRequest = false;

	/**
	 * Sets the Code we want to send as Request
	 *
	 * @param string 
	 * @return Cusd
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120918 15:26
	 */
    public function setCode( $code ) 
    {
        $this->code = $code;
        return $this;
    }

	/**
     * Returns the AT-Code for CUSD
     * If we specify in the Constructor any code,
     * like *100#, we gonna return the Request.
     * If we did'nt define the code, we just check
     * if +CUSD is available.
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120918 15:26
	 */
    public function __toString()
    {
        if( $this->isAlternateRequest && !$this->converted ) {
            $converter = new PDUConverter;
            $this->code = $converter->encodeGSM7Bit( $this->code );
            $this->converted = true;
        }
        $request = "AT+CUSD=1,\"{$this->code}\",15\r";
        return $request;
    }

	/**
     * AlternateRequestInterface
     * Checks or sets if this is the AlternateRequest
	 *
	 * @param boolean OPTIONAL Default: null
	 * @return boolean
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121017 11:09
	 */
    public function isAlternateRequest( $status=null )
    {
        if( is_bool($status) ) {
            $this->isAlternateRequest = $status;
        }
        return $this->isAlternateRequest;
    }

	/**
	 * Returns the Validation String
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121009 15:41
	 */
    public function validate()
    {
        return "AT+CUSD?\r";
    }

	/**
	 * Returns true because +CUSD has a response
	 *
	 * @return true
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120918 15:57
	 */
    public function hasResponse()
    {
        return true;
    }
    
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
