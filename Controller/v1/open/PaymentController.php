<?php

namespace NaxCrmBundle\Controller\v1\open;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use NaxCrmBundle\Entity\Deposit;
use NaxCrmBundle\Modules\Email\Triggers\Client\DepositSuccess;
use NaxCrmBundle\Modules\Email\Triggers\Client\DepositFailed;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Get;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class PaymentController extends BaseController
{
    public $FULL_VIEW = false;

    function redirectToClient($success = true, $params = []){
        $client_host = $this->getParameter('client_host');
        if($success){
            $url = $client_host.'client/deposit/success';
        }
        else{
            $url = $client_host.'client/deposit/failure';
        }
        if($this->FULL_VIEW){
            $url.= '/full';
        }
        if(!empty($params)){
            $url.='?'.http_build_query($params);
        }
        return $this->redirect($url);
    }

    function success(&$deposit, $test = false){
        $deposit->setStatus(Deposit::STATUS_PROCESSED);
        if(!$test){
            $this->getPlatformHandler()->setDeposit($deposit);
        }
        $this->getEm()->persist($deposit);
        $this->getEm()->flush();

        $client = $deposit->getClient();
        $this->getTriggerService()->sendEmails(DepositSuccess::class(), compact('deposit', 'client'), $client->getLangcode());

        $this->getJsonResponse(null, 'Deposit was processed');
        return $this->redirectToClient(true);
    }
    function decline(&$deposit, $comment = 'Unknown error', $test = false){
        $deposit->setStatus(Deposit::STATUS_DECLINED);
        $deposit->setComment($comment);
        $this->getEm()->persist($deposit);
        $this->getEm()->flush();

        $client = $deposit->getClient();
        $this->getTriggerService()->sendEmails(DepositFailed::class(), compact('deposit', 'client'), $client->getLangcode());

        $this->getFailedJsonResponse(null, 'The payment declined');
        return $this->redirectToClient(false);
    }

    /**
     * @Post("/pl-callback", name="open_v1_pl_callback")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/payment",
     *    authentication = false,
     *    description = "Callback for PL",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when Content is empty",
     *          "Returned when Content is not a valid json",
     *          "Returned when Can`t get transaction data",
     *          "Returned when Can`t get reference_id",
     *        },
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="", "dataType"="string", "required"=true, "format"=".+", "description" = "set JSON with information"},
     *    },
     * )
     */
    public function callbackPlAction(Request $request)
    {
        $content = $request->getContent();

        $logger = $this->get('nax_crm.logger');
        $logger->createLog('deposits', 'Pl callback', $request->getPathInfo(), $content);
        $logger->saveLog();


        if (empty($content)) {
            throw new HttpException(400, 'Content is empty');
        }

        if (!$content = json_decode($content, true)) {
            throw new HttpException(400, 'Content is not a valid json');
        }
        if (!isset($content)) {
            throw new HttpException(400, 'Can`t get transaction data');
        }

        if (!isset($content['reference_id'])) {
            throw new HttpException(400, 'Can`t get reference_id');
        }
        /* @var $em EntityManager */
        $em = $this->getEm();

        $depositId = $content['reference_id'];
        /* @var $deposit Deposit */
        $deposit = $em->getRepository(Deposit::class())->find($depositId);
        if (!$deposit) {
            throw new HttpException(404, 'Deposit was not found');
        }

        /*if (Deposit::MAX_CONFIRMATION_TIME_IN_MINUTES) {
            $timeDiffMinutes = (new \DateTime())->diff($deposit->getCreated())->i;
            if ($timeDiffMinutes > Deposit::MAX_CONFIRMATION_TIME_IN_MINUTES) {
                $deposit->setStatus(Deposit::STATUS_EXPIRED);
                $deposit->setComment('The payment was not executed');
                $em->flush();
                return new \Exception('The payment was not executed');
            }
        }*/

        if (!$deposit || $deposit->getStatus() != Deposit::STATUS_PENDING) {
            return new \Exception('Deposit was already processed before');
        }
        if ($content['status'] == 'success') {
            $deposit->setStatus(Deposit::STATUS_PROCESSED);
        } else {
            $deposit->setStatus(Deposit::STATUS_DECLINED);
            $fail_comment = 'Failed. ';
            if (!empty($content['failure_message'])) {
                $fail_comment .= $content['failure_message'];
            }
            $deposit->setComment($fail_comment);
        }

        if (!empty($content['source'])) {
            if (!empty($transaction['source']['method'])) {
                $method = $transaction['source']['method'];
                $deposit->setPayMethod($method);
            }
            if (!empty($content['source']['cc_last4'])) {
                $card = $content['source']['cc_last4'];
                $deposit->setLastDigits($card);
            }
        }
        $account = $deposit->getAccount();
        $this->getPlatformHandler($account->getPlatform())->setDeposit($deposit);
        $em->persist($deposit);
        $em->flush();

        return $this->getJsonResponse(null, 'Deposit was updated');
    }

    /**
     * @Get("/payboutique-callback-decline", name="open_v1_payboutique_callback_decline")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/payment",
     *    authentication = false,
     *    description = "Callback for PayBoutique decline PS",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *        },
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="merchant_order", "dataType"="integer", "required"=true, "format"=".+", "description" = "set depositId"},
     *    },
     * )
     */
    public function callbackPayboutiqueDeclineAction()
    {
        $depositId = $this->getRequiredParam('merchant_order');
        $deposit = $this->getRepository(Deposit::class())->find($depositId);
        if (empty($deposit)) {
            return $this->getFailedJsonResponse(null, 'Deposit was not found', 404);
        }
        $deposit->setStatus(Deposit::STATUS_DECLINED);
        $deposit->setComment('Declined in Payboutique');
        $this->getEm()->flush();

        return $this->getJsonResponse(null, 'Deposit was declined');
    }


    /**
     * @Get("/netpay-callback", name="open_v1_netpay_callback")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/payment",
     *    authentication = false,
     *    description = "Callback for NetPay PS",
     *    statusCodes={
     *        200="Returned when successful",
     *        200="Returned when Deposit was already processed before",
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Invalid data",
     *    },
     *    parameters = {
     *        {"name"="Reply",     "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="TransID",   "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="Order",     "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="Amount",    "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="Currency",  "dataType"="string",  "required"=true, "format"=".+", },
     *        {"name"="signature", "dataType"="string",  "required"=true, "format"=".+", },
     *    },
     * )
     */
    public function callbackNetpayAction(Request $request)
    {
        $this->FULL_VIEW = true;
        // $PSP_NETPAY_HASH_KEY = 'Z8E2XCOPSK';
        $data = $request->query->all();

        $logger = $this->get('nax_crm.logger');
        $logger->createLog('deposits', 'Netpay callback', $request->getPathInfo(), $data);
        $logger->saveLog();

        if (empty($data['Reply']) || empty($data['TransID']) || empty($data['Order']) || empty($data['Amount']) || empty($data['Currency']) || empty($data['signature'])) {
            $this->getFailedJsonResponse(null, 'Invalid data', 500);
            return $this->redirectToClient(false);
        }
        /*if (base64_encode(hash('sha256', implode(array($data['Reply'], $data['TransID'], $data['Order'], $data['Amount'], $data['Currency'])).$PSP_NETPAY_HASH_KEY)) != $data['signature']) {
            $this->getFailedJsonResponse(null, 'Invalid signature', 500);
            return $this->redirectToClient(false);
        }*/

        $depositId = $data['Order'];
        /* @var $deposit Deposit */
        $deposit = $this->getRepository(Deposit::class())->find($depositId);
        if (!$deposit) {
            $this->getFailedJsonResponse(null, 'Deposit was not found', 400);
            return $this->redirectToClient(false);
        }

        /*$timeDiffMinutes = (new \DateTime())->diff($deposit->getCreated())->i;
        if ($timeDiffMinutes > Deposit::MAX_CONFIRMATION_TIME_IN_MINUTES) {
            $deposit->setStatus(Deposit::STATUS_EXPIRED);
            $deposit->setComment('The payment was not executed');
            $this->getEm()->persist($deposit);
            $this->getEm()->flush();
            $this->getFailedJsonResponse(null, 'The payment was not executed', 500);
            return $this->redirectToClient(false);
        }*/

        if (!$deposit || $deposit->getStatus() != Deposit::STATUS_PENDING) {
            $this->getFailedJsonResponse(null, 'Deposit was already processed before', 201);
            return $this->redirectToClient(false);
        }
        if ($data['Reply'] == '000') {
            return $this->success($deposit);
        }
        else/*if ($data['status'] == 'declined')*/ {
            $comment = 'ERROR #'.$data['Reply']. ': ' . $data['ReplyDesc'];
            return $this->decline($deposit, $comment);
        }
    }

    /**
     * @Get("/masterpay-cancel", name="open_v1_masterpay_cancel")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/payment",
     *    authentication = false,
     *    description = "Callback for masterpay PS",
     *    statusCodes={
     *        200="Returned when successful",
     *        200="Returned when Deposit was already processed before",
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Invalid data",
     *    },
     *    parameters = {
     *        {"name"="Reply",     "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="TransID",   "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="Order",     "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="Amount",    "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="Currency",  "dataType"="string",  "required"=true, "format"=".+", },
     *        {"name"="signature", "dataType"="string",  "required"=true, "format"=".+", },
     *    },
     * )
     */
    public function cancelMasterpayAction(Request $request)
    {
        $data = $request->query->all();

        $logger = $this->get('nax_crm.logger');
        $logger->createLog('deposits', 'Masterpay cancel', $request->getPathInfo(), $data);
        $logger->saveLog();

        if(empty($data['orderId'])){
            $this->getFailedJsonResponse(null, 'Undefined orderId', 400);
            return $this->redirectToClient(false);
        }

        $deposit = $this->getRepository(Deposit::class())->find($data['orderId']);
        if (!$deposit) {
            $this->getFailedJsonResponse(null, 'Deposit was not found', 400);
            return $this->redirectToClient(false);
        }

        if ($deposit->getStatus() != Deposit::STATUS_PENDING) {
            $this->getFailedJsonResponse(null, 'Deposit was already processed before', 201);
            return $this->redirectToClient(false);
        }

        if (!empty($data['ccNumber'])) {
            $card = substr($data['ccNumber'], -4);
            $deposit->setLastDigits($card);
        }
        $comment = 'ERROR #'.(!empty($data['resultCode'])? $data['resultCode'] : '0').' ';
        $comment.=(!empty($data['message'])? $data['message'] : 'Canceled');
        return $this->decline($deposit, $comment);
    }

    /**
     * @Get("/masterpay-return", name="open_v1_masterpay_return")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/payment",
     *    authentication = false,
     *    description = "Callback for masterpay PS",
     *    statusCodes={
     *        200="Returned when successful",
     *        200="Returned when Deposit was already processed before",
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Invalid data",
     *    },
     *    parameters = {
     *        {"name"="Reply",     "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="TransID",   "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="Order",     "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="Amount",    "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="Currency",  "dataType"="string",  "required"=true, "format"=".+", },
     *        {"name"="signature", "dataType"="string",  "required"=true, "format"=".+", },
     *    },
     * )
     */
    public function returnMasterpayAction(Request $request)
    {
        $data = $request->query->all();

        $logger = $this->get('nax_crm.logger');
        $logger->createLog('deposits', 'Masterpay return', $request->getPathInfo(), $data);
        $logger->saveLog();

        if(empty($data['orderId'])){
            $this->getFailedJsonResponse(null, 'Undefined orderId', 400);
            return $this->redirectToClient(false);
        }

        $deposit = $this->getRepository(Deposit::class())->find($data['orderId']);
        if (!$deposit) {
            $this->getFailedJsonResponse(null, 'Deposit was not found', 400);
            return $this->redirectToClient(false);
        }

        if ($deposit->getStatus() != Deposit::STATUS_PENDING) {
            $this->getFailedJsonResponse(null, 'Deposit was already processed before', 201);
            return $this->redirectToClient(false);
        }
        if (!empty($data['ccNumber'])) {
            $card = substr($data['ccNumber'], -4);
            $deposit->setLastDigits($card);
        }

        if (empty($data['resultCode']) || $data['resultCode']!=='1') {
            $comment = 'ERROR #'.(!empty($data['errorCode0'])?$data['errorCode0']:'0').' ';
            if(!empty($data['errorMessage0'])){
                $comment.=$data['errorMessage0'];
            }
            else if(!empty($data['message'])){
                $comment.=$data['message'];
            }
            else{
                $comment.='Unknown fail';
            }
            return $this->decline($deposit, $comment);
        }
        else {
            return $this->success($deposit);
        }
    }

    /**
     * @Put("/masterpay-callback", name="open_v1_masterpay_callback")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/payment",
     *    authentication = false,
     *    description = "Callback for masterpay PS",
     *    statusCodes={
     *        200="Returned when successful",
     *        200="Returned when Deposit was already processed before",
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Invalid data",
     *    },
     *    parameters = {
     *        {"name"="Reply",     "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="TransID",   "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="Order",     "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="Amount",    "dataType"="integer", "required"=true, "format"="\d+",},
     *        {"name"="Currency",  "dataType"="string",  "required"=true, "format"=".+", },
     *        {"name"="signature", "dataType"="string",  "required"=true, "format"=".+", },
     *    },
     * )
     */
    public function callbackMasterpayAction(Request $request)
    {
        $data = $request->query->all();

        $logger = $this->get('nax_crm.logger');
        $logger->createLog('deposits', 'Masterpay callback', $request->getPathInfo(), $data);
        $logger->saveLog();
        return $this->redirectToClient(false);
    }

    /**
     * @Post("/orangepay-callback", name="open_v1_orangepay_callback")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/payment",
     *    authentication = false,
     *    description = "Callback for OrangePay PS",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when Content is empty",
     *          "Returned when Content is not a valid json",
     *          "Returned when Can`t get transaction data",
     *          "Returned when Can`t get reference_id",
     *          "Returned when Invalid reference_id",
     *        },
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="", "dataType"="string", "required"=true, "format"=".+", "description" = "set JSON with information"},
     *    },
     * )
     */
    public function postCallbackOrangePayAction(Request $request)
    {
        /**
         * {
         * "event": "transaction.succeeded",
         * "data": {
         * "transaction": {
         * "id": "wch_vB9RO37N1aM7ZOdWmpnyxXXX",
         * "reference_id": "tr_15rpgM2eZvKYlo2C1lUyzOhy",
         * "livemode": false,
         * "status_id": "SUCCESSFUL",
         * "paid": true,
         * "amount": 200,
         * "amount_readable": "2.00",
         * "currency_id": "USD",
         * "description": "Example credit card charge",
         * "ip_address": "192.168.0.1",
         * "email": "domain.ltd@gmail.com",
         * "source": {
         * ...
         * },
         * "failure_code": null,
         * "failure_message": null,
         * "created_at": 1431688005,
         * "updated_at": 1431688005,
         * "valid_till": 1431688605
         * }
         * },
         * "status_code": 200,
         * }
         */

        $content = $request->getContent();

        $logger = $this->get('nax_crm.logger');
        $logger->createLog('deposits', 'OrangePay callback', $request->getPathInfo(), $content);
        $logger->saveLog();


        if (empty($content)) {
            $this->getFailedJsonResponse(null, 'Content is empty');
            return $this->redirectToClient(false);
        }

        if (!$content = json_decode($content, true)) {
            $this->getFailedJsonResponse(null, 'Content is not a valid json');
            return $this->redirectToClient(false);
        }
        if (!isset($content['data']['transaction'])) {
            $this->getFailedJsonResponse(null, 'Can`t get transaction data');
            return $this->redirectToClient(false);
        }

        $transaction = $content['data']['transaction'];

        if (!isset($transaction['reference_id'])) {
            $this->getFailedJsonResponse(null, 'Can`t get reference_id');
            return $this->redirectToClient(false);
        }
        if (!preg_match('#\d+_(\d+)_d_#', $transaction['reference_id'], $reference_id)) {
            $this->getFailedJsonResponse(null, 'Invalid reference_id');
            return $this->redirectToClient(false);
        }
        $depositId = $reference_id[1];
        /* @var $deposit Deposit */
        $deposit = $this->getRepository(Deposit::class())->find($depositId);
        if (!$deposit) {
            $this->getFailedJsonResponse(null, 'Deposit was not found', 404);
            return $this->redirectToClient(false);
        }

        /*if (Deposit::MAX_CONFIRMATION_TIME_IN_MINUTES) {
            $timeDiffMinutes = (new \DateTime())->diff($deposit->getCreated())->i;
            if ($timeDiffMinutes > Deposit::MAX_CONFIRMATION_TIME_IN_MINUTES) {
                $deposit->setStatus(Deposit::STATUS_EXPIRED);
                $deposit->setComment('The payment was not executed');
                $this->getEm()->flush();
                return new \Exception('The payment was not executed');
            }
        }*/

        if (!$deposit || $deposit->getStatus() != Deposit::STATUS_PENDING) {
            $this->getFailedJsonResponse(null, 'Deposit was already processed before');
            return $this->redirectToClient(false);
        }

        if (!empty($transaction['source'])) {
            /*if(!empty($transaction['source']['object'])){
                $method = $transaction['source']['object'];
                if(!empty($transaction['source']['cc_brand'])) {
                    $method.= $transaction['source']['cc_brand'];
                }
                $deposit->setPayMethod($method);
            }*/
            if (!empty($transaction['source']['cc_last4'])) {
                $card = $transaction['source']['cc_last4'];
                $deposit->setLastDigits($card);
            }
        }

        $sandbox = empty($transaction['livemode']);

        if ($content['event'] == 'transaction.succeeded' && $transaction['status_id'] == 'SUCCESSFUL' /*&& !empty($transaction['paid'])*/) {
            return $this->success($deposit, $sandbox);
        }
        else {
            $comment = 'Failed. '. (!empty($transaction['failure_message']? $transaction['failure_message'] : 'Unknown error'));
            return $this->decline($deposit, $comment, $sandbox);
        }
    }

    /**
     * @Post("/payboutique-callback", name="open_v1_payboutique_callback")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/payment",
     *    authentication = false,
     *    description = "Callback for PayBoutique PS",
     *    statusCodes={
     *        200="Returned when successful",
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="xml", "dataType"="string", "required"=true, "format"=".+", "description" = "set XML with information"},
     *    },
     * )
     */
    public function postCallbackPayboutiqueAction(Request $request)
    {
        $xmlContent = $request->request->get('xml');

        $logger = $this->get('nax_crm.logger');
        $logger->createLog('deposits', 'PB callback', $request->getPathInfo(), $xmlContent);
        $logger->saveLog();

        $xml = new \SimpleXMLElement($xmlContent);

        if (!isset($xml->Header) || !isset($xml->Body)) {
            throw new HttpException(400, 'Invalid XML content');
        }

        if ($xml->Header->Identity->Checksum->__toString() != $this->getPayboutiqueSignature(
                $xml->Header->Time->__toString(),
                $xml->Body->ReportedTransaction->ReferenceID->__toString())
        ) {
            throw new HttpException(403, 'Invalid checksum');
        }

        /* @var $em EntityManager */
        $em = $this->getEm();
        $depositId = $xml->Body->ReportedTransaction->OrderID->__toString();
        /* @var $deposit Deposit */
        $deposit = $this->getRepository(Deposit::class())->find($depositId);
        if (!$deposit) {
            throw new HttpException(404, 'Deposit was not found');
        }

        $timeDiffMinutes = (new \DateTime())->diff($deposit->getCreated())->i;
        if ($timeDiffMinutes > Deposit::MAX_CONFIRMATION_TIME_IN_MINUTES) {
            $deposit->setStatus(Deposit::STATUS_EXPIRED);
            $deposit->setComment('The payment was not executed');
            $em->flush();
            return new \Exception('The payment was not executed');
        }

        if (!$deposit || $deposit->getStatus() != Deposit::STATUS_PENDING) {
            return new \Exception('Deposit was already processed before');
        }
        $deposit->setStatus(Deposit::STATUS_PROCESSED);
        $account = $deposit->getAccount();
        $account->changeAmount($deposit->getAmount()); //FIXME: Fix from weber
        $em->flush();

        return $this->getJsonResponse(null, 'Deposit was processed');
    }

    /**
     * @Get("/payboutique-stub", name="open_v1_payboutique_stub")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/payment",
     *    authentication = false,
     *    description = "PayBoutique stub",
     *    statusCodes={
     *        200="Returned when successful",
     *        500="Returned when Something went wrong",
     *    },
     * )
     */
    public function getPayboutiqueStubAction()
    {
        return $this->getJsonResponse(null, 'Processing..');
    }

    private function getPayboutiqueSignature($time, $reference = '')
    {
        return strtoupper(hash('sha512', $this->getParameter('psp_payboutique_user_id_dev') .
            $this->getParameter('psp_payboutique_password_sha512_dev') . strtoupper($time) . $reference));
    }
}