<?php
/**
 * Message.php
 *
 * @package MBSmsd
 * @author Max Boesing <max@kriegt.es>
 * @since 20120924 16:09
 */
namespace MBSmsd;
use MBSmsd\Converter\Pdu AS PDUConverter;

/**
 * class Message
 *
 * @package MBSmsd
 * @author Max Boesing <max@kriegt.es>
 * @since 20120924 16:09
 */
class Message
{
    const       TP_COMPRESSED_TEXT          = 1;
    const       TP_UNCOMPRESSED_TEXT        = 2;
    const       TP_CLASS_FLASH              = 4;
    const       TP_CLASS_ME_SPECIFIC        = 8;
    const       TP_CLASS_SIM_SPECIFIC       = 16;
    const       TP_CLASS_TE_SPECIFIC        = 32;
    const       TP_ALPHABET_7BIT            = 64;
    const       TP_ALPHABET_8BIT            = 128;
    const       TP_ALPHABET_UCS2            = 256;
    const       TP_ALPHABET_RESERVED        = 512;
    const       TP_DISCARD_MESSAGE          = 1024;
    const       TP_STORE_MESSAGE_DEFAULT    = 2048;
    const       TP_STORE_MESSAGE_UCS2       = 4096;

    const       VALIDITY_NOT_PRESENT        = 1;
    const       VALIDITY_RELATIVE           = 2;
    const       VALIDITY_ENHANCED           = 4;
    const       VALIDITY_ABSOLUTE           = 8;

    const       TP_TOA_AUTOMATIC            = -1;
    const       TP_TOA_INTERNATIONAL        = 145;
    const       TP_TOA_NATIONAL             = 161;
    const       TP_TOA_UNKNOWN              = 129;

    const       NUMBER_FORMAT_NATIONAL      = 81;
    const       NUMBER_FORMAT_INTERNATIONAL = 91;
    const       NUMBER_FORMAT_UNKNOWN       = 81;
    const       NUMBER_FORMAT_RNATIONAL     = 'A1';

    /**
     * Defines maximum chars for 7Bit
     * @var int
     */
    const       MAX_CHAR_7BIT           = 160;
    const       MAX_CHAR_7BIT_CONCAT    = 153;

    /**
     * Defines maximum chars for 8Bit
     * @var int
     */
    const       MAX_CHAR_8BIT           = 140;
    const       MAX_CHAR_8BIT_CONCAT    = 134;

    /**
     * Defines maximum chars for 16Bit
     * @var int
     */
    const       MAX_CHAR_UCS2           = 70;
    const       MAX_CHAR_UCS2_CONCAT    = 67;

    protected   $receiver;
    protected   $sender;
    protected   $toa                    = self::TP_TOA_AUTOMATIC;
    protected   $bitsize                = PDUConverter::ENCODING_GSM_7BIT;
    protected   $class;
    protected   $receipt                = false;
    protected   $validity               = 255; // maximum of 63 weeks
    protected   $smsc;
    protected   $timestamp;
    protected   $text                   = "";
    protected   $smsLength              = 0;
    protected   $pdu                    = "";
    protected   $userDataHeader         = false;
    protected   $modem;
    protected   $messageId              = -1;

    protected   $message;

    protected   $messageParts           = array();

	/**
	 * Creates new Message
	 *
	 * @param int OPTIONAL Default: 7
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121013 01:30
	 */
    public function __construct( $bitsize = PDUConverter::ENCODING_GSM_7BIT )
    {
        $this->setBitsize($bitsize);
    }

	/**
	 * Sets the Modem
	 *
	 * @param Modem
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121018 15:49
	 */
    public function setModem( Modem $m )
    {
        $this->modem = $m;
        return $this;
    }

	/**
	 * Returns the Modem
	 *
	 * @return Modem
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121018 15:49
	 */
    public function getModem()
    {
        return $this->modem;
    }

	/**
	 * Sets the Bitsize
	 *
	 * @param int 
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121013 01:30
	 */
    public function setBitsize( $bitsize )
    {
        if( !empty($this->text) ) {
            throw new \RuntimeException('Cannot change bitsize after text is set!');
        }
        $this->bitsize = $bitsize;
        return $this;
    }

