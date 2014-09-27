<?php
/**
 * Cmgs.php
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20121116 13:09
 */
namespace MBSmsd\Request;
use MBSmsd\Request\AbstractRequest AS Request;
use MBSmsd\Message;
use MBSmsd\Converter\Pdu AS PDUConverter;
use MBSmsd\Response;

/**
 * class Cmgs
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20121116 13:09
 */
class Cmgs extends Request implements ValidationInterface, DelayedResponseInterface 
{
    protected   $message;

	/**
	 * Sets the Message we want to send
	 *
	 * @param Message
	 * @return Cmgs
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121116 13:09
	 */
    public function setMessage( Message $m )
    {
        $this->message = $m;
        return $this;
    }

	/**
	 * Returns the validation string
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121116 13:09
	 */
    public function validate()
    {
        return "AT+CMGS=?\r";
    }

	/**
	 * Returns true because CMGS has a response 
	 *
	 * @return true
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121122 10:32
	 */
    public function hasResponse()
    {
        return true;
    }

	/**
	 * Klassendefinition
	 *
	 * @param 
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121116 13:14
	 */
    protected function generatePDU( Message $message )
    {
        $converter = new PDUConverter;

        $encoding = $message->getBitsize();
        $text = (string)$message;
        $receiver = $message->getReceiver();
        $smsCNumber = $message->getSMSC();
        $messageClass = $message->getClass();
        $toa = $message->getTOA();
        $validity = $message->getValidity();
        $receipt = $message->getReceiptRequest();

        $octetFirst = "";
        $octetSecond = "";
        $encoded = "";

        list( $smsCInfoLength, $smsCLength, $smsCNumberFormat, $smsC ) = array( 0, 0, "", "" );

        if( $smsCNumber !== null || strlen($smsCNumber) > 0 ) {
            $smsCNumberFormat = Message::NUMBER_FORMAT_NATIONAL;

            if( substr($smsCNumber,0,1) == '+' ) {
                $smsCNumberFormat = Message::NUMBER_FORMAT_INTERNATIONAL;
                $smsCNumber = substr($smsCNumber,1);
            } else if( substr($smsCNumber,0,1) != '0' ) {
                $smsCNumberFormat = Message::NUMBER_FORMAT_INTERNATIONAL;
            }

            if( strlen($smsCNumber) % 2 != 0 ) {
                $smsCNumber .= 'F';
            }

            $smsC = $converter->semiOctetToString( $smsCNumber );
            $smsCInfoLength = (strlen($smsCNumberFormat . $smsC)/2);
            $smsCLength = $smsCInfoLength;
        }

        if( $smsCInfoLength < 10 ) {
            $smsCInfoLength = '0' . $smsCInfoLength;
        }

        if( $receipt ) {
            $firstOctet = "2100";
            if( $validity !== null ) {
                $firstOctet = "3100";
            }
        } else {
            $firstOctet = "0100";
            if( $validity !== null ) {
                $firstOctet = "1100";
            }
        }

        $receiverNumberFormat = Message::NUMBER_FORMAT_UNKNOWN;
        if( substr($receiver,0,1) == '+' ) {
            $receiverNumberFormat = Message::NUMBER_FORMAT_INTERNATIONAL;
            $receiver = substr($receiver,1);
        } elseif( substr($receiver,0,1) != '0' ) {
            $receiverNumberFormat = Message::NUMBER_FORMAT_INTERNATIONAL;
        } 

        switch( $toa ) {
            case 145:
                $receiverNumberFormat = Message::NUMBER_FORMAT_INTERNATIONAL;
            break;
            case 161:
                $receiverNumberFormat = Message::NUMBER_FORMAT_RNATIONAL;
            break;
            case 129:
                $receiverNumberFormat = Message::NUMBER_FORMAT_UNKNOWN;
            break;

        }

        $receiverLength = strlen($receiver);
        if( $receiverLength % 2 != 0 ) {
            $receiver .= 'F';
        }
        $receiverLength = sprintf('%02X', $receiverLength);

        $receiverNumber = $converter->semiOctetToString($receiver);
        $proto  = '00';
        $dcs = 0;
        if( $messageClass !== null ) {
            $dcs = $messageClass | 0x10;
        } 

        switch( $encoding ) {
            case PDUConverter::ENCODING_GSM_7BIT:
            break;
            case PDUConverter::ENCODING_8BIT:
                $dcs |= 4;
            break;
            case PDUConverter::ENCODING_UCS2:
                $dcs |= 8;
            break;
        }

        $dataEncoding = sprintf('%02X', $dcs);
        $validPeriod = "";
        if( $validity !== null ) {
            $validPeriod = sprintf('%02X', $validity);
        }

        if( $encoding == PDUConverter::ENCODING_GSM_7BIT ) {
            $text = mb_substr($text, 0, Message::MAX_CHAR_7BIT);
            $userDataSize = sprintf('%02X', mb_strlen($text));
            $encoded = $converter->encodeGSM7Bit( $text );
        } else if( $encoding == PDUConverter::ENCODING_8BIT ) {
            $text = mb_substr($text, 0, Message::MAX_CHAR_8BIT);
            $userDataSize = sprintf('%02X', mb_strlen($text));
            $encoded = $converter->encodeGSM8Bit( $text );
        } else if( $encoding == PDUConverter::ENCODING_UCS2 ) {
            $text = mb_substr($text, 0, Message::MAX_CHAR_UCS2);
            $userDataSize = sprintf('%02X', mb_strlen($text) * 2);
            $encoded = $converter->encodeUCS2( $text );
        }



        $header = $smsCInfoLength . $smsCNumberFormat . $smsC . $firstOctet . $receiverLength . $receiverNumberFormat . $receiverNumber . $proto . $dataEncoding . $validPeriod . $userDataSize;

        $pdu = $header . $encoded;

        return array( (strlen($pdu)/2 - $smsCLength - 1), $pdu );
    }

	/**
	 * Klassendefinition
	 *
	 * @param 
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121116 13:09
	 */
    public function __toString()
    {
        trigger_error( __METHOD__ . " is not supported.", E_USER_ERROR );
        return "";
    }

	/**
	 * Klassendefinition
	 *
	 * @param 
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121122 10:26
	 */
    public function execute()
    {
        if(! $this->message instanceOf Message ) {
            throw new \RuntimeException('Missing Message we want to sent');
        }

        $this->_validate();

        list( $length, $pdu ) = $this->generatePDU( $this->message );

        $header = $this->modem->send( sprintf("AT+CMGS=%d\r", $length) );
        usleep(50000);
        $response = parent::_execute( $pdu . chr(0x1A) );
        if( ($header && $response instanceOf Response\Cmgs) === false ) {
            $extraInfo = "";
            if( $response instanceOf Response\Cms ) {
                $extraInfo = chr(32) . $response->__toString();
            }
            throw new \RuntimeException("Could not sent Message!". $extraInfo);
        }

        return $response;
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
