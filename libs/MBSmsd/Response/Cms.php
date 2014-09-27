<?php
/**
 * Cms.php
 *
 * @package MBSmsd
 * @subpackage Response
 * @author Max Boesing <max@kriegt.es>
 * @since 20121015 16:37
 */
namespace MBSmsd\Response;

/**
 * class Cms
 *
 * @package MBSmsd
 * @subpackage Response
 * @author Max Boesing <max@kriegt.es>
 * @since 20121015 16:37
 */
class Cms implements ResponseInterface
{
    protected   $raw;
    protected   $code;
    protected   $processed          = false;

    protected   $messages           = array(
        300 => 'Mobile equipment (ME) failure. Mobile equipment refers to the mobile device that communicates with the wireless network. Usually it is a mobile phone or GSM/GPRS modem. The SIM card is defined as a separate entity and is not part of mobile equipment.',
        301 => 'SMS service of mobile equipment (ME) is reserved. See +CMS error code 300 for the meaning of mobile equipment.',
        302 => 'The operation to be done by the AT command is not allowed.',
        303 => 'The operation to be done by the AT command is not supported.',
        304 => 'One or more parameter values assigned to the AT command are invalid. [PDU mode]',
        305 => 'One or more parameter values assigned to the AT command are invalid. [Text mode]',
        310 => 'There is no SIM card.',
        311 => 'The SIM card requires a PIN to operate. The AT command +CPIN (command name in text: Enter PIN) can be used to send the PIN to the SIM card.',
        312 => 'The SIM card requires a PH-SIM PIN to operate. The AT command +CPIN (command name in text: Enter PIN) can be used to send the PH-SIM PIN to the SIM card.',
        313 => 'SIM card failure.',
        314 => 'The SIM card is busy.',
        315 => 'The SIM card is wrong.',
        316 => 'The SIM card requires a PUK to operate. The AT command +CPIN (command name in text: Enter PIN) can be used to send the PUK to the SIM card.',
        320 => 'Memory/message storage failure.',
        321 => 'The memory/message storage index assigned to the AT command is invalid.',
        322 => 'The memory/message storage is out of space.',
        330 => 'The SMS center (SMSC) address is unknown.',
        331 => 'No network service is available.',
        332 => 'Network timeout occurred.',
        340 => 'There is no need to send message acknowledgement by the AT command +CNMA (command name in text: New Message Acknowledgement to ME/TA).',
        500 => 'An unknown error occurred.',
    );

	/**
	 * Sets the RAW Response
	 *
	 * @param string
	 * @return Cms
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121015 16:37
	 */
    public function setRawResponse( $response )
    {
        $this->raw = $response;
        return $this;
    }

	/**
	 * Processes the RAW Data
	 *
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121015 16:37
	 */
    protected function process()
    {
        if( $this->processed ) {
            return;
        }

        if( empty($this->raw) ) {
            throw new \RuntimeException("Missing RAW Response for +CMS");
        }

        $this->code = substr($this->raw, strrpos($this->raw,chr(32))+1);
        $this->processed = true;
    }

	/**
	 * Returns the message for this error
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121015 16:37
	 */
    public function __toString()
    {
        $this->process();
        if( !isset($this->messages[$this->code]) ) {
            trigger_error("Cannot find Message for current code \"{$this->code}\"", E_USER_ERROR);
            return $this->messages[500];
        }
        return $this->messages[$this->code];
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
