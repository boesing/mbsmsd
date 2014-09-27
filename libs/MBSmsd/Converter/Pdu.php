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
 * Pdu.php
 *
 * @package MBSmsd
 * @subpackage Converter
 * @author Max Boesing <max@kriegt.es>
 * @since 20120919 11:10
 */
namespace MBSmsd\Converter;

/**
 * class Pdu
 *
 * @package MBSmsd
 * @subpackage Converter
 * @author Max Boesing <max@kriegt.es>
 * @since 20120919 11:10
 */
class Pdu
{
    const       ENCODING_GSM_7BIT       = 7;
    const       ENCODING_8BIT           = 8;
    const       ENCODING_UCS2           = 16;

    protected   static $sevenbitdefault = array(
        '@',    '£',    '$',    '¥',    'è',    'é',    'ù',    'ì',    'ò',    'Ç',    "\n",   'Ø',    'ø',    "\r",   'Å',    'å',
        "\u0394",   '_',    "\u03a6",   "\u0393",   "\u039b",   "\u03a9",   "\u03a0",   "\u03a8",
        "\u03a3",   "\u0398",   "\u039e",   "\u261d",    'Æ',    'æ',    'ß',    'É',
        ' ',    '!',    '"',    '#',    '¤',    '%',    '&',    '\'',   '(',    ')',    '*',    '+',    ',',    '-',    '.',    '/',
        '0',    '1',    '2',    '3',    '4',    '5',    '6',    '7',    '8',    '9',    ':',    ';',    '<',    '=',    '>',    '?',
        '¡',    'A',    'B',    'C',    'D',    'E',    'F',    'G',    'H',    'I',    'J',    'K',    'L',    'M',    'N',    'O',
        'P',    'Q',    'R',    'S',    'T',    'U',    'V',    'W',    'X',    'Y',    'Z',    'Ä',    'Ö',    'Ñ',    'Ü',    '§',
        '¿',    'a',    'b',    'c',    'd',    'e',    'f',    'g',    'h',    'i',    'j',    'k',    'l',    'm',    'n',    'o',
        'p',    'q',    'r',    's',    't',    'u',    'v',    'w',    'x',    'y',    'z',    'ä',    'ö',    'ñ',    'ü',    'à'
    );

    protected   static $sevenbitextended = array(
        '\f'    =>   0x0A,   // '\u000a',    // <FF>
        '^'     =>   0x14,   // '\u0014',    // CIRCUMFLEX ACCENT
        '{'     =>   0x28,   // '\u0028',    // LEFT CURLY BRACKET
        '}'     =>   0x29,   // '\u0029',    // RIGHT CURLY BRACKET
        '\\'    =>   0x2F,   // '\u002f',    // REVERSE SOLIDUS
        '['     =>   0x3C,   // '\u003c',    // LEFT SQUARE BRACKET
        '~'     =>   0x3D,   // '\u003d',    // TILDE
        ']'     =>   0x3E,   // '\u003e',    // RIGHT SQUARE BRACKET
        '|'     =>   0x40,   // '\u0040',    // VERTICAL LINE \u7c
        '€'     =>   0x65,   // '\u0065' // EURO SIGN &#8364;
    );

	/**
	 * Decodes 7-Bit PDU Message to readable String
	 *
     * @param string
     * @param int OPTIONAL Default: 0
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120919 11:10
	 */
    public function decodeGSM7Bit( $message, $skip=0 )
    {
        $length = strlen($message);
        if( ($length-$skip) % 2 ) {
            throw new \InvalidArgumentException("Given text is not well formatted. \"".substr($message,$skip)."\"");
        }

        $septets = floor( $length / 2 * 8 / 7 );
        $buffer = $this->decodeMessage7Bit($skip, $message, $septets);
        $length = strlen($buffer);
        $padding = chr(0x0D);
        if( ($septets % 8 == 0 && $length > 0 && $buffer[$length - 1] == $padding) || ($septets % 8 == 1 && $length > 1 && $buffer[$length - 1] == $padding && $buffer[$length - 2] == $padding) ) {
            $buffer = substr($buffer, 0, $length - 1);
        }

        return $buffer;
    }

	/**
     * Decodes UCS2 (16-bit) written response string
     * into readable string
	 *
	 * @param string
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120919 11:10
	 */
    public function decodeUCS2( $encoded )
    {
        $length = strlen($encoded);
        if( $length % 2 ) {
            throw new \InvalidArgumentException("Given text is not well formatted.");
        }

        return $this->decodeMessage16Bit( $encoded );
    }

	/**
	 * Decodes given 8-bit encoded string
	 *
	 * @param string
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121013 00:32
	 */
    public function decode8Bit( $encoded )
    {
        $length = strlen($encoded);
        if( $length % 2 ) {
            throw new \InvalidArgumentException("Given text is not well formatted.");
        }
        return $this->decodeMessage8Bit( $encoded, $length );
    }