	/**
	 * Returns the Bitsize
	 *
	 * @return int
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121116 13:14
	 */
    public function getBitsize()
    {
        return $this->bitsize;
    }

	/**
	 * Sets the Message Id (Mailbox)
	 *
	 * @param int
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121018 16:29
	 */
    public function setMessageId( $id )
    {
        $this->messageId = $id;
        return $this;
    }

	/**
	 * Sets the Message Class
	 *
	 * @param int
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121029 16:25
	 */
    public function setClass( $class )
    {
        $this->class = $class;
        return $this;
    }

	/**
	 * Returns the Message Class
	 *
	 * @return int
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121116 13:14
	 */
    public function getClass()
    {
        return $this->class;
    }

	/**
     * Deletes this Message from SIM and all its
     * Parts (in case of concatenated
	 *
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121018 16:29
	 */
    public function delete()
    {
        if(! $this->modem instanceOf Modem || $this->getMessageId() <= -1 ) {
            throw new \RuntimeException('Missing Modem and/or Message ID');
        }
        
        $request = new Request\Cmgd( $this->modem );
        $request->setMessageId( $this->getMessageId() )->execute();
        if( $this->isConcatenated() ) {
            foreach( $this->messageParts AS $parts ) {
                $parts->delete();
            }
        }
    }

	/**
	 * Returns the Message Id
	 *
	 * @return int
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121018 16:29
	 */
    public function getMessageId()
    {
        return $this->messageId;
    }

	/**
	 * Sets the Receiver
	 *
	 * @param string
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121013 01:30
	 */
    public function setReceiver( $receiver )
    {
        if( $receiver[0] != '+' && is_numeric($receiver) && strlen($receiver) > 6 && $receiver[0] != '0' ) {
            $receiver = '+' . $receiver;
        }
        $this->receiver = $receiver;
        return $this;
    }

	/**
	 * Returns the Receiver
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121018 15:31
	 */
    public function getReceiver()
    {
        if( empty($this->receiver) ) {
            $this->receiver = $this->modem->getNumber();
        }
        return $this->receiver;
    }    

	/**
	 * Sets the Type of Address
	 *
	 * @param int
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121013 01:57
	 */
    public function setTOA( $toa )
    {
        $this->toa = $toa;
        return $this;
    }

	/**
	 * Returns the TOA
	 *
	 * @return int
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121116 13:14
	 */
    public function getTOA()
    {
        return $this->toa;
    }

	/**
	 * Sets the SMS Text
	 *
	 * @param string
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121013 01:57
	 */
    public function setText( $text )
    {
        $length = mb_strlen($text);
        $concatenate = false;

        switch( $this->bitsize ) {
            case PDUConverter::ENCODING_8BIT:
                $maxLength = self::MAX_CHAR_8BIT;
                $conLength = self::MAX_CHAR_8BIT_CONCAT;
            break;
            case PDUConverter::ENCODING_UCS2:
                $maxLength = self::MAX_CHAR_UCS2;
                $conLength = self::MAX_CHAR_UCS2_CONCAT;
            break;
            case PDUConverter::ENCODING_GSM_7BIT:
            default:
                $maxLength = self::MAX_CHAR_7BIT;
                $conLength = self::MAX_CHAR_7BIT_CONCAT;
        }

        if( $length > $maxLength ) {
            $concatenate = true;
            $maxLength = $conLength;
        }

        $this->text = $text;
        $this->smsLength = $length;
        return $this;
    }

	/**
	 * Returns the SMS Text
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121015 11:03
	 */
    public function getText()
    {
        return $this->text;
    }

	/**
	 * Sets the SMS Receipt Request
	 *
	 * @param boolean
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121013 01:57
	 */
    public function setReceiptRequest( $receipt )
    {
        $this->receipt = (bool)$receipt;
        return $this;
    }

	/**
	 * Returns the Receipt Request status
	 *
	 * @return boolean
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121018 15:31
	 */
    public function getReceiptRequest()
    {
        return $this->receipt;
    }

