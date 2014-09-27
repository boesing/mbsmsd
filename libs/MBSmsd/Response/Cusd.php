<?php
/**
 * Cusd.php
 *
 * @package MBSmsd
 * @subpackage Response
 * @author Max Boesing <max@kriegt.es>
 * @since 20120918 15:33
 */
namespace MBSmsd\Response;
use MBSmsd\Converter\Pdu AS PDUConverter;

/**
 * class Cusd
 *
 * @package MBSmsd
 * @subpackage Response
 * @author Max Boesing <max@kriegt.es>
 * @since 20120918 15:33
 */
class Cusd implements ResponseInterface
{
    const       NO_ACTION_REQUIRED      = 0;
    const       FURTHER_ACTION_REQUIRED = 1;
    const       TERMINATED_BY_NETWORK   = 2;
    const       OPERATION_NOT_SUPPORTED = 4;
    const       NETWORK_TIMEOUT         = 5;

    protected   $raw;

    protected   $status;
    protected   $text;
    protected   $extra;
    protected   $regex                  = null;
    protected   $processed              = false;

	/**
	 * Sets the RAW Response
	 *
	 * @param string
	 * @return Cusd
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121012 10:50
	 */
    public function setRAWResponse( $response )
    {
        $this->raw = $response;
        return $this;
    }

	/**
     * Formats the Response to String
     * Using 7-bit or 16-bit UCS2
	 *
	 * @param int
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120918 15:33
	 */
    public function toString( $encoding = PDUConverter::ENCODING_GSM_7BIT )
    {
        $this->process($encoding);
        return $this->text;
    }

	/**
	 * Processes the raw data with given encoding
	 *
	 * @param int
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120918 16:15
	 */
    protected function process( $encoding )
    {
        if( empty($this->raw) ) {
            throw new \RuntimeException("Missing RAW Response!");
        }

        if( $this->processed ) {
            return;
        }

        list( $this->status, $this->text, $this->extra ) = explode(',', substr( $this->raw, strpos($this->raw, ':')+1 ), 3); 
        $this->text = trim($this->text,'"');
        $converter = new PDUConverter;
        switch( $encoding ) {
            case PDUConverter::ENCODING_GSM_7BIT:
                $this->text = $converter->decodeGSM7Bit( $this->text );
            break;
            default:
            case PDUConverter::ENCODING_UCS2:
                $this->text = $converter->decodeUCS2( $this->text );
            break;
        }
        $this->processed = true;
    }

	/**
	 * Returns the match of given Regex
	 *
     * @param string
     * @param int OPTIONAL Default: 16
	 * @return float
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121012 10:28
	 */
    public function getValue( $regex, $encoding = PDUConverter::ENCODING_GSM_7BIT )
    {
        preg_match( "/$regex/", $this->toString( $encoding ), $matches );
        if( !isset($matches[1]) ) {
            trigger_error("Could not match CUSD-Response with given regex \"$regex\"", E_USER_ERROR);
            return 0.00;
        }
        return (float)$matches[1];
    }

	/**
	 * Converts the Response to text
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120918 15:33
	 */
    public function __toString()
    {
        return $this->toString();
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