	/**
     * Returns the User Message in a readable format
	 *
     * @param int
     * @param string
     * @param int 
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120919 11:10
	 */
    protected function decodeMessage7Bit( $skip, $encoded, $length )
    {
        $message = "";

        $octets = array();
        $rest = array();
        $septets = array();

        $s = 1;
        $count = 0;
        $matchcount = 0;
        $escaped = false;
        $chars = 0;

        $byteString = "";
        for( $i=0; $i < strlen($encoded); $i+=2 ) {
            $hex = substr($encoded,$i,2);
            $byteString .= sprintf('%08b', hexdec( $hex ));
        }

        for( $i=0; $i < strlen($byteString); $i+=8 ) {
            $octets[$count] = substr($byteString, $i, 8);
            $rest[$count] = substr( $octets[$count], 0, ($s % 8) );
            $septets[$count] = substr( $octets[$count], ($s % 8) );

            $s++;
            $count++;

            if( $s == 8 ) {
                $s = 1;
            }
        }

        for( $i=0; $i < count($rest); $i++ ) {
            if( ($i % 7) == 0 ) {
                if( $i != 0 ) {
                    $chars++;
                    $chrVal = bindec($rest[$i - 1]);

                    if( $escaped ) {
                        $message .= $this->getSevenBitExtendedChar($chrVal);
                        $escaped = false;
                    } else if( $chrVal == 27 && $chars > $skip ) {
                        $escaped = true;
                    } else if( $chars > $skip ) {
                        $message .= static::$sevenbitdefault[$chrVal]; 
                    }

                    $matchcount++; 
                }

                $chars++;
                $chrVal = bindec($septets[$i]);
                if( $escaped ) {
                    $message .= $this->getSevenBitExtendedChar($chrVal);
                    $escaped = false;
                } else if( $chrVal == 27 && $chars > $skip ) {
                    $escaped = true;
                } else if( $chars > $skip ) {
                    $message .= static::$sevenbitdefault[$chrVal]; 
                }

                $matchcount++;
            } else {
                $chars++;
                
                $chrVal = bindec($septets[$i] . $rest[$i - 1]);

                if( $escaped ) {
                    $message .= $this->getSevenBitExtendedChar($chrVal);
                    $escaped = false;
                } else if( $chrVal == 27 && $chars > $skip ) {
                    $escaped = true;
                } else if( $chars > $skip ) {
                    $message .= static::$sevenbitdefault[$chrVal];
                }

                $matchcount++;
            }
        }

        if( $matchcount != $length ) {
            $chars++;
            $chrVal = bindec($rest[$i-1]);
            if( !$escaped ) {
                if( $chars > $skip ) {
                    $message .= static::$sevenbitdefault[$chrVal];
                }
            } else if( $chars > $skip ) {
                $message .= $this->getSevenBitExtendedChar($chrVal);
            }
        }

        return $message;
    }

	/**
     * Returns the User Message in a readable format
	 *
     * @param int
     * @param string
     * @param int
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120919 11:10
	 */
    protected function decodeMessage16Bit( $encoded, $skip=0 )
    {
        if( $skip > 0 ) {
            $encoded = mb_substr($encoded, $skip);
        }
        return mb_convert_encoding(pack('H*', $encoded), 'UTF-8', 'UCS-2');
    }

	/**
     * Returns the User Message in a readable format
     *
     * @param int
     * @param string
     * @param int OPTIONAL Default: null
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120919 11:10
	 */
    protected function decodeMessage8Bit( $encoded, $length )
    {
        $message = "";
        for( $i=0; $i < $length; $i+=2 ) {
            $hex = substr($encoded, $i, 2);
            $message .= chr( hexdec($hex) );
        }

        return $message;
    }

	/**
	 * Returns the character for the given code
     *
	 * @param int
	 * @return char
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120919 11:10
	 */
    public function getSevenBitExtendedChar( $code ) 
    {
        $key = array_search($code, static::$sevenbitextended);

        if( $key !== false ) {
            return $key;
        }

        return '\u2639';
    }

	/**
     * Returns the code for the given extended char
	 *
	 * @param char
	 * @return int
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120921 15:00
	 */
    public function getSevenBitExtendedCode( $char )
    {
        if( array_key_exists($char, static::$sevenbitextended) ) {
            return static::$sevenbitextended[$char];
        }

        return 0;
    }

	/**
	 * Returns the code for the given char
	 *
	 * @param char
	 * @return int
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120921 15:00
	 */
    public function getSevenBitCode( $char )
    {
        return (int)array_search($char, static::$sevenbitdefault);
    }

