<?php
/**
 * Most code in here is copied from the smstools3 PDU converter by Keijo Kasvi
 * http://smstools3.kekekasvi.com/topic.php?id=288
 *
 * I've copied some functions from his javascript and converted it into PHP
 * Those functions who are not identical, I created by inspiration of his 
 * javascript code.
 *
 * Aswell - there are some functions from php.net comments or stackoverflow
 * So all I want to say is - THANK YOU
 * 
 * If some1 finds his code in here and wants to be mentioned, just write me an 
 * email
 */

/**
 * Cmgl.php
 *
 * @package MBSmsd
 * @subpackage Response
 * @author Max Boesing <max@kriegt.es>
 * @since 20121012 10:35
 */
namespace MBSmsd\Response;
use MBSmsd\Converter\Pdu AS PDUConverter;
use MBSmsd\Message;
use MBSmsd\Message\Receipt;
use Iterator;

/**
 * class Cmgl
 *
 * @package MBSmsd
 * @subpackage Response
 * @author Max Boesing <max@kriegt.es>
 * @since 20121012 10:35
 */
class Cmgl implements ResponseInterface, Iterator
{
    protected   $messageId              = -1;
    protected   $raw;
    protected   $processed              = false;

    protected   $mailbox                = array();

	/**
	 * Process the RAW Data
	 *
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121012 15:49
	 */
    protected function process()
    {
        if( $this->processed ) {
            return;
        }

        if( empty($this->raw) ) {
            throw new \RuntimeException("Missing RAW Response!");
        }

        $lines = explode("\n", trim($this->raw));

        for( $i=0; $i < count($lines)-1; $i+=2 ) {
            if( trim($lines[$i]) == '' ) {
                continue;
            }
            $pos1 = strpos($lines[$i], chr(32))+1;
            $pos2 = strpos($lines[$i], ',') - $pos1;
            $messageId = substr($lines[$i], $pos1, $pos2);

            $this->mailbox[ $messageId ] = $this->convertPDU( trim($lines[$i+1]), $messageId ); 
        }

        if( count($this->mailbox) > 0 ) {
            $this->messageId = 0;
        }

        $this->processed = true;
    }

	/**
	 * Converts the whole Message PDU to readable Message
	 *
	 * @param string
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121012 15:49
	 */
    protected function convertPDU( $message, $id )
    {
        if( empty($message) ) {
            throw new \InvalidArgumentException('Empty message given!');
        }

        $message = str_replace( array( "\t", "\n", "\r", " ", ), '', $message );
        $smsCLength = hexdec( substr($message, 0, 2) );
        $smsCInfo   = substr($message, 2, $smsCLength * 2);
        $smsCTOA    = substr($smsCInfo,0,2);
        $smsCNumber = substr($smsCInfo,2,$smsCLength*2);

        if( $smsCLength ) {
            $smsCNumber = $this->semiOctetToString($smsCNumber);
            $smsCNumber = rtrim($smsCNumber,'F');
        }

        $startSMSDelivery = $smsCLength * 2 + 2;
        $firstOctet = substr( $message, $startSMSDelivery, 2);
        $startSMSDelivery += 2;

        if( (hexdec($firstOctet) & 0x03) == 1 || (hexdec($firstOctet) & 0x03) == 3 ) {
            throw new \RuntimeException("Not implemented yet");
        } else if( (hexdec($firstOctet) & 0x03) == 0 ) {
            $message = $this->decodeReceivedMessagePDU( $firstOctet, $message, $startSMSDelivery );
            $message->setSMSC( $smsCNumber );
            $message->setMessageId( $id );
        } else {
            $message = $this->decodeReceivedReceiptPDU( $message, $startSMSDelivery );
        }

        return $message;
    }

