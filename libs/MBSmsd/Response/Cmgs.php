<?php
/**
 * Cmgs.php
 *
 * @package MBSmsd
 * @subpackage Response
 * @author Max Boesing <max@kriegt.es>
 * @since 20121122 10:35
 */
namespace MBSmsd\Response;

/**
 * class Cmgs
 *
 * @package MBSmsd
 * @subpackage Response
 * @author Max Boesing <max@kriegt.es>
 * @since 20121122 10:35
 */
class Cmgs implements ResponseInterface
{
    protected   $raw;
    protected   $processed      = false;
    protected   $reference      = 0;
    protected   $smscTimestamp  = "";

	/**
	 * Sets the RAW Response
	 *
	 * @param string
	 * @return Cmgs
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121122 10:35
	 */
    public function setRAWResponse( $response )
    {
        $this->raw = $response;
        return $this;
    }

	/**
	 * Klassendefinition
	 *
	 * @param 
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20130913 14:33
	 */
    protected function process() 
    {
        if( empty($this->raw) ) {
            throw new \RuntimeException("Missing RAW Response!");
        }

        if( $this->processed ) {
            return;
        }

        $this->raw = substr( $this->raw, 0, strrpos( $this->raw, 'OK') );

        $explode = explode(',', substr( trim($this->raw), strpos( $this->raw, ':' )+1 ), 2);
        
        switch( count($explode) ) {
            case 1:
                list( $this->reference ) = $explode;
            break;
            default:
                list( $this->reference, $this->smscTimestamp ) = $explode;
        }

        $this->processed = true;
    }

	/**
	 * Klassendefinition
	 *
	 * @param 
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121122 10:35
	 */
    public function __toString()
    {
        $this->process();
        $response = "SMS sent, refid: {$this->reference}";
        if( $this->smscTimestamp != '' ) {
            $response .= ", SMSCenter received message on {$this->smscTimestamp}";
        }
        return $response;
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
