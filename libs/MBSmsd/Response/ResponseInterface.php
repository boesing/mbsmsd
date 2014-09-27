<?php
/**
 * ResponseInterface.php
 *
 * @package MBSmsd
 * @subpackage Response
 * @author Max Boesing <max@kriegt.es>
 * @since 20120918 15:31
 */
namespace MBSmsd\Response;

/**
 * Interface ResponseInterface
 *
 * @package MBSmsd
 * @subpackage Response
 * @author Max Boesing <max@kriegt.es>
 * @since 20120918 15:31
 */
interface ResponseInterface 
{
	/**
     * Should return the Answer as readable string
     * If the Response is encoded in PDU, please
     * decode the string before return!
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120918 15:31
	 */
    public function __toString();

	/**
	 * Should set the RAW Response
	 *
	 * @param string
	 * @return ResponseInterface
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121012 10:49
	 */
    public function setRAWResponse( $response );
}
