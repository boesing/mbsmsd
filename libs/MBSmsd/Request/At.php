<?php
/**
 * At.php
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20120918 15:25
 */
namespace MBSmsd\Request;
use MBSmsd\Request\AbstractRequest AS Request;

/**
 * class At
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20120918 15:25
 */
class At extends Request implements ValidationInterface
{
	/**
	 * Returns the Validation String
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121009 15:39
	 */
    public function validate()
    {
        return "AT\r";
    }

	/**
	 * Returns the initialize string
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121011 12:11
	 */
    public function initialize()
    {
        return "AT S7=45 S0=0 L1 V1 X4 &c1 E1 Q0\r";
    }

	/**
	 * Returns the AT Request as String
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120918 15:25
	 */
    public function __toString()
    {
        return $this->initialize();
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
