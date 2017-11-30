<?php

namespace NaxCrmBundle\Modules\Deposits;

use NaxCrmBundle\Entity\Deposit;
use NaxCrmBundle\Debug;

class MasterpayDepositHandler extends BaseDepositHandler
{
    const WEB_PURCHASE = "/FE/rest/tx/purchase/w/execute";
    // const PSP_NETPAY_COMPANYNUM = 2635996;
    // const PSP_NETPAY_HASH_KEY = 'Z8E2XCOPSK';
    //Username : brokerzunion

    public function makeDeposit($amount) {
    	$config = $this->config['masterpay'];
        $merchantIdHash = $this->hashDataInBase64($config['merchantId']);
        $merchantAccountIdHash = $this->hashDataInBase64($config['merchantAccountId']);
      	$encryptedAccountUsername = $this->encryptDataInBase64($config['accountUserName'], $config['accountKey']);
      	$encryptedAccountPassword = $this->encryptDataInBase64($config['accountPassword'], $config['accountKey']);
      	$requestTime = $this->getSysDateTime();

        $this->method = 'CreditCard';
        $this->createDeposit($amount);
        //save to get deposit id
        $this->em->persist($this->deposit);
        $this->em->flush();

        $host = $this->config['callback_host'];

        $send = [
	        'requestTime' => $requestTime,
	        'merchantIdHash' => $merchantIdHash,
	        'merchantAccountIdHash' => $merchantAccountIdHash,
	        'encryptedAccountUsername' => $encryptedAccountUsername,
	        'encryptedAccountPassword' => $encryptedAccountPassword,
	        'lang'=> 'en',
	        'transactionInfo'=> [
	          'apiVersion'=> '1.0.0',
	          'requestId'=> /* 'ORDER_'. */$this->deposit->getId(),
	          'txSource'=> '1',
	          'recurrentType'=> '1',
	          // 'perform3DS'=> '0',
	          'perform3DS'=> '1',
	          'orderData'=> [
	            'orderId'=> $this->deposit->getId(),
	            'orderDescription'=> 'order-' + $this->deposit->getId(),
	            'amount'=> $amount,
	            'currencyCode'=> $this->deposit->getCurrency(),
	            'billingAddress'=> [
	               'firstName' => $this->client->getFirstname(),
	               'lastName' => $this->client->getLastname(),
	              /* 'address1' => data.address,*/
	              // 'address2' => data.address,
	              /* 'city' => data.city,
	               'zipcode' => data.zip,
	               'stateCode' => data.state,
	               'countryCode' => data.country, */
	              'mobile' => $this->client->getPhone(),
	              'phone' => $this->client->getPhone(),
	              'email'=> $this->client->getEmail(),
	              // 'fax' => data.phone
	            ],
	            'shippingAddress'=> null,
	            'personalAddress'=> null
	          ],
	          'statementText'=> 'Payment',
	          'cancelUrl'=> $host.$this->router->generate('open_v1_masterpay_cancel'),
	          'returnUrl'=> $host.$this->router->generate('open_v1_masterpay_return'),
	          'notificationUrl'=> $host.$this->router->generate('open_v1_masterpay_callback')
	        ]
		];
		$send['signature'] = $this->createSignature($send, $config['accountKey']);

		$req = json_encode($send);
		$base64EncodedJsonRequest = base64_encode($req);
		$requestUrl = $config['host'].self::WEB_PURCHASE;
		\NaxCrmBundle\Debug::$messages[]=compact('requestUrl', 'req');

        return [
            'html' => "<html><body OnLoad='AutoSubmitForm();'><form name='downloadForm' action='{$requestUrl}' method='POST'><input type='hidden' name='request' value='{$base64EncodedJsonRequest}'>
				<script>function AutoSubmitForm() {document.downloadForm.submit();}</script><h3>Transaction is in progress. Please wait...</h3></form></body></html>",
            'depositId' => $this->deposit->getId(),
        ];
    }

    public function encryptData($input, $key)
	{
		$alg = MCRYPT_RIJNDAEL_128; // AES
		$mode = MCRYPT_MODE_ECB; // ECB

		//padding
		$block_size = mcrypt_get_block_size($alg, $mode);
		$pad = $block_size - (strlen($input) % $block_size);
		$input .= str_repeat(chr($pad), $pad);

		$crypttext = mcrypt_encrypt($alg, $key, $input , $mode);
		return $crypttext;
	}

	public function encryptDataInBase64($input, $key)
	{
		$crypttext = $this->encryptData($input, $key);
		$encryptDataInBase64 = base64_encode($crypttext);
		return $encryptDataInBase64;
	}

	public function hashDataInBase64($input)
  	{
		$output = hash('sha256',$input,true);
		$hashDataInBase64 = base64_encode($output);
		return $hashDataInBase64;
    }

    public function createSignature($data,$key)
    {
    	$input = ''.
    		trim($data['requestTime']).
		    trim($data['merchantIdHash']).
		    trim($data['merchantAccountIdHash']).
		    trim($data['encryptedAccountUsername']).
		    trim($data['encryptedAccountPassword']).
		    trim($data['transactionInfo']['apiVersion']).
		    trim($data['transactionInfo']['requestId']).
		    trim($data['transactionInfo']['txSource']).
		    trim($data['transactionInfo']['recurrentType']).
		    trim($data['transactionInfo']['orderData']['orderId']).
		    trim($data['transactionInfo']['orderData']['orderDescription']).
		    trim($data['transactionInfo']['orderData']['amount']).
		    trim($data['transactionInfo']['orderData']['currencyCode']).
		    $data['transactionInfo']['orderData']['billingAddress']['firstName'].
		    $data['transactionInfo']['orderData']['billingAddress']['lastName'].
		    /* $data['transactionInfo']['orderData']['billingAddress']['address1']. */
		    // $data['transactionInfo']['orderData']['billingAddress']['address2'].
		    /* $data['transactionInfo']['orderData']['billingAddress']['city'].
		     $data['transactionInfo']['orderData']['billingAddress']['zipcode'].
		     $data['transactionInfo']['orderData']['billingAddress']['stateCode'].
		     $data['transactionInfo']['orderData']['billingAddress']['countryCode'].*/
		    $data['transactionInfo']['orderData']['billingAddress']['mobile'].
		    $data['transactionInfo']['orderData']['billingAddress']['phone'].
		    trim($data['transactionInfo']['orderData']['billingAddress']['email']).
		    // $data['transactionInfo']['orderData']['billingAddress']['fax'].
		    trim($data['transactionInfo']['statementText']).
		    trim($data['transactionInfo']['cancelUrl']).
		    trim($data['transactionInfo']['returnUrl']).
		    trim($data['transactionInfo']['notificationUrl']).
		    '';
    	$signature = $this->hashDataInBase64($this->encryptData($input, $key));
    	return $signature;
    }

    public function getSysDateTime()
    {
    	return date('Y-m-d H:i:s');
    }
}