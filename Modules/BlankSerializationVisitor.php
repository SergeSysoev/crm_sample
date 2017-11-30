<?php

namespace NaxCrmBundle\Modules;

use JMS\Serializer\Context;
use JMS\Serializer\JsonSerializationVisitor;

class BlankSerializationVisitor extends JsonSerializationVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visitNull($data, array $type, Context $context)
    {
        return '';
    }
}