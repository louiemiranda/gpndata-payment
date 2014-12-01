<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * GPNData Gateway Transaction Processing Services
 * For transaction processing of the following
 * - Initial pre-authorization or direct settlement using direct and 3DS
 * - 3DS continuation
 * - Recurring or Manual Rebill (You need the gate-trans-id and other tokens here)
 *
 * PHP version 5
 *
 * @copyright     Copyright (c) 2014, Louie Miranda
 * @author        Louie Miranda <lmiranda@gmail.com>
 */

require_once dirname(__FILE__) . '/Library/PaymentGateway.php';
require_once dirname(__FILE__) . '/Library/XML2Array.php';

class Custom_GPNData extends PaymentGateway {
    
    private $_version = '1.9.15'; // API Doc
    private $_apiUser;
    private $_apiPassword;
    private $_apiKey;
    private $_apiCmd;
    private $_URL;
    
    function __construct($environment)
    {
        if ($environment == "test") {
            $this->_URL = 'https://txtest.txpmnts.com/api/transaction/'; // INTEGRATION
            // $this->Simulate = TRUE; // Simulate transactions - TRUE TO ALLOW INVALID TRANS, FALSE WHEN PRODUCTION
        }
        else if ($environment == "live") {
            $this->_URL = 'https://txpmnts.com/api/transaction/'; // PRODUCTION
            // $this->Simulate = FALSE; // Simulate transactions - TRUE TO ALLOW INVALID TRANS, FALSE WHEN PRODUCTION
        }
        else {
            die("Missing environment, please check your data.");
        }
    }
    
    public function setApiUser($value) {
        $this->_apiUser = $value;
    }
    public function getApiUser() {
        return $this->_apiUser;
    }
    
    public function setApiPasswd($value) {
        $this->_apiPassword = $value;
    }
    public function getApiPasswd() {
        return $this->_apiPassword;
    }
    
    public function setApiKey($value) {
        $this->_apiKey = $value;
    }
    public function getApiKey() {
        return $this->_apiKey;
    }
    
    public function setApiCmd($value) {
        $this->_apiCmd = $value;
    }
    public function getApiCmd() {
        return $this->_apiCmd;
    }
    
   
    /**
     * 700- Start Credit Card charge (3DS Enabled)
     */
    public function startCharge($data)
    {
        $_Path = '';
        
        $start = microtime(true);

        $xml = new DOMDocument('1.0', 'utf-8');

        $root = $xml->appendChild($xml->createElement("transaction"));
        $root->appendChild($xml->createElement('apiUser', $this->getApiUser()));
        $root->appendChild($xml->createElement('apiPassword', $this->getApiPasswd()));
        $root->appendChild($xml->createElement('apiCmd', $data['apiCmd']));

        $transaction = $root->appendChild($xml->createElement("transaction"));
        $transaction->appendChild($xml->createElement('merchanttransid', $data['merchanttransid']));
        $transaction->appendChild($xml->createElement('amount', $data['amount']));
        $transaction->appendChild($xml->createElement('curcode', $data['curcode']));
        $transaction->appendChild($xml->createElement('statement', $data['statement']));
        $transaction->appendChild($xml->createElement('description', $data['description']));
        $transaction->appendChild($xml->createElement('merchantspecific1', $data['merchantspecific1']));
        $transaction->appendChild($xml->createElement('merchantspecific2', $data['merchantspecific2']));
        $transaction->appendChild($xml->createElement('merchantspecific3', $data['merchantspecific3']));

        if ($data['rebill']) {
            $rebill = $root->appendChild($xml->createElement("rebill"));
            $rebill->appendChild($xml->createElement('freq', $data['rebill_freq']));
            $rebill->appendChild($xml->createElement('start', $data['rebill_start']));
            $rebill->appendChild($xml->createElement('amount', $data['rebill_amount']));
            $rebill->appendChild($xml->createElement('desc', $data['rebill_desc']));
            $rebill->appendChild($xml->createElement('count', $data['rebill_count']));
            // $rebill->appendChild($xml->createElement('followup_time', $data['rebill_followup_time']));
            // $rebill->appendChild($xml->createElement('followup_amount', $data['rebill_followup_amount']));
        }

        $customer = $root->appendChild($xml->createElement("customer"));
        $customer->appendChild($xml->createElement('firstname', $data['firstname']));
        $customer->appendChild($xml->createElement('lastname', $data['lastname']));
        $customer->appendChild($xml->createElement('birthmonth', $data['birthmonth']));
        $customer->appendChild($xml->createElement('birthyear', $data['birthyear']));
        $customer->appendChild($xml->createElement('email', $data['email']));
        $customer->appendChild($xml->createElement('countryiso', $data['countryiso']));
        $customer->appendChild($xml->createElement('stateregioniso', $data['stateregioniso']));
        $customer->appendChild($xml->createElement('zippostal', $data['zippostal']));
        $customer->appendChild($xml->createElement('city', $data['city']));
        $customer->appendChild($xml->createElement('address1', $data['address1']));
        $customer->appendChild($xml->createElement('address2', $data['address2']));
        $customer->appendChild($xml->createElement('phone1country', $data['phone1country']));
        $customer->appendChild($xml->createElement('phone1area', $data['phone1area']));
        $customer->appendChild($xml->createElement('phone1phone', $data['phone1phone']));
        $customer->appendChild($xml->createElement('phone2country', $data['phone2country']));
        $customer->appendChild($xml->createElement('phone2area', $data['phone2area']));
        $customer->appendChild($xml->createElement('phone2phone', $data['phone2phone']));
        $customer->appendChild($xml->createElement('accountid', $data['accountid']));
        $customer->appendChild($xml->createElement('ipaddress', $data['ipaddress']));

        $creditcard = $root->appendChild($xml->createElement("creditcard"));
        $creditcard->appendChild($xml->createElement('ccnumber', $data['ccnumber']));
        $creditcard->appendChild($xml->createElement('cccvv', $data['cccvv']));
        $creditcard->appendChild($xml->createElement('expmonth', $data['expmonth']));
        $creditcard->appendChild($xml->createElement('expyear', $data['expyear']));
        $creditcard->appendChild($xml->createElement('nameoncard', $data['nameoncard']));
        $creditcard->appendChild($xml->createElement('billingcountryiso', $data['billingcountryiso']));
        $creditcard->appendChild($xml->createElement('billingstateregioniso', $data['billingstateregioniso']));
        $creditcard->appendChild($xml->createElement('billingzippostal', $data['billingzippostal']));
        $creditcard->appendChild($xml->createElement('billingcity', $data['billingcity']));
        $creditcard->appendChild($xml->createElement('billingaddress1', $data['billingaddress1']));
        $creditcard->appendChild($xml->createElement('billingaddress2', $data['billingaddress2']));
        $creditcard->appendChild($xml->createElement('billingphone1country', $data['billingphone1country']));
        $creditcard->appendChild($xml->createElement('billingphone1area', $data['billingphone1area']));
        $creditcard->appendChild($xml->createElement('billingphone1phone', $data['billingphone1phone']));

        $checksum = sha1($this->getApiUser() . $this->getApiPasswd() . $data['apiCmd'] . $data['merchanttransid'] . $data['amount'] . $data['curcode'] . $data['ccnumber'] . $data['cccvv'] . $data['nameoncard'] . $this->getApiKey());
        $root->appendChild($xml->createElement('checksum', $checksum));

        $auth = $root->appendChild($xml->createElement("auth"));
        $auth->appendChild($xml->createElement('type', $data['type']));
        $auth->appendChild($xml->createElement('sid', $data['sid']));

        $xml->formatOutput = TRUE;
        $requestXML = $xml->saveXML();

        $this->setUrl($this->_URL.$_Path);
        $responseXML = $this->postXml($requestXML);

        $response  = XML2Array::createArray($responseXML);
        
        return $response;
    }

