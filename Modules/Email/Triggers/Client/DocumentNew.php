<?php

namespace NaxCrmBundle\Modules\Email\Triggers\Client;

use NaxCrmBundle\Entity\Document;
use Negotiation\Exception\InvalidArgument;

class DocumentNew extends BaseClient
{
    protected static $alias = 'New document uploaded';

    /**
     * @var Document
     */
    protected $document;

    public static $variables = [
        //тип документа, имя файла, дата загрузки, дата изменения статуса, статус
        'DOCUMENT_TYPE',
        'DOCUMENT_NAME',
        'DOCUMENT_DATE',
        'DOCUMENT_STATUS',
        'CHANGE_STATUS_DATE',
    ];

    public function getValues() {
        return array_merge(parent::getValues(), [
            'DOCUMENT_TYPE' => $this->document->getDocumentType(),
            'DOCUMENT_NAME' => $this->document->getPath(),
            'DOCUMENT_DATE' => $this->document->getTime(),
            'DOCUMENT_STATUS' => $this->document->getStatus(),
            //todo updated_at
            'CHANGE_STATUS_DATE' => '???',
        ]);
    }

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->document= $this->findObject('document', $data);
        if (!($this->document instanceof Document)) {
            throw new InvalidArgument('Document wrong type');
        }
    }
}