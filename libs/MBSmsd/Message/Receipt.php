<?php
/**
 * Receipt.php
 *
 * @package MBSmsd
 * @subpackage Message
 * @author Max Boesing <max@kriegt.es>
 * @since 20121030 13:44
 */
namespace MBSmsd\Message;
use MBSmsd\Message;

/**
 * class Receipt
 *
 * @package MBSmsd
 * @subpackage Message
 * @author Max Boesing <max@kriegt.es>
 * @since 20121030 13:44
 */
class Receipt extends Message
{
    protected   $statusCode;
    protected   $delivered;
    protected   $sent;

	/**
	 * Sets the delivered timestamp
	 *
	 * @param string
	 * @return Receipt
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121116 13:46
	 */
    public function setDeliveredTimestamp( $timestamp )
    {
        $this->delivered = $timestamp;
        return $this;
    }
    
	/**
	 * Returns the delivered Timestamp
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121119 15:10
	 */
    public function getDeliveredTimestamp()
    {
        return $this->delivered;
    }

	/**
	 * sets the sent timestamp
	 *
	 * @param string
	 * @return Receipt
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121116 13:46
	 */
    public function setSentTimestamp( $timestamp )
    {
        $this->sent = $timestamp;
        return $this;
    }

	/**
	 * Returns the sent timestamp
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121119 15:10
	 */
    public function getSentTimestamp()
    {
        return $this->sent;
    }

	/**
	 * Sets the StatusCode of this Receipt
	 *
	 * @param int
	 * @return Receipt
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121116 13:46
	 */
    public function setStatusCode( $code )
    {
        $this->statusCode = (int)$code;
        return $this;
    }

	/**
	 * Returns the Status Message for this Receipt
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121116 13:46
	 */
    public function getStatusMessage()
    {
        if( !is_int($this->statusCode) ) {
            throw new \RuntimeException('Missing StatusCode - cannot parse Message');
        }
        $octet = hexdec($this->statusCode);

        $message = "Unknown status";

        switch ($octet)
        {
            case 0: $message = "Ok, short message received by the SME"; break;
            case 1: $message = "Ok, short message forwarded by the SC to the SME but the SC is unable to confirm delivery"; break;
            case 2: $message = "Ok, short message replaced by the SC"; break;
            case 32: $message = "Still trying, congestion"; break;
            case 33: $message = "Still trying, SME busy"; break;
            case 34: $message = "Still trying, no response sendr SME"; break;
            case 35: $message = "Still trying, service rejected"; break;
            case 36: $message = "Still trying, quality of service not available"; break;
            case 37: $message = "Still trying, error in SME"; break;
            case 64: $message = "Error, remote procedure error"; break;
            case 65: $message = "Error, incompatible destination"; break;
            case 66: $message = "Error, connection rejected by SME"; break;
            case 67: $message = "Error, not obtainable"; break;
            case 68: $message = "Error, quality of service not available"; break;
            case 69: $message = "Error, no interworking available"; break;
            case 70: $message = "Error, SM validity period expired"; break;
            case 71: $message = "Error, SM deleted by originating SME"; break;
            case 72: $message = "Error, SM deleted by SC administration"; break;
            case 73: $message = "Error, SM does not exist"; break;
            case 96: $message = "Error, congestion"; break;
            case 97: $message = "Error, SME busy"; break;
            case 98: $message = "Error, no response from sender SME"; break;
            case 99: $message = "Error, service rejected"; break;
            case 100: $message = "Error, quality of service not available"; break;
            case 101: $message = "Error, error in SME"; break;
        }

        return $message;
    }

	/**
	 * Overwrites the __toString of Message
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121116 13:46
	 */
    public function __toString()
    {
        try {
            return $this->getStatusMessage();
        } catch( \Exception $e ) {
            return "Error, missing status code";
        }
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