    /**
     * 705- 3DS Transaction Continuation
     */
    public function transactionContinuation($data)
    {
        $_Path = '';
        
        $xml = new DOMDocument('1.0', 'utf-8');

        $root = $xml->appendChild($xml->createElement("transaction"));
        $root->appendChild($xml->createElement('apiUser', $this->getApiUser()));
        $root->appendChild($xml->createElement('apiPassword', $this->getApiPasswd()));
        $root->appendChild($xml->createElement('apiCmd', $data['apiCmd']));
        
        $auth = $root->appendChild($xml->createElement("auth"));
        $auth->appendChild($xml->createElement('type', $data['type']));
        $auth->appendChild($xml->createElement('ACSRes', $data['ACSRes']));
        $auth->appendChild($xml->createElement('MD', $data['MD']));
        
        $checksum = sha1($this->getApiUser() . $this->getApiPasswd() . $data['apiCmd'] . $data['type'] . $data['MD'] . $this->getApiKey());
        $root->appendChild($xml->createElement('checksum', $checksum));

        $xml->formatOutput = TRUE;
        $requestXML = $xml->saveXML();

        $this->setUrl($this->_URL.$_Path);
        $responseXML = $this->postXml($requestXML);

        $response  = XML2Array::createArray($responseXML);

        return $response;
        
    }

    /**
     * 756- Manual Rebill Request
     * 
     * Description: Allows the merchant to manually request the execution of the Rebill and follow-up transaction
     * when authorized by Merchant Agreement and indicated in the Merchant Profile. (Submitted using the 700 transaction)
     * 
     * Note: All Manual Rebill Requests must occur within the timeframe(s) indicated in the rebillwindows->windows tags
     * returned in the 700 Response or an error will be returned.
     * 
     * Direction: From merchant transaction server to the Gateway Server.
     * Posting URL: (see the integration instructions sent to you)
     * 
     * @method manualRebill()
     * @param array $data
     */
    public function manualRebill($data)
    {
        $_Path = '';
        
        $xml = new DOMDocument('1.0', 'utf-8');

        $root = $xml->appendChild($xml->createElement("transaction"));
        $root->appendChild($xml->createElement('apiUser', $this->getApiUser()));
        $root->appendChild($xml->createElement('apiPassword', $this->getApiPasswd()));
        $root->appendChild($xml->createElement('apiCmd', $data['apiCmd']));
        $root->appendChild($xml->createElement('gatetransid', $data['gatetransid']));
        $root->appendChild($xml->createElement('rebillsecret', $data['rebillsecret']));
        
        $transaction = $root->appendChild($xml->createElement("transaction"));
        $transaction->appendChild($xml->createElement('amount', $data['amount']));
        $transaction->appendChild($xml->createElement('merchanttransid', $data['merchanttransid']));
        
        $checksum = sha1($this->getApiUser() . $this->getApiPasswd() . $data['apiCmd'] . $data['gatetransid'] . $data['merchanttransid'] . $this->getApiKey());
        $root->appendChild($xml->createElement('checksum', $checksum));

        $xml->formatOutput = TRUE;
        $requestXML = $xml->saveXML();

        $this->setUrl($this->_URL.$_Path);
        
        $response = $responseXML = array();
        $responseXML = $this->postXml($requestXML);

        if (isset($responseXML)) {
            $response = XML2Array::createArray($responseXML);
        }
        
        return $response;
    }
}