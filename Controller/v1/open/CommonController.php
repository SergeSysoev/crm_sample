<?php
namespace NaxCrmBundle\Controller\v1\open;


use NaxCrmBundle\Entity\AclResources;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Country;
use NaxCrmBundle\Entity\Department;
use NaxCrmBundle\Entity\Deposit;
use NaxCrmBundle\Entity\Document;
use NaxCrmBundle\Entity\DocumentType;
use NaxCrmBundle\Entity\Language;
use NaxCrmBundle\Entity\PaymentSystem;
use NaxCrmBundle\Entity\SaleStatus;
use NaxCrmBundle\Entity\Withdrawal;
use FOS\RestBundle\Controller\Annotations\Get;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class CommonController extends BaseController
{
    /**
     * @Get("/common-resources", name="open_v1_get_common_resources")
     * @ApiDoc(
     *    section = "open",
     *    resource = "open/common",
     *    authentication = false,
     *    description = "Get common resources list",
     *    statusCodes={
     *        200="Returned when successful",
     *        500="Returned when Something went wrong",
     *    },
     * )
     */
    public function getCommonResourcesAction()
    {
        $res = $this->getCommonResources();
        return $this->getJsonResponse($res);
    }

    public function getCommonResources()
    {
        $res = [];
        // $h = $this->getPlatformHandler();
        $res['build_version'] = (int)file_get_contents($this->getParameter('kernel.root_dir').'/../build.ver');

        $res['defaultPermissions'] = $this->genEnum(AclResources::get('permissionTitles'));
        $res['aclResources'] = $this->genEnum(AclResources::get('resourceNames'));

        // $res['currency'] = $h->getSymbols();
        // $res['currency'] = $this->getRepository(Country::class())->getDistinctCurrency();
        $res['countries'] = $this->getRepository(Country::class())->findAll(['name' => 'ASC']);
        array_unshift($res['countries'], [
            'id' => '',
            'code' => '',
            'name' => 'no_select',
        ]);
        $res['languages'] = $this->getRepository(Language::class())->findBy([], ['name' => 'ASC']);
        // $res['saleStatuses'] = $this->getRepository(SaleStatus::class())->findAll();
        $res['departments'] = $this->genEnum($this->getIdNamesArray(Department::class()));
        $res['documentTypes'] = $this->getRepository(DocumentType::class())->findAll();
        $res['clientStatuses'] = $this->genEnum(Client::get('Statuses'), 'statusName');
        $res['clientGenders'] = $this->genEnum(Client::get('Genders'));
        $res['paymentSystems'] = $this->getRepository(PaymentSystem::class())->findBy([
            'status' => 1,
        ]);
        $res['documentStatuses'] = Document::getStatuses();
        $res['withdrawalStatuses'] = $this->genEnum(Withdrawal::get('Statuses'));
        $res['depositStatuses'] = $this->genEnum(Deposit::get('Statuses'));
        $res['depositCurrencies'] = $this->getRepository(Deposit::class())->getCurrencies();

        return $res;
    }
}