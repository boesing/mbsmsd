<?php
/**
 * Cpin.php
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20121015 16:32
 */
namespace MBSmsd\Request;
use MBSmsd\Request\AbstractRequest AS Request;

/**
 * class Cpin
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20121015 16:32
 */
class Cpin extends Request implements ValidationInterface
{
    const       ST_READY                = 'READY';
    const       ST_PIN                  = 'SIM PIN';
    const       ST_PUK                  = 'SIM PUK';
    const       ST_PIN2                 = 'SIM PIN2';
    const       ST_PUK2                 = 'SIM PUK2';
    const       ST_BLOCKED              = 'BLOCKED';

	/**
	 * Klassendefinition
	 *
	 * @param 
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121015 16:32
	 */
    public function validate()
    {
        return "AT+CPIN?\r";
    }

	/**
	 * Klassendefinition
	 *
	 * @param 
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121015 16:32
	 */
    public function __toString()
    {
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
