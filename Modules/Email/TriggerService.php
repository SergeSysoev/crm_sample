<?php

namespace NaxCrmBundle\Modules\Email;

use Doctrine\ORM\EntityManager;
use NaxCrmBundle\Entity\Message;
use NaxCrmBundle\Modules\Email\Triggers\AbstractTrigger;
use NaxCrmBundle\Modules\Email\Triggers\Client\BaseClient;
use NaxCrmBundle\Modules\Email\Triggers\Client\Confirmation;
use NaxCrmBundle\Modules\Email\Triggers\Client\Deposit;
use NaxCrmBundle\Modules\Email\Triggers\Client\DepositFailed;
use NaxCrmBundle\Modules\Email\Triggers\Client\DepositSuccess;
use NaxCrmBundle\Modules\Email\Triggers\Client\DocumentApproved;
use NaxCrmBundle\Modules\Email\Triggers\Client\DocumentDeclined;
use NaxCrmBundle\Modules\Email\Triggers\Client\DocumentNew;
use NaxCrmBundle\Modules\Email\Triggers\Client\ResetPassword;
use NaxCrmBundle\Modules\Email\Triggers\Client\WithdrawalApproved;
use NaxCrmBundle\Modules\Email\Triggers\Client\WithdrawalDeclined;
use NaxCrmBundle\Modules\Email\Triggers\Client\WithdrawalNew;
use NaxCrmBundle\Modules\Email\Triggers\Client\Registration;
use NaxCrmBundle\Modules\Email\Triggers\Client\Message as MessageTrigger;
use NaxCrmBundle\Modules\Email\Triggers\Manager\BaseManager;
use NaxCrmBundle\Entity\EmailTemplate;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\User\UserInterface;

class TriggerService
{

    /**
     * @var EntityManager
     */
    private $em;

    protected $user;

    protected $jwtSalt;

    protected $urls;

    protected $container;
    protected $mailer;

    /**
     * TriggerService constructor.
     * @param EntityManager $em
     * @param TokenStorage $tokenStorage
     * @param $container
     */
    public function __construct(EntityManager $em, TokenStorage $tokenStorage, $container)
    {
        $this->em = $em;
        if ($token = $tokenStorage->getToken()) {
            $this->user = $token->getUser();
        }
        $this->container = $container;
        $this->mailer = $this->container->getParameter('mailer');
        $this->urls = $this->container->getParameter('urls');
        $this->jwtSalt = $this->container->getParameter('jwt_salt');
    }

    public function setUser(UserInterface $user) {
        $this->user = $user;
    }

    public function getUrls()
    {
        return $this->urls;
    }

    public function getAllParameters() {
        return [
            'client' => [
                'defaultVariables'  => BaseClient::$variables,
                'triggersData' => [
                    'Confirmation'       => Confirmation::getAliasAndVariables(),
                    'Registration'       => Registration::getAliasAndVariables(),
                    'ResetPassword'      => ResetPassword::getAliasAndVariables(),
                    'Message'            => MessageTrigger::getAliasAndVariables(),
                    'Deposit'            => Deposit::getAliasAndVariables(),
                    'DepositFailed'      => DepositFailed::getAliasAndVariables(),
                    'DepositSuccess'     => DepositSuccess::getAliasAndVariables(),
                    'DocumentApproved'   => DocumentApproved::getAliasAndVariables(),
                    'DocumentDeclined'   => DocumentDeclined::getAliasAndVariables(),
                    'DocumentNew'        => DocumentNew::getAliasAndVariables(),
                    'WithdrawalApproved' => WithdrawalApproved::getAliasAndVariables(),
                    'WithdrawalDeclined' => WithdrawalDeclined::getAliasAndVariables(),
                    'WithdrawalNew'      => WithdrawalNew::getAliasAndVariables(),
                ],
            ],
            'manager' => [
                'defaultVariables'  => BaseManager::$variables,
                'triggersData'      => [],
            ],
        ];
    }

    public function createMessage(EmailTemplate $emailTemplate, AbstractTrigger $trigger, $copy_email = false)
    {
        $values = $trigger->getValues();
        $html = $emailTemplate->getHtml();
        // $pregex = '/%([^%]*)%/im';
        $pregex = '/%([A-Z_^%]+)%/im';
        preg_match_all($pregex, $html, $matches);
        foreach ($matches[1] as $index => $match) {
            if (isset($values[$match])) {
                $html = str_replace($matches[0][$index], $values[$match], $html);
            }
        }

        $subject = $emailTemplate->getSubject();
        preg_match_all($pregex, $subject, $matches);
        foreach ($matches[1] as $index => $match) {
            if (isset($values[$match])) {
                $subject = str_replace($matches[0][$index], $values[$match], $subject);
            }
        }

        $message = Message::createInst();
        $message->setEmail(($copy_email ?: $trigger->getEmail()));
        $message->setSubject($subject);
        $message->setHtml($html);
        $message->setSender($trigger->from);
        $message->setTemplate($emailTemplate);
        $delay = new \DateInterval($emailTemplate->getDelay());
        $message->setSendAt((new \DateTime())->add($delay));
        $message->setReplyTo($trigger->getReplyTo());

        if(!empty($trigger->getAttachment())){
            $message->setAttachment($trigger->getAttachment());
        }
        return $message;
    }

    public function getCopyEmails($triggerName){
        $copy_emails = [];
        return $copy_emails;
    }

    public function sendEmails($triggerName, array $data = [], $langCode = EmailTemplate::DEFAULT_LANGCODE)
    {
        $messages = 0;
        $copy_emails = $this->getCopyEmails($triggerName);
        $trigger = TriggerFactory::createTrigger($triggerName, $this->user, $this->jwtSalt, $this->urls, $this->em, $data, $this->mailer);
        $reflect = new \ReflectionClass($trigger);
        $shortTriggerName = $reflect->getShortName();

        $template_filter = [
            'triggerName' => $shortTriggerName,
            'langcode' => $langCode,
            'enabled' => 1,
        ];
        if(!empty($data['__test'])){
            unset($template_filter['enabled']);
        }

        /* @var EmailTemplate[] $templates */
        $templates = $this->em->getRepository(EmailTemplate::class())->findBy($template_filter);
        // \NaxCrmBundle\Debug::$messages[]=[$templates, $template_filter];
        if (!$templates && $langCode != EmailTemplate::DEFAULT_LANGCODE) {
            $template_filter['langcode'] = EmailTemplate::DEFAULT_LANGCODE;
            $templates = $this->em->getRepository(EmailTemplate::class())->findBy($template_filter);
        }
        foreach ($templates as $emailTemplate) {
            $message = $this->createMessage($emailTemplate, $trigger);
            $this->em->persist($message);

            foreach ($copy_emails as $copy_email) {
                $message = $this->createMessage($emailTemplate, $trigger, $copy_email);
                $this->em->persist($message);
            }
            $messages++;
        }
        $this->em->flush();
        return [$triggerName, $messages];
    }
}