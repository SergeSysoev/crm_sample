<?php

namespace NaxCrmBundle\Modules\Email\Triggers;

use Doctrine\ORM\EntityManager;
use NaxCrmBundle\Entity\BaseEntity;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Manager;

abstract class AbstractTrigger
{
    /**
     * @var string
     */
    protected static $alias = '';

    /**
     * @var string
     */
    public $from = 'support@naxtrader.com';

    /**
     * @var string
     */
    protected $replyTo = '';

    /**
     * @var Manager|Client
     */
    protected $user;

    /**
     * @var string
     */
    protected $attachment;

    /**
     * @var string
     */
    protected $jwtSalt;

    /**
     * @var array
     */
    protected $urls;

    /**
     * @param EntityManager $em
     */
    public function setEm($em)
    {
        $this->em = $em;
    }

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var array
     */
    public static $variables = [
        'FROM',
        'REPLY_TO'
    ];

    public static function getVariables() {
        return static::$variables;
    }

    public static function getAliasAndVariables() {
        return [
            'alias' => static::$alias,
            'variables' => static::getVariables(),
        ];
    }

    public function getValues(){
        return [
            'FROM' => $this->from,
            'REPLY_TO' => $this->getReplyTo(),
        ];
    }

    public function getReplyTo(){
        return $this->replyTo ?: $this->from;
    }

    /**
     * @param string $replyTo
     */
    public function setReplyTo(string $replyTo)
    {
        $this->replyTo = $replyTo;
    }


    /**
     * Email send to
     * @return string
     */
    public abstract function getEmail();

    /**
     * @return Manager|Client
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $jwtSalt
     */
    public function setJwtSalt($jwtSalt)
    {
        $this->jwtSalt = $jwtSalt;
    }

    /**
     * @return string
     */
    public function getJwtSalt()
    {
        return $this->jwtSalt;
    }

    /**
     * @param array $urls
     */
    public function setUrls($urls)
    {
        $this->urls = $urls;
    }

    /**
     * @param Manager|Client $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @param string $attachment
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * @param string
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    public function findObject($name, $stack) {
        if (is_array($stack)) {
            if (array_key_exists($name, $stack)) {
                return $stack[$name];
            }
            return reset($stack);
        }
        return $stack;
    }

    public static function class($triger_category='Client', $brand = null){
        $class_path = 'Modules\\Email\\Triggers\\'.$triger_category;
        $class_name = BaseEntity::class($brand, get_called_class(), $class_path);
        return $class_name;
    }
}