	/**
	 * Decodes received Message from Mailbox
	 *
     * @param string
     * @param string
     * @param int
     * @param int
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121029 16:25
	 */
    protected function decodeReceivedMessagePDU( $firstOctet, $message, $startSMSDelivery )
    {
        $converter = new PDUConverter; 
        $userDataHeader = ((hexdec($firstOctet) & 0x40) == 0x40);

        $senderLength = hexdec(substr($message, $startSMSDelivery,2));
        $startSMSDelivery += 2;

        $senderTOA = substr($message, $startSMSDelivery,2);
        $startSMSDelivery += 2;

        $receipt = ((dechex($firstOctet) & 0x20)==0x20)?true:false;

        if( $senderLength%2 > 0 ) {
            $senderLength++;
        }

        if( $senderTOA == 'D0' ) {
            $senderNumber = $converter->decodeGSM7Bit( substr( $message, $startSMSDelivery, $senderLength ) );
        } else {
            $senderNumber = $this->semiOctetToString( substr( $message, $startSMSDelivery, $senderLength ) );
            if( substr($senderNumber,-1) == 'F' ) {
                $senderNumber = substr($senderNumber, 0, -1);
            }
        }

        $startSMSDelivery += $senderLength;
        list( $tpPID, $tpDCS ) = preg_split('/(.{2})/s', substr( $message, $startSMSDelivery ), 2, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $startSMSDelivery += 2 * 2;

        $tpDCSOptions   = $this->getDCSOptions( $tpDCS );

        $timeStamp = $this->semiOctetToString( substr( $message, $startSMSDelivery, 14) );

        list( $year, $month, $day, $hours, $minutes, $seconds, $timezone ) = preg_split('/(.{2})/s', $timeStamp, 7, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        $timezone = $this->decodeTimezone( $timezone );

        // Creating ISO-8601 timestamp for better compatibilities
        $timeStamp = sprintf('%02d%02d-%02d-%02d %02d:%02d:%02d%s', (int)strftime('%C'), $year, $month, $day, $hours, $minutes, $seconds, $timezone);
        $startSMSDelivery += 14;
        $messageLength = hexdec( substr($message, $startSMSDelivery, 2) );
        $startSMSDelivery+=2;

        $bitSize = $this->getDCSBitsize( $tpDCS );

        $skipChars = 0;
        if( ($bitSize == 7 || $bitSize == 16) && $userDataHeader ) {
            $udLength = hexdec( substr( $message, $startSMSDelivery, 2 ) );
            $userDataHeader = "";
            for( $i=0; $i <= $udLength; $i++ ) {
                $userDataHeader .= substr( $message, $startSMSDelivery + $i * 2, 2);
            }

            $skipChars = ($udLength + 1) / 2;

            if( $bitSize == 7 ) {
                $skipChars = ( ( ($udLength + 1) * 8 ) + 6 ) / 7;
            }
        }

        switch( $bitSize ) {
            default:
            case 7:
                $decoded = $converter->decodeGSM7Bit( substr( $message, $startSMSDelivery ), $skipChars );
            break;
            case 8:
                $decoded = $converter->decode8Bit( substr( $message, $startSMSDelivery ) );
            break;
            case 16:
                $decoded = $converter->decodeUCS2( substr( $message, $startSMSDelivery ), $skipChars );
            break;
        }

        $decoded = mb_substr($decoded, 0, $messageLength);

        if( $bitSize == 16 ) {
            $messageLength /= 2;
        }

        $rawMessage = $message;
        $message = new Message( $bitSize );
        $message->setText( $decoded );
        $message->setTOA( $senderTOA );
        if( $tpDCSOptions & Message::TP_CLASS_FLASH ) {
            $message->setClass( Message::TP_CLASS_FLASH );
        } else if( $tpDCSOptions & Message::TP_CLASS_ME_SPECIFIC ) {
            $message->setClass( Message::TP_CLASS_ME_SPECIFIC );
        } else if( $tpDCSOptions & Message::TP_CLASS_SIM_SPECIFIC ) {
            $message->setClass( Message::TP_CLASS_SIM_SPECIFIC );
        } else if( $tpDCSOptions & Message::TP_CLASS_TE_SPECIFIC ) {
            $message->setClass( Message::TP_CLASS_TE_SPECIFIC );
        }
        $message->setReceiptRequest( $receipt );

        if( isset($validity) ) {
            $message->setValidity( $validity );
        }

        if( isset($timeStamp) ) {
            $message->setTimestamp( $timeStamp );
        }

        if( isset($senderNumber) ) {
            $message->setSender( $senderNumber );
        }

        $message->setPDU( $rawMessage );
        $message->setUserDataHeader( $userDataHeader );

        return $message;
    }

	/**
	 * Klassendefinition
	 *
	 * @param 
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121029 16:25
	 */
    protected function decodeReceivedReceiptPDU( $message, $startSMSDelivery )
    {
        $messageReference = hexdec( substr($message,$startSMSDelivery,2) );
        $startSMSDelivery += 2;

        $senderLength = hexdec( substr($message, $startSMSDelivery, 2) );
        if( $senderLength % 2 != 0 ) {
            $senderLength++;
        }

        $startSMSDelivery += 2;

        $senderTOA = substr($message, $startSMSDelivery, 2);

        $startSMSDelivery += 2;

        $senderNumber = $this->semiOctetToString( substr( $message, $startSMSDelivery, $senderLength) );
        if( substr($senderNumber,-1) == 'F' ) {
            $senderNumber = substr($senderNumber,0,-1);
        }

        $startSMSDelivery += $senderLength;

        $timeStamp = $this->semiOctetToString( substr( $message, $startSMSDelivery, 14) );
        list( $year, $month, $day, $hours, $minutes, $seconds, $timezone ) = preg_split('/(.{2})/s', $timeStamp, 7, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        $timezone = $this->decodeTimezone( $timezone );

        // Creating ISO-8601 timestamp for better compatibilities
        $timeStampSent = sprintf('%02d%02d-%02d-%02d %02d:%02d:%02d%s', (int)strftime('%C'), $year, $month, $day, $hours, $minutes, $seconds, $timezone);
        $startSMSDelivery += 14;

        $timeStamp = $this->semiOctetToString( substr( $message, $startSMSDelivery, 14) );
        list( $year, $month, $day, $hours, $minutes, $seconds, $timezone ) = preg_split('/(.{2})/s', $timeStamp, 7, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        $timezone = $this->decodeTimezone( $timezone );
        // Creating ISO-8601 timestamp for better compatibilities
        $timeStampDelivered = sprintf('%02d%02d-%02d-%02d %02d:%02d:%02d%s', (int)strftime('%C'), $year, $month, $day, $hours, $minutes, $seconds, $timezone);
        $startSMSDelivery += 14;

        $statusCode = substr( $message, $startSMSDelivery, 2);

        $rawMessage = $message;
        $message = new Receipt;

        $message->setTOA( $senderTOA );

        if( isset($timeStampDelivered) ) {
            $message->setDeliveredTimestamp( $timeStampDelivered );
        }
        
        if( isset($timeStampSent) ) {
            $message->setSentTimestamp( $timeStampSent );
        }

        if( isset($senderNumber) ) {
            $message->setReceiver( $senderNumber );
        }

        $message->setPDU( $rawMessage );
        $message->setStatusCode( $statusCode );
        
        return $message;
    } 

	/**
	 * Decodes given DCS
	 *
	 * @param string 
	 * @return int
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121012 22:29
	 */
    protected function getDCSOptions( $tpDCS )
    {
        $tpDCSOptions = 0;
        $pomDCS = hexdec( $tpDCS );

        switch( $pomDCS & 192 ) {
            case 0: {
                $tpDCSOptions |= ($pomDCS & 32)?Message::TP_COMPRESSED_TEXT:Message::TP_UNCOMPRESSED_TEXT;

                if( $pomDCS & 16 ) {
                    switch( $pomDCS & 3 ) {
                        case 0:
                            $tpDCSOptions |= Message::TP_CLASS_FLASH;
                        break;
                        case 1:
                            $tpDCSOptions |= Message::TP_CLASS_ME_SPECIFIC;
                        break;
                        case 2:
                            $tpDCSOptions |= Message::TP_CLASS_SIM_SPECIFIC;
                        break;
                        case 3:
                            $tpDCSOptions |= Message::TP_CLASS_TE_SPECIFIC;
                        break;
                    }
                }

                switch( $pomDCS & 12 ) {
                    case 0:
                        $tpDCSOptions |= Message::TP_ALPHABET_7BIT;
                    break;
                    case 4:
                        $tpDCSOptions |= Message::TP_ALPHABET_8BIT;
                    break;
                    case 8:
                        $tpDCSOptions |= Message::TP_ALPHABET_UCS2;
                    break;
                    case 12:
                        $tpDCSOptions |= Message::TP_ALPHABET_RESERVED;
                    break;
                }
            }
            break;
            case 192: {
                switch( $pomDCS & 0x30 ) {
                    case 0:
                        $tpDCSOptions |= Message::TP_DISCARD_MESSAGE;
                    break;
                    case 0x10:
                        $tpDCSOptions |= Message::TP_STORE_MESSAGE_DEFAULT;
                    break;
                    case 0x20:
                        $tpDCSOptions |= Message::TP_STORE_MESSAGE_UCS2;
                    break;
                    case 0x30: {
                        if( !($pomDCS & 0x4) ) {
                            $tpDCSOptions |= Message::TP_ALPHABET_7BIT;
                            break;
                        }
                        $tpDCSOptions |= Message::TP_ALPHABET_8BIT;
                    }
                    break;
                }

                switch( $pomDCS & 3 ) {
                    case 0:
                        $tpDCSOptions |= Message::TP_CLASS_FLASH;
                    break;
                    case 1:
                        $tpDCSOptions |= Message::TP_CLASS_ME_SPECIFIC;
                    break;
                    case 2:
                        $tpDCSOptions |= Message::TP_CLASS_SIM_SPECIFIC;
                    break;
                    case 3:
                        $tpDCSOptions |= Message::TP_CLASS_TE_SPECIFIC;
                    break;
                }
            }
        }

        return $tpDCSOptions;
    }

	/**
	 * Returns the DCS Bitsize
	 *
	 * @param string
	 * @return int
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121012 22:29
	 */
    protected function getDCSBitsize( $tpDCS )
    {
        $pomDCS = hexdec( $tpDCS );
        switch( ($pomDCS & 0x0C) >> 2 ) {
            default:
            case 0:
                $bitSize = 7;
            break;
            case 1:
                $bitSize = 8;
            break;
            case 2:
                $bitSize = 16;
            break;
        }

        return $bitSize;
    }

	/**
	 * Decodes given timezone
	 *
	 * @param string
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121013 02:47
	 */
    protected function decodeTimezone( $timezone )
    {
        $tz = dechex( substr($timezone,0,1) );
        $result = '+';
        if( $tz & 8 ) {
           $result = '-';
        }

        $tz = ($tz & 7) * 10;
        $tz .= abs( substr($timezone,1,1) );
        $tzHours = floor( $tz / 4 );
        $tzMinutes = 15 * ($tz % 4);

        if( $tzHours < 10 ) {
            $result .= '0';
        }

        $result .= $tzHours . ':';

        if( $tzMinutes == 0 ) {
            $result .= '0';
        }
        $result .= $tzMinutes;
        return $result;
    }

	/**
	 * SemiOctetToString
	 *
	 * @param string
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121116 15:08
	 */
    protected function semiOctetToString( $octet )
    {
        $converter = new PDUConverter;
        $string = $converter->semiOctetToString($octet);
        unset($converter);
        return $string;
    }

	/**
	 * Iterator: current
	 *
	 * @return Message
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121012 15:49
	 */
    public function current()
    {
        return $this->mailbox[ $this->messageId ];
    }

	/**
	 * Iterator: next
	 *
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121012 15:49
	 */
    public function next()
    {
        $this->messageId++;
    }

	/**
	 * Iterator: key
	 *
	 * @return int
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121012 15:49
	 */
    public function key() 
    {
        return $this->messageId;
    }

	/**
	 * Iterator: valid
	 *
	 * @return boolean
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121012 15:49
	 */
    public function valid()
    {
        $this->process();
        return ($this->messageId < count($this->mailbox));
    }

	/**
	 * Iterator: rewind
	 *
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121012 15:49
	 */
    public function rewind()
    {
        $this->messageId = 0;
    }

	/**
	 * Sets the RAW Response
	 *
	 * @param string
	 * @return Cmgl
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121012 10:50
	 */
    public function setRAWResponse( $response )
    {
        $this->raw = $response;
        return $this;
    }

	/**
	 * Returns array of Message
	 *
	 * @return Message[]
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121015 11:49
	 */
    public function toArray()
    {
        $this->process();
        return $this->mailbox;
    }

	/**
	 * ResponseInterface: __toString
     * Returns empty string
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121012 10:37
	 */
    public function __toString()
    {
        return "";
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