	/**
	 * Encodes the given Text into 7-Bit
	 *
	 * @param string
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120921 15:00
	 */
    public function encodeGSM7Bit( $message )
    {
        if( !static::isGSM7Bit( $message ) ) {
            throw new \InvalidArgumentException("Given string is not 7-Bit GSM compatible!");
        }

        $encoded = "";
        $padding = chr(0x0D);
        $tmp = $message;
        $message = "";
        $firstOctet = "";
        $secondOctet = "";

        for( $i=0; $i < mb_strlen($tmp); $i++ ) {
            $char = mb_substr($tmp, $i, 1); // Unicode only works with mb_substr, not $tmp[$i]
            if( $this->getSevenBitExtendedCode( $char ) ) {
                $message .= chr(0x1B);
            }

            $message .= $char;
        }

        $length = mb_strlen($message);

        if( ($length % 8) == 7 || ( ($length % 8) == 0 && $length > 0 && mb_substr($message, -1) == $padding ) ) {
            $message .= $padding;
        }

        for( $i=0; $i <= mb_strlen($message); $i++ ) {
            if( $i == mb_strlen($message) ) {
                if( $secondOctet != "" ) {
                    $encoded .= sprintf('%02s', dechex( bindec( $secondOctet ) ));
                }
                break;
            }

            $char = mb_substr($message, $i, 1);

            if( $char == chr(0x1B) ) {
                $current = sprintf('%07b', 0x1B);
            } else {
                $tmp = $this->getSevenBitExtendedCode( $char );
                if( $tmp == 0 ) {
                    $tmp = $this->getSevenBitCode( $char );
                }

                $current = sprintf('%07b', $tmp);
            }

            $currentOctet = "";

            if( $i != 0 && ($i % 8) != 0 ) {
                $firstOctet = mb_substr($current, 7-($i%8));
                $currentOctet = $firstOctet . $secondOctet;

                $encoded .= sprintf('%02s', dechex( bindec($currentOctet) ));
                $secondOctet = mb_substr($current, 0, 7-($i%8));
            } else {
                $secondOctet = mb_substr($current, 0, 7-($i%8));
            }
        }

        return strtoupper($encoded);
    }

	/**
     * Encodes given UTF-8 string into 16-Bit UCS-2 Hexcode
     * Requires paramter as UTF-8! 
	 *
	 * @param string
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120924 12:40
	 */
    public function encodeUCS2( $message )
    {
        return strtoupper( bin2hex( mb_convert_encoding($message, 'UCS-2', 'UTF-8') ) );
    }

	/**
	 * Encodes given UTF-8 string into 8bit Hexcode
	 *
	 * @param string
     * @return string
     * @throws \RuntimeException
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120924 12:40
	 */
    public function encodeGSM8Bit( $message )
    {
        throw new \RuntimeException('Not supported yet.');
    }

    /**
     * Checks if given Message is GSM 7 Bit
     *
     * @param string
     * @return boolean
     * @author Max Boesing <max@kriegt.es>
     * @since 20121015 14:43
     */
    public static function isGSM7Bit( $message )
    {
        $gsm7bitChars = join( static::$sevenbitdefault ) . join( static::$sevenbitextended );
        for( $i=0; $i < mb_strlen($message); $i++ ) {
            $char = mb_substr($message,$i,1);
            if( mb_strpos($gsm7bitChars, $char)===false && $char != "\\" ) {
                return false;
            }
        }
        return true;
    }

	/**
	 * Returns all valid encodings for this Converter
	 *
	 * @return string[]
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120924 16:09
	 */
    public static function getValidEncodings()
    {
        $validEncodings = array();
        $refClass = new \ReflectionClass(__CLASS__);
        foreach( $refClass->getConstants() AS $name => $value ) {
            if( substr($name, 0, 9) == 'ENCODING_' ) {
                array_push( $validEncodings, $value );
            }
        }
        return $validEncodings;
    }

	/**
	 * Converts semi octet to string
	 *
	 * @param string
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121012 15:49
	 */
    public function semiOctetToString( $input )
    {
        $out = "";
        for( $i=0; $i<strlen($input); $i+=2 ) {
            $temp = substr($input,$i,2);
            $out .= $this->phoneNumberMap( $temp[1] ) . $this->phoneNumberMap( $temp[0] );
        }

        return $out;
    }

	/**
	 * Converts the given char to phone number
	 *
	 * @param string
	 * @return string|int
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121012 15:49
	 */
    protected function phoneNumberMap( $char )
    {
        if( is_numeric($char) && $char >= 0 && $char <= 9 ) {
            return $char;
        }

        switch( strtoupper($char) ) {
            case '*':
                return 'A';
            case '#':
                return 'B';
            case 'A':
                return 'C';
            case 'B':
                return 'D';
            case 'C':
                return 'E';
            case '+':
                return '+';
            default:
                return 'F';
        }
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
