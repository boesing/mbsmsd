<?php
/**
 * Mysqli.php
 *
 * @package MBSmsd
 * @subpackage Backend
 * @author Max Boesing <max@kriegt.es>
 * @since 20121018 15:13
 */
namespace MBSmsd\Backend;
use MBSmsd\BackendInterface;
use MBSmsd\Message;
use MBSmsd\Modem;

/**
 * class Mysqli
 *
 * @package MBSmsd
 * @subpackage Backend
 * @author Max Boesing <max@kriegt.es>
 * @since 20121018 15:13
 */
class Mysqli extends \mysqli implements BackendInterface
{
    const       TABLE_INBOX         = 'inbox';
    const       TABLE_OUTBOX        = 'outbox';
    const       TABLE_SENT          = 'sent';

    protected   $initialized        = false;

	/**
	 * Initialize and check
	 *
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121018 16:06
	 */
    protected function initialize()
    {
        if( !$this->ping() ) {
            throw new \RuntimeException('We are not connected to the MySQL Database!');
        }

        if( $this->initialized ) {
            return;
        }

        $this->set_charset('UTF8');
        $this->real_query('SET NAMES UTF8');

        $this->initialized = true;
    }

	/**
	 * Stores the given Message to database
	 *
	 * @param Message
     * @return boolean
     * @throws \RuntimeException
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121018 15:13
	 */
    public function store( Message $m ) 
    {
        $this->initialize();
        $received = new \DateTime( $m->getTimestamp() );
        $sender = $m->getSender();
        $receiver = $m->getReceiver();
        $receipt = $m->getReceiptRequest();
        $smsc = $m->getSMSC();
        $message = $m->getText();

        $modem = $m->getModem();
        $inbox = false;
        if( $modem instanceOf Modem ) {
            if( $modem->getNumber() == $receiver ) {
                $inbox = true;
            } 
        }

        $tableName = $inbox?static::TABLE_INBOX:static::TABLE_SENT;

        $insertSQL = sprintf(
            'INSERT INTO `%s` (received, sender, receiver, receipt, smsc, message) VALUES (\'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\')',
            $tableName,
            $received->format('Y-m-d H:i:s'),
            $this->real_escape_string( $sender ),
            $this->real_escape_string( $receiver ),
            (int)$receipt,
            $this->real_escape_string( $smsc ),
            $this->real_escape_string( $message )
        );

        return $this->query( $insertSQL );
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
