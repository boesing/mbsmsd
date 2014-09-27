<?php
/**
 * Cmgf.php
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20140115 12:03
 */
namespace MBSmsd\Request;
use MBSmsd\Request\AbstractRequest AS Request;

/**
 * class Cmgf
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20140115 12:03
 */
class Cmgf extends Request implements ValidationInterface
{
    /**
     * Modes
     */
    const       MODE_PDU            = 0;
    const       MODE_TEXT           = 1;

    /**
     * which mode to use
     * @var int
     */
    protected   $mode               = self::MODE_PDU;

	/**
	 * Sets the mode we want to use
	 *
	 * @param int
	 * @return Cmgf
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20140115 12:03
	 */
    public function setMode( $mode )
    {
        if( !in_array( $mode, array( self::MODE_PDU, self::MODE_TEXT ) ) ) {
            throw new \InvalidArgumentException("Given mode is invalid!");
        }
        $this->mode = $mode;
        return $this;
    }

	/**
	 * Returns the validation string
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20140115 12:03
	 */
    public function validate()
    {
        return "AT+CMGF=?\r";
    }

	/**
	 * Returns the AT-Code for CMGF  
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20140115 12:03
	 */
    public function __toString()
    {
        return sprintf("AT+CMGF=%d\r", $this->mode);
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
