<?php

namespace NaxCrmBundle\Modules\Email\Triggers\Manager;

use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Modules\Email\Triggers\AbstractTrigger;
use Negotiation\Exception\InvalidArgument;

class BaseManager extends AbstractTrigger
{
    /**
     * @var Manager
     */
    protected $manager;

    public static $variables = [
        'MANAGER_ID',
        'MANAGER_NAME',
        'MANAGER_EMAIL',
    ];

    /**
     * BaseClient constructor.
     * @param Manager|array $manager
     */
    public function __construct($manager)
    {
        $this->manager = $this->findObject('manager', $manager);
        if (!($this->manager instanceof Manager)) {
            throw new InvalidArgument('Wrong type for Manager');
        }
    }

    public function getEmail() {
        return $this->manager->getEmail();
    }

    public function getValues() {
        return array_merge(parent::getValues(), [
            'MANAGER_ID' => $this->manager->getId(),
            'MANAGER_NAME' => $this->manager->getName(),
            'MANAGER_EMAIL' => $this->manager->getEmail(),
        ]);
    }
}