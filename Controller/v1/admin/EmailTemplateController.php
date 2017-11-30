<?php

namespace NaxCrmBundle\Controller\v1\admin;

use Doctrine\ORM\EntityRepository;
use NaxCrmBundle\Entity\Account;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Entity\Document;
use NaxCrmBundle\Entity\Deposit;
use NaxCrmBundle\Entity\Withdrawal;
use NaxCrmBundle\Entity\EmailTemplate;
use NaxCrmBundle\Entity\PaymentSystem;
use NaxCrmBundle\Entity\UserLogItem;
use NaxCrmBundle\Modules\Email\TriggerFactory;
use NaxCrmBundle\Modules\Email\TriggerService;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use NaxCrmBundle\Controller\v1\BaseController;

class EmailTemplateController extends BaseController
{
    /**
     * @Get("/triggers")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/emailTemplate",
     *    authentication = true,
     *    description = "Get all available email triggers",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *        },
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     * )
     */
    public function getTriggers()
    {
        $triggerService = $this->get('nax_crm.trigger_service');
        return $this->getJsonResponse($triggerService->getAllParameters());
    }

    /**
     * @Get("/email-templates")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/emailTemplate",
     *    authentication = true,
     *    description = "Get all available email templates",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     * )
     * @Security("is_granted('R', 'email_template')")
     */
    public function getAction(Request $request)
    {
        $repo = $this->getRepository(EmailTemplate::class());
        //$preFilters['managerIds'] = [$this->getUser()->getId()];
        $result = $repo->findBy($request->query->all());
        return $this->getJsonResponse($result);
    }

    /**
     * @Get("/email-templates/{id}/try")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/emailTemplate",
     *    authentication = true,
     *    description = "Try to send selected email template to current manager email",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when event name is empty",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404={
     *          "Returned when emailTemplate was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select emailTemplate by id", "requirement"="\d+"},
     *    },
     * )
     * @param EmailTemplate $template
     * @ParamConverter("template", class="NaxCrmBundle:EmailTemplate")
     * @Security("is_granted('R', 'email_template')")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function tryAction(EmailTemplate $template)
    {
        if (empty($template)) {
            return $this->getFailedJsonResponse(null, 'EmailTemplate was not found', 404);
        }
        $triggerName = $template->getTriggerName();
        if (empty($triggerName)) {
            return $this->getFailedJsonResponse(null, 'Event name is empty');
        }
        $triggerService = $this->get('nax_crm.trigger_service');
        $triggerName = TriggerFactory::getClass($triggerName);
        $data = [];
        $reflection = new \ReflectionClass($triggerName);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PROTECTED);
        foreach ($properties as $property) {
            if (!$property->isStatic()) {
                $account = Account::createInst();
                $client = Client::createInst();
                $account->setClient($client);
                $client->addAccount($account);
                $client->setEmail($this->getUser()->getEmail());

                $value = null;
                switch ($property->getName()) {
                    case 'client':
                        $value = $client;
                        break;
                    case 'manager':
                        $value = Manager::createInst();
                        $value->setEmail($this->getUser()->getEmail());
                        break;
                    case 'withdrawal':
                        $value = Withdrawal::createInst();
                        $value->setClient($client);
                        $value->setAccount($account);
                        break;
                    case 'deposit':
                        $value = Deposit::createInst();
                        $value->setClient($client);
                        $value->setAccount($account);
                        $value->setPaymentSystem((PaymentSystem::createInst())->setPaymentSystemName('Paypal'));
                        break;
                    case 'document':
                        $value = Document::createInst();
                        $value->setClient(Client::createInst());
                        break;
                    case 'confirmLink':
                        $value = 'http://test.com/confirmLink';
                        break;
                    case 'password':
                        $value = 'test';
                        break;
                    default:
                        CONTINUE;
                }
                $data[$property->getName()] = $value;
            }
        }

        $em = $this->getEm();

        $trigger = TriggerFactory::createTrigger($triggerName, $this->getUser(), $this->getParameter('jwt_salt'), $triggerService->getUrls(), $em, $data);

        $message = $triggerService->createMessage($template, $trigger);

        $data['__test'] = true;
        $triggerService->sendEmails($template->getTriggerName(), $data, $template->getLangcode());

        return $this->getJsonResponse($message, 'Email has been successfully sent');
    }

    /**
     * @Get("/email-templates/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/emailTemplate",
     *    authentication = true,
     *    description = "Get selected email template",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404={
     *          "Returned when emailTemplate was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select emailTemplate by id", "requirement"="\d+"},
     *    },
     * )
     * @param EmailTemplate $bean
     * @ParamConverter("bean", class="NaxCrmBundle:EmailTemplate")
     * @Security("is_granted('R', 'email_template')")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getOne(EmailTemplate $bean)
    {
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'EmailTemplate was not found', 404);
        }

        return $this->getJsonResponse($bean);
    }

    /**
     * @Post("/email-templates")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/emailTemplate",
     *    authentication = true,
     *    description = "Create new email template",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when missing required parameter",
     *          "Returned on validation error",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="name",        "dataType"="string", "required"=true, "format"=".+", "description" = "set name for new email template."},
     *        {"name"="description", "dataType"="string", "required"=true, "format"=".+", "description" = "set description for new email template."},
     *    },
     * )
     * @Security("is_granted('C', 'email_template')")
     */
    public function createAction(Request $request)
    {
        $params = $request->request->all();
        $bean = EmailTemplate::createInst();
        $fields = [
            'name' => ['req'],
            'description' => ['req'],
        ];
        $this->saveEntity($bean, $fields, $params);

        $data = $bean->export();
        $this->createUserLog(UserLogItem::ACTION_TYPE_CREATE, UserLogItem::OBJ_TYPE_EMAIL_TEMPLATE, $data);
        return $this->getJsonResponse($data, 'EmailTemplate created');
    }

