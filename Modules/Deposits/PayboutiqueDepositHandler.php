<?php

namespace NaxCrmBundle\Modules\Deposits;

use NaxCrmBundle\Entity\Deposit;

class PayboutiqueDepositHandler extends BaseDepositHandler
{
    public function makeDeposit($amount) {
        $this->validateCardNumber();
        $this->method = 'CreditCard';
//        $this->method = $this->getRequiredParam('method');
//        if (!in_array($method, ['Qiwi', 'CreditCard', 'YandexMoney', 'BankTransfer'])) {
//            return $this->getFailedJsonResponse(null, 'Invalid method');
//        }

        $this->createDeposit($amount);
        //save to get deposit id
        $this->em->persist($this->deposit);
        $this->em->flush();

        $time = str_replace(array('-', ':'), array('', ''), date(DATE_ISO8601));
        $param2 = '<Param2>int</Param2>';

        $host = $this->config['callback_host'];

        $signature = $this->getPayboutiqueSignature($time);
        $template = str_replace([
            '%method%',
            '%user_id%',
            '%merchant_id%',
            '%time%',
            '%currency%',
            '%amount%',
            '%order_id%',
            '%site%',
            '%signature%',
            '%success_url%',
            '%failure_url%',
            '%postback_url%',
            '%param2%'
        ], [
            $this->method,
            $this->config['payboutique']['user_id'],
            $this->config['payboutique']['merchant_id'],
            $time,
            $this->currency,
            $amount,
            $this->deposit->getId(),
            $host,
            $signature,
            $host . $this->router->generate('open_v1_payboutique_stub'),
            $host . $this->router->generate('open_v1_payboutique_callback_decline'),
            $host . $this->router->generate('open_v1_payboutique_callback'),
            $param2
        ], $this->getTemplate());
        $url = $this->config['payboutique']['host'];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => ['xml' => $template]
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        if (!$result) {
            $this->deposit->setComment('Request to Payboutique failed');
            throw $this->Exception("Request to payment provider failed \n Deposit #".$this->deposit->getId().' - '.json_encode([$url, $template]), 500);
        }
        try{
            $xml = new \SimpleXMLElement($result);
        }
        catch(\Exception $e){
            throw $this->Exception("Bad response from payment provider \n Deposit #".$this->deposit->getId().' - '.$result, 503);
        }

        $this->em->persist($this->deposit);

        if (!isset($xml->Body) || !isset($xml->Body->Order) || !isset($xml->Body->Order->RedirectURL)) {
            $this->deposit->setComment('Bad response from payment provider');
            throw $this->Exception("Provider reject payment \n Deposit #".$this->deposit->getId().' - '.$result, 503);
        }

        return [
            'status' => $this->deposit->getStatus(),
            'url' => $xml->Body->Order->RedirectURL->__toString(),
            'depositId' => $this->deposit->getId()
        ];
    }

    private function getPayboutiqueSignature($time, $reference = '') {
        return strtoupper(hash('sha512', $this->config['payboutique']['user_id'] .
            $this->config['payboutique']['password_sha512'] . strtoupper($time) . $reference));
    }

    private function getTemplate() {
        return "<Message version=\"0.5\"><Header><Identity><UserID>%user_id%</UserID><Signature>
                    %signature%</Signature></Identity><Time>%time%</Time></Header><Body type=\"GetInvoice\" live=\"true\"><Order
                        paymentMethod=\"%method%\" buyerCurrency=\"%currency%\"><MerchantID>%merchant_id%</MerchantID><OrderID>%order_id%
                </OrderID><AmountMerchantCurrency>%amount%</AmountMerchantCurrency><MerchantCurrency>%currency%
                </MerchantCurrency><Label>Deposit #%order_id%</Label><SiteAddress>%site%</SiteAddress><Description>Deposit
                    #%order_id%</Description><SuccessURL>%success_url%</SuccessURL><FailureURL>%failure_url%</FailureURL><PostbackURL>
                    %postback_url%</PostbackURL>%param2%</Order></Body></Message>";
    }
}