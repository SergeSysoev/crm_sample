<?php

namespace NaxCrmBundle\Modules\Deposits;

use NaxCrmBundle\Entity\Deposit;
use NaxCrmBundle\Debug;

class NetpayDepositHandler extends BaseDepositHandler
{
    const PSP_NETPAY_URL = "https://process.netpay-intl.com/member/remote_charge.asp?";
    // const PSP_NETPAY_COMPANYNUM = 2635996;
    // const PSP_NETPAY_HASH_KEY = 'Z8E2XCOPSK';
    //Username : brokerzunion

    public function makeDeposit($amount) {
        $_netpay_currencies = array(
            'ILS' => 0,
            'USD' => 1,
            'EUR' => 2,
            'GBP' => 3,
            'AUD' => 4,
            'CAD' => 5,
            'JPY' => 6,
            'NOK' => 7,
            'PLN' => 8,
            'MXN' => 9,
            'ZAR' => 10,
            'RUB' => 11,
            'TRY' => 12,
            'CHF' => 13,
            'INR' => 14,
            'DKK' => 15,
            'SEK' => 16,
            'CNY' => 17,
            'HUF' => 18,
            'NZD' => 19,
            'HKD' => 20,
            'KRW' => 21,
            'SGD' => 22,
            'THB' => 23,
            'BSD' => 24
        );
        if (!isset($_netpay_currencies[$this->currency])) {
            throw $this->Exception('Invalid currency');
        }

        $this->method = 'CreditCard';
        $this->validateCardNumber();
        $this->createDeposit($amount);
        //save to get deposit id
        $this->em->persist($this->deposit);
        $this->em->flush();
        $data = $this->dep_data;

        $time = str_replace(array('-', ':'), array('', ''), date(DATE_ISO8601));

        $host = $this->config['callback_host'];
        $address = [
            'zip' => $this->client->getZip(),
            'city' => $this->client->getCity(),
            'address' => $this->client->getAddress(),
            'state' => $this->client->getState(),
            'country' => $this->client->getCountry()->getCode(),//->getName(),
        ];
        foreach ($address as $k => $v) {
            if(empty($v)){
                $address[$k] = $k;
            }
        }
        if($address['country']=='country'){
            $address['country'] = 'US';
        }

        $send = array(
            'CompanyNum' => $this->config['netpay']['api_key'],
            'TransType' => 0,
            'CardNum' => $this->cardNumber,
            'ExpMonth' => $data['card_expiry_month'],
            'ExpYear' => $data['card_expiry_year'],
            'Member' => $data['card_name'],
            'CVV2' => $data['card_cvv'],
            'TypeCredit' => 1,
            'Payments' => 1,
            'Amount' => $amount,
            'Currency' => $_netpay_currencies[$this->currency],
            'Email' => $this->client->getEmail(),
            'PhoneNumber' => $this->client->getPhone(),
            'BillingAddress1' => $address['address'],
            'BillingCity' => $address['city'],
            'BillingZipCode' => $address['zip'],
            'BillingState' => $address['state'],
            'BillingCountry' => $address['country'],
            'Order' => $this->deposit->getId(),
            'RetURL' => $host . $this->router->generate('open_v1_netpay_callback')
        );

        $send['signature'] = $this->getSignature($send);

        Debug::$messages[] = $url = self::PSP_NETPAY_URL.http_build_query($send);
        $retstr = file_get_contents($url);
        if (!$retstr) {
            $this->deposit->setComment('Request to payment provider failed');
            $this->Exception("Request to payment provider failed \n Deposit #".$this->deposit->getId().' - '.$send, 500);
        }
        parse_str($retstr, $ret);
        Debug::$messages[] = $ret;

        if (!isset($ret['Reply']) || $ret['Reply'] != '000') {
            if ($ret['Reply'] == '553') {
                return [
                    'status' => $this->deposit->getStatus(),
                    'depositId' => $this->deposit->getId(),
                    // 'iframe' => 1,
                    'url' => $ret['D3Redirect'],
                    'html' => ''
                ];
            }
            $this->deposit->setComment($ret['ReplyDesc']?:'Bad response from payment provider');
            throw $this->Exception("Provider reject payment \n Deposit #".$this->deposit->getId()." - ERROR #{$ret['Reply']} - {$ret['ReplyDesc']}", 503);
        }

        $this->success();

        file_put_contents('/srv/deps.json', json_encode($send)."\n", FILE_APPEND);

        return [
            'status' => $this->deposit->getStatus(),
            'depositId' => $this->deposit->getId(),
        ];
    }

    private function getSignature($send = []) {
        return base64_encode(hash('sha256', implode([
            $send['CompanyNum'], $send['TransType'], $send['TypeCredit'],
            $send['Amount'], $send['Currency'], $send['CardNum'],
            $this->config['netpay']['password_key']
        ])));
    }
}