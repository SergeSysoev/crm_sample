<?php

namespace NaxCrmBundle\Modules\Deposits;

use NaxCrmBundle\Entity\Deposit;
use NaxCrmBundle\Debug;

class OrangepayDepositHandler extends BaseDepositHandler
{
    public function makeDeposit($amount) {
        // $this->validateCardNumber();
        $this->method = 'CreditCard';

        $this->createDeposit($amount);

        $this->em->persist($this->deposit);
        $this->em->flush();

        $data = [
            'paymentSystem' => 'OrangePay',
            'pay_method'    => 'card',
            'currency'      => $this->currency,
            'amount'        => ($amount * 100),
            'reference_id'  => '1_' . $this->deposit->getId() . '_d_' . sha1($this->config['orange_pay']['api_key']),
            'client_ip'     => $_SERVER['REMOTE_ADDR'],
            'email'         => $this->client->getEmail(),
            'description'   => "Deposit for {$this->client->getFullName()} account #{$this->account->getId()}"
        ];
        $url = 'https://pay.payorange-pay.com/index.php/api/v1/widget/transactions';//$this->config['orange_pay']['url'];
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_USERPWD        => $this->config['orange_pay']['api_key'].':'.$this->config['orange_pay']['password_key'],
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        if (!$result) {
            $this->deposit->setComment('Request to payment provider failed');
            throw $this->Exception("Request to payment provider failed \n Deposit #".$this->deposit->getId().' - '.json_encode([$url, $data]), 500);
        }
        $result = json_decode($result, 1);
        Debug::$messages[] = print_r($data, 1);
        Debug::$messages[] = print_r($result, 1);
        Debug::$messages[] = $this->router->generate('open_v1_orangepay_callback');

        if (empty($result['status']) || $result['status'] != 'success' || empty($result['data']['redirect_url'])) {
            if (is_array($result)) {
                $result = empty($result['message']) ? json_encode($result) : $result['message'];
            }
            $this->deposit->setComment($result?:'Bad response from payment provider');
            throw $this->Exception("Provider reject payment \n Deposit #".$this->deposit->getId().' - '.$result, 503);
        }

        return [
            'status' => $this->deposit->getStatus(),
            'url' => $result['data']['redirect_url'],
            'iframe' => 1,
            'depositId' => $this->deposit->getId(),
        ];
    }

}