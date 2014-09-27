<?php
/**
 * Cmgl.php
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20120927 16:44
 */
namespace MBSmsd\Request;
use MBSmsd\Request\AbstractRequest AS Request;

/**
 * class Cmgl
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20120927 16:44
 */
class Cmgl extends Request implements ValidationInterface
{
    /**
     * Message Status
     */
    const   ST_RECEIVED_UNREAD      = 0;
    const   ST_RECEIVED_READ        = 1;
    const   ST_STORED_UNSENT        = 2;
    const   ST_STORED_SENT          = 3;
    const   ST_LIST_ALL             = 4;

    /**
     * status we want to use for request
     *
     * @var int
     */
    protected   $status             = self::ST_RECEIVED_UNREAD;

	/**
	 * Sets the Status we want messages for
	 *
	 * @param int 
	 * @return Cmgl
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121011 12:22
	 */
    public function setMessageStatus( $status )
    {
        if( !is_int($status) || ($status < 0 || $status > 4) ) {
            throw new \InvalidArgumentException("Invalid Message status provided: \"$status\"");
        }
        $this->status = $status;
        return $this;
    }

	/**
	 * Returns the validation string
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121011 12:22
	 */
    public function validate()
    {
        return "AT+CMGL=?\r";
    }

	/**
	 * Just tells that this Command has a response
	 *
	 * @return true
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121012 10:34
	 */
    public function hasResponse()
    {
        return true;
    }

	/**
	 * Returns the AT-Code for CMGL
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121011 12:22
	 */
    public function __toString()
    {
        return sprintf("AT+CMGL=%d\r", $this->status);
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
