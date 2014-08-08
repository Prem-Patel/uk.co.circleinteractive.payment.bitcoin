<?php

/**
 * Class to abstract the process of interacting with stored BitPay transaction data
 * @author  andyw@circle
 * @package uk.co.circleinteractive.payment.bitcoin
 */
class BitPay_Payment_BAO_Transaction {

    protected static $fields = array(
        'contribution_id', 'bitpay_id', 'url', 'posData', 'status', 'btcPrice',
        'price', 'currency', 'invoiceTime', 'expirationTime', 'currentTime',
        'btcPaid', 'rate', 'exceptionStatus'
    ); 

    public static function getOutstanding() {

        # check for any non-finalized invoices in the past 24 hours
        $dao = CRM_Core_DAO::executeQuery("
            SELECT cbt.* FROM civicrm_bitpay_transaction cbt
        INNER JOIN civicrm_contribution c ON c.id = cbt.contribution_id
             WHERE c.receive_date > DATE_SUB(NOW(), INTERVAL 1 DAY)
               AND cbt.status NOT IN ('complete', 'expired', 'invalid')
        ");

        $records = array();

        while ($dao->fetch()) {
            $record = array();
            foreach (self::$fields as $field)
                $record[$field] = $dao->$field;
            $records[] = $record;
        }

        return $records;

    }

    public static function installSchema() {
        
        CRM_Core_DAO::executeQuery("
            CREATE TABLE IF NOT EXISTS `civicrm_bitpay_transaction` (
              `contribution_id` int(10) unsigned NOT NULL,
              `bitpay_id` varchar(64) NOT NULL,
              `url` varchar(255) NOT NULL,
              `posData` text NOT NULL,
              `status` varchar(16) NOT NULL,
              `btcPrice` double NOT NULL,
              `price` decimal(10,2) NOT NULL,
              `currency` varchar(3) NOT NULL,
              `invoiceTime` int(10) unsigned NOT NULL,
              `expirationTime` int(10) unsigned NOT NULL,
              `currentTime` int(10) unsigned NOT NULL,
              `btcPaid` double NOT NULL,
              `rate` double NOT NULL,
              `exceptionStatus` varchar(255) NOT NULL,
              PRIMARY KEY (`contribution_id`),
              UNIQUE KEY `index_bitpay_id` (`bitpay_id`),
              KEY `index_expirationTime` (`expirationTime`),
              KEY `index_invoiceTime` (`invoiceTime`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
        ");

    }

    public static function load($params) {

        if (!$params)
            CRM_Core_Error::fatal(ts("Empty params list in %1::%2", array(
                1 => __CLASS__,
                2 => __METHOD__
            )));

        $conditions = array();
        $i = 0;

        foreach ($params as $key => $value) {
            $conditions[]  = "$key = %" . ++$i;
            $db_params[$i] = array($value, 'String'); 
        }

        $where_clause = implode(' AND ', $conditions);

        $dao = CRM_Core_DAO::executeQuery("
            SELECT * FROM civicrm_bitpay_transaction 
            WHERE $where_clause
        ", $db_params);

        $record = array();

        if ($dao->fetch())
            foreach (self::$fields as $field)
                $record[$field] = $dao->$field;

        return $record;

    }

    public static function save($params) {

        $missing_params = array();

        foreach (self::$fields as $param)
            if (!isset($params[$param]))
                $missing_params[] = $param;

        if ($missing_params)
            CRM_Core_Error::fatal(ts("Missing required params in %1::%2: %3", array(
                1 => __CLASS__,
                2 => __METHOD__,
                3 => implode(', ', $missing_params)
            )));

        CRM_Core_DAO::executeQuery("
            REPLACE INTO civicrm_bitpay_transaction (
                contribution_id, bitpay_id, url, posData, status, btcPrice, price, 
                currency, invoiceTime, expirationTime, currentTime, btcPaid, rate, 
                exceptionStatus
            ) VALUES (
                %1, %2, %3, %4, %5, %6, %7, %8, %9, %10, %11, %12, %13, %14
            )
        ", array(
              1  => array($params['contribution_id'], 'Positive'),
              2  => array($params['bitpay_id'],       'String'),
              3  => array($params['url'],             'String'),
              4  => array($params['posData'],         'String'),
              5  => array($params['status'],          'String'),
              6  => array($params['btcPrice'],        'Float'),
              7  => array($params['price'],           'Money'),
              8  => array($params['currency'],        'String'),
              9  => array($params['invoiceTime'],     'Positive'),
              10 => array($params['expirationTime'],  'Positive'),
              11 => array($params['currentTime'],     'Positive'),
              12 => array($params['btcPaid'],         'Float'),
              13 => array($params['rate'],            'Float'),
              14 => array($params['exceptionStatus'], 'String')
           )
        );

    }

}