    /**
     * @Put("/email-templates/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/emailTemplate",
     *    authentication = true,
     *    description = "Update email template",
     *    statusCodes={
     *        200="Returned when successful",
     *        400={
     *          "Returned when hTML could not be empty for enabled template",
     *          "Returned when json could not be empty for enabled template",
     *          "Returned when trigger name could not be empty for enabled template",
     *          "Returned when langcode name could not be empty for enabled template",
     *          "Returned on validation errors",
     *        },
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404={
     *          "Returned when emailTemplate was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    parameters = {
     *        {"name"="name",        "dataType"="string",  "required"=false, "format"=".+",  "description" = "set description for email template."},
     *        {"name"="description", "dataType"="string",  "required"=false, "format"=".+",  "description" = "set description for email template."},
     *        {"name"="html",        "dataType"="string",  "required"=false, "format"=".+",  "description" = "set html code of email template."},
     *        {"name"="langcode",    "dataType"="string",  "required"=false, "format"=".+",  "description" = "set langcode for email template."},
     *        {"name"="json",        "dataType"="string",  "required"=false, "format"=".+",  "description" = "set json settings of email template."},
     *        {"name"="hours",       "dataType"="integer", "required"=false, "format"="\d+", "description" = "set execution time hours for email template."},
     *        {"name"="minutes",     "dataType"="integer", "required"=false, "format"="\d+", "description" = "set execution time minutes for email template."},
     *        {"name"="subject",     "dataType"="string",  "required"=false, "format"=".+",  "description" = "set email template subject"},
     *        {"name"="triggerName", "dataType"="string",  "required"=false, "format"=".+",  "description" = "set triggerName for email template."},
     *        {"name"="enabled",     "dataType"="boolean", "required"=false, "format"="\d",  "description" = "set is email template enabled"},
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select emailTemplate by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("bean", class="NaxCrmBundle:EmailTemplate")
     * @Security("is_granted('U', 'email_template')")
     */
    public function update(EmailTemplate $bean = null, Request $request)
    {
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'EmailTemplate was not found', 404);
        }
        $post = $request->request;
        $params = $post->all();
        $old_data = $bean->export();

        $enabled = $post->get('enabled', false);
        if ($enabled) {
            if (empty($bean->getHtml())) {
                return $this->getFailedJsonResponse('HTML could not be empty for enabled template');
            }
            if (empty($bean->getJson())) {
                return $this->getFailedJsonResponse('Json could not be empty for enabled template');
            }
            if (empty($bean->getTriggerName())) {
                return $this->getFailedJsonResponse('Trigger name could not be empty for enabled template');
            }
            if (empty($bean->getLangcode())) {
                return $this->getFailedJsonResponse('Langcode name could not be empty for enabled template');
            }
        }

        $h = $post->get('hours', '0');
        $m = $post->get('minutes', '0');
        $params['delay'] = "PT{$h}H{$m}M";
        $fields = [
            'langcode',
            'name',
            'html',
            'description',
            'json',
            'subject',
            'triggerName',
            'delay',
            'enabled',
        ];
        $this->saveEntity($bean, $fields, $params);

        $data = $bean->export();
        $diff = array_diff_assoc($data, $old_data);
        if(!empty($diff)){
            $diff['id'] = $bean->getId();
            $this->createUserLog(UserLogItem::ACTION_TYPE_UPDATE, UserLogItem::OBJ_TYPE_EMAIL_TEMPLATE, $diff);
        }
        return $this->getJsonResponse($data, 'EmailTemplate updated');
    }

    /**
     * @Delete("/email-templates/{id}")
     * @ApiDoc(
     *    views = {"default"},
     *    section = "admin",
     *    resource = "admin/emailTemplate",
     *    authentication = true,
     *    description = "Delete email template",
     *    statusCodes={
     *        200="Returned when successful",
     *        403={
     *          "Returned when the user is not authorized with JWT token",
     *          "Returned when the user have not proper acl rights",
     *        },
     *        404={
     *          "Returned when deposit was not found",
     *        },
     *        500="Returned when Something went wrong",
     *    },
     *    requirements = {
     *         {"name"="id", "dataType"="integer", "description"="select emailTemplate by id", "requirement"="\d+"},
     *    },
     * )
     * @ParamConverter("bean", class="NaxCrmBundle:EmailTemplate")
     * @Security("is_granted('D', 'email_template')")
     */
    public function deleteAction(EmailTemplate $bean = null)
    {
        if (empty($bean)) {
            return $this->getFailedJsonResponse(null, 'EmailTemplate was not found', 404);
        }
        $data = $bean->export();

        $this->removeEntity($bean);

        $this->createUserLog(UserLogItem::ACTION_TYPE_DELETE, UserLogItem::OBJ_TYPE_EMAIL_TEMPLATE, $data);
        return $this->getJsonResponse(null, 'EmailTemplate was deleted');
    }

}
