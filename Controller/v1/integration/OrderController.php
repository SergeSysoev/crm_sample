<?php

namespace NaxCrmBundle\Controller\v1\integration;

use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Order;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use NaxCrmBundle\Controller\v1\BaseController;

class OrderController extends BaseController
{
    /**
     * @Post("/client/{clientId}/order/{ext_id}", name="v1_integrate_order")
     * @ParamConverter("client", class="NaxCrmBundle\Entity\Client", options={"id" = "clientId"})
     * @Security("has_role('ROLE_MANAGER') and is_granted('U', 'client')")
     */
    public function integrationOrderAction(Request $request, Client $client = null, $ext_id = 0)
    {
        return $this->getJsonResponse(null, 'Ignore');// TURN CODE OFF

        /*if (empty($client)) {
            return $this->getJsonResponse(null, 'Client was not found', 404);
        }
        $em = $this->getEm();

        $create_new = false;

        if (!$order = $this->getRepository(Order::class())->findOneBy(['extId' => $ext_id])) {
            $order = Order::createInst();
            $create_new = true;
        }

        $type = $this->getRequiredParam('type');
        if (!in_array($type, [Order::TYPE_CALL, Order::TYPE_PUT])) {
            return $this->getFailedJsonResponse(null, 'Invalid order type');
        }
        $currency = $request->request->get('currency');
        if (!$currency) {
            $accountId = $this->getRequiredParam('accountId');
            $account = $this->getRepository(Account::class())->find($accountId);
        } else {
            $account = $this->getClientService($client)->getAccount($currency);
        }
        if ((!$account instanceof Account)) {
            return $this->getFailedJsonResponse(null, "Client has no account for {$currency}. Please make deposit");
        }

        $tsOpen = $this->getRequiredParam('timeOpen');
        if (strlen($tsOpen) >= 14) {
            return $this->getFailedJsonResponse($tsOpen, 'Invalid time open, need timestamp with milliseconds');
        }
        $tsExpiration = $this->getRequiredParam('timeExpiration');
        if (strlen($tsExpiration) >= 14) {
            return $this->getFailedJsonResponse($tsExpiration, 'Invalid time expiration, need timestamp with milliseconds');
        }
        $tsOpenDate = substr($tsOpen, 0, 10);
        $tsOpenMilli = substr($tsOpen, 10) ?: 0;
        $tsExpirationDate = substr($tsOpen, 0, 10);
        $tsExpirationMilli = substr($tsOpen, 10) ?: 0;

        $order->setAmount($this->getRequiredParam('amount'));
        $order->setExtId($this->getRequiredParam('login'));
        $order->setPayout($this->getRequiredParam('payout'));
        $order->setPayoutRate($this->getRequiredParam('payoutRate'));
        $order->setState($this->getRequiredParam('state'));
        $order->setTimeOpen((new \DateTime())->setTimestamp($tsOpenDate));
        $order->setTimeOpenMillisec($tsOpenMilli);
        $order->setTimeExpiration((new \DateTime())->setTimestamp($tsExpirationDate));
        $order->setTimeExpirationMillisec($tsExpirationMilli);
        $order->setPriceOpen($this->getRequiredParam('priceOpen'));
        $order->setPriceStrike($this->getRequiredParam('priceStrike'));
        $order->setSymbol($this->getRequiredParam('symbol'));
        $order->setType($type);
        $order->setClient($client);
        $order->setAccount($account);
        $order->setInfo($request->request->get('info', ''));
        $em->persist($order);
        $em->flush();

        return $this->getJsonResponse($order->export(), 'Order has been successfully ' . ($create_new ? 'created' : 'updated'));*/
    }
}
