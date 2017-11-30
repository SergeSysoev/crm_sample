<?php

namespace NaxCrmBundle\Modules\Email\Triggers\Client;

class DocumentDeclined extends DocumentNew
{
    protected static $alias = 'Document declined';

    public static function getVariables() {
        return array_merge(parent::$variables, [
            'DOCUMENT_DECLINE_REASON',
        ]);
    }

    public function getValues() {
        return array_merge(parent::getValues(), [
            'DOCUMENT_DECLINE_REASON' => $this->document->getComment()
        ]);
    }
}