	/**
	 * Sets the SMS Center number
	 *
	 * @param string
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121013 01:57
	 */
    public function setSMSC( $smsc )
    {
        if( $smsc[0] != '+' && is_numeric($smsc) && strlen($smsc) > 6 && $smsc[0] != 0 ) {
            $smsc = '+' . $smsc;
        }
        $this->smsc = $smsc;
        return $this;
    }

	/**
	 * Returns the SMS Center
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121018 15:31
	 */
    public function getSMSC()
    {
        return $this->smsc;
    }

	/**
	 * Sets the validity
	 *
	 * @param int|null
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121116 13:14
	 */
    public function setValidity( $value )
    {
        if( $value === null ) {
            $this->validity = null;
            return $this;
        }

        if( $value > 255 || $value < 0 ) {
            throw new \InvalidArgumentException('Validity of Message cannot be greater than 255 (63 weeks) or less than 0 (5 minutes)! If you dont want to specify a validity, please just define the value as NULL');
        }

        $this->validity = $value;
        return $this;
    }

	/**
	 * Returns the validity
	 *
	 * @return int|null
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121116 13:14
	 */
    public function getValidity()
    {
        return $this->validity;
    }

	/**
	 * Sets the Timestamp of the Message
	 *
	 * @param string
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121013 02:42
	 */
    public function setTimestamp( $timestamp )
    {
        $this->timestamp = $timestamp;
        return $this;
    }

	/**
	 * Returns the Timestamp
	 *
     * @param string OPTIONAL
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121018 15:31
	 */
    public function getTimestamp( $format=null )
    {
        if( $format !== null ) {
            return (new \DateTime($this->timestamp))->format( $format );
        }
        return $this->timestamp;
    }

	/**
	 * Sets the Sender Number or Name
	 *
	 * @param string
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121015 11:27
	 */
    public function setSender( $sender )
    {
        if( $sender[0] != '+' && is_numeric($sender) && strlen($sender) > 6 ) {
            $sender = '+' . $sender;
        }
        $this->sender = $sender;
        return $this;
    }

	/**
	 * Returns the Sender Number
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121018 15:31
	 */
    public function getSender()
    {
        return $this->sender;
    }

	/**
	 * Sets the UserDataHeader
	 *
	 * @param string
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121015 11:49
	 */
    public function setUserDataHeader( $header )
    {
        $this->userDataHeader = $header;
        return $this;
    }

	/**
	 * Returns the UserDataHeader
	 *
	 * @return string|false
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121015 11:49
	 */
    public function getUserDataHeader()
    {
        return $this->userDataHeader;
    }

	/**
	 * Sets the PDU of this Message
	 *
	 * @param string
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121015 11:27
	 */
    public function setPDU( $pdu )
    {
        $this->pdu = $pdu;
        return $this;
    }

	/**
     * Adds the Text of given Message
     * Used for concatenated messages
	 *
	 * @param Message
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121015 11:49
	 */
    public function addText( Message $m )
    {
        $this->text .= $m->getText();
        $this->messageParts[ $m->getUserDataHeader() ] = $m;
        return $this;
    }

	/**
	 * Checks if this Message is concatenated
	 *
	 * @return boolean
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121015 13:49
	 */
    public function isConcatenated()
    {
        return is_string($this->userDataHeader);
    }

	/**
	 * Checks if this Message is valid
	 *
	 * @return boolean
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121015 13:49
	 */
    public function isValid()
    {
        if( !$this->isConcatenated() ) {
            return true;
        }

        $userDataHeader = $this->getUserDataHeader();
        $length = hexdec( substr($userDataHeader,0,2) );

        switch( $length ) {
            default:
            case 5:
                $totalParts = hexdec(substr($userDataHeader,4*2,2));
            break;
            case 6:
                $totalParts = hexdec(substr($userDataHeader,2*2,2));
            break;
        }

        return ( count($this->messageParts) == $totalParts - 1 );
    }

	/**
	 * Returns the SMS Text
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121015 11:03
	 */
    public function __toString()
    {
        return $this->getText();
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
