<?php

namespace NaxCrmBundle\Modules;

use Doctrine\ORM\EntityManager;
use NaxCrmBundle\Entity\Client;
use NaxCrmBundle\Entity\Country;
use NaxCrmBundle\Entity\Department;
use NaxCrmBundle\Entity\Event;
use NaxCrmBundle\Entity\Lead;
use NaxCrmBundle\Entity\LeadUpload;
use NaxCrmBundle\Entity\Manager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LeadLoader
{
    /***
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var Factory
     */
    protected $phpExcel;

    /**
     * @var string
     */
    protected $failedLeads;

    /**
     * @var array
     */
    protected $managerLeads = [];

    /**
     * @var int
     */
    protected $currentManagerIndex = 0;

    /**
     * @var integer
    */
    protected $totalRows = 0;

    /**
     * @var array
    */
    protected $numberOfFailedLeads = [
        'other' => 0,
        'phone_duplicate' => 0,
        'email_duplicate' => 0,
    ];

    protected $countryRepo = null;
    protected $countryMappings = null;
    protected $countryMappingsName = false;

    protected $duplicates =[
        'email' => [],
        'phone' => [],
    ];

    protected $mimes_csv = [
        'text/csv',
        'text/plain',
    ];

    protected $mimes_xls = [
        'application/vnd.ms-excel',
        'application/vnd.ms-office',
        'application/wps-office.xlsx',
        'application/vnd.openxmlformats',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    protected $mimes_detect = [
        'application/octet-stream',
        'application/download',
    ];

    protected $available_delimiters = [
        ',',
        ';',
        ' ',
        "\t",
    ];

    public function __construct(EntityManager $entityManager, ValidatorInterface $validator, $phpExcel)
    {
        $this->em        = $entityManager;
        $this->validator = $validator;
        $this->phpExcel  = $phpExcel;

    }

    public function getFirstRow(UploadedFile $file, $delimiter)
    {
        $mimeType = $file->getClientMimeType();
        if (in_array($mimeType, $this->mimes_detect)) {
            $mimeType = $this->tryDetectMime($file);
        }

        if (in_array($mimeType, $this->mimes_csv)) {
            return $this->getFirstRowCsv($file, $delimiter);
        }
        elseif (in_array($mimeType, $this->mimes_xls)) {
            return $this->getFirstRowExcel($file);
        }
        else {
            throw new HttpException(400, 'You should use CSV or Excel files for loading, not '.$mimeType);
        }
    }

    public function loadLeads(UploadedFile $file, $fields, $delimiter, $manager = null, $managerToAssign = null, $departmentToAssign = null, $assignToAdmin = false)
    {
        if($assignToAdmin){
            $managerToAssign = $this->em->getReference(Manager::class(), 1);
        }
        /* @var Manager $managerToAssign */
        /* @var Manager $manager */
        if (is_null($departmentToAssign)) {
            if (is_null($managerToAssign)) {
                $department = $manager->getDepartment();
            }
            else {
                $department = $managerToAssign->getDepartment();
            }
        }
        else {
            $department = $departmentToAssign;
        }

        if (is_null($managerToAssign)) {
            $this->loadManagersLeadCount($department);
        }
        else {
            $this->managerLeads[] = [
                'managerId'             => $managerToAssign->getId(),
                'departmentId'          => $department->getId(),
                'existingLeadsCount'    => 0,
                'uploadedLeads'         => 0
            ];
        }

        foreach ($fields as $k => $v) {
            $fields[$k] = trim($v);
        }

        $leadUpload = LeadUpload::createInst();
        $leadUpload->setManager($manager);

        $mimeType = $file->getClientMimeType();
        if (in_array($mimeType, $this->mimes_detect)) {
            $mimeType = $this->tryDetectMime($file);
        }

        if (in_array($mimeType, $this->mimes_csv)) {
            $numberOfUploadedLeads = $this->loadLeadsCsv($file, $fields, $delimiter, $department, $leadUpload);
        }
        elseif (in_array($mimeType, $this->mimes_xls)) {
            $numberOfUploadedLeads = $this->loadLeadsExcel($file, $fields, $department, $leadUpload);
        }
        else {
            throw new HttpException(400, 'You should use CSV or Excel files for loading, not '.$mimeType);
        }
        $leadUpload->setNumberOfUploadedLeads($numberOfUploadedLeads);
        $this->em->persist($leadUpload);
        $this->em->flush();

        /*foreach ($this->managerLeads as $managersLeadData) {
            if ($managersLeadData['uploadedLeads'] > 0) {
                $event = Event::createInst();
                $event->setManager($this->em->getReference(Manager::class(), $managersLeadData['managerId']));
                $event->setMessage($managersLeadData['uploadedLeads'] . " leads was uploaded to you");
                $event->setTitle('new leads upload');
                $event->setObjectType(Event::OBJ_TYPE_LEAD);
                $event->setObjectId($leadUpload->getId());
                $this->em->persist($event);
            }
        }*/
        $this->em->flush();

        return $leadUpload;
    }

    protected function getFirstRowCsv(UploadedFile $file, $delimiter)
    {
        if(!in_array($delimiter, $this->available_delimiters)){
            throw new HttpException(400, 'Delimiter is not supported '.$delimiter);
        }
        if($this->IsBinary($file->getPathname())){
            throw new HttpException(400, 'Wrong file. CSV can`t be binary');
        }
        $fh = fopen($file->getPathname(), 'r');
        $firstRow = fgetcsv($fh, 0, $delimiter);
        fclose($fh);
        return $firstRow;
    }

    protected function getFirstRowExcel(UploadedFile $file)
    {
        $phpExcelObject = $this->phpExcel->createPHPExcelObject($file->getPathname());
        $values = $phpExcelObject->getActiveSheet()
            ->rangeToArray(
                'A' . 1 .
                ':' .
                $phpExcelObject->getActiveSheet()->getHighestDataColumn() . 1
            )[0];
        return $values;
    }

    protected function preProcessValues($values, $indexes)
    {
        $values[$indexes['phone']] = preg_replace("/[^0-9]/", '', $values[$indexes['phone']]);
        $values[$indexes['country']] = strtolower($values[$indexes['country']]);

        return $values;
    }

    protected function  setLeadValues($values, $indexes, $params){
        $lead = Lead::createInst();

        $lead->setFirstname($values[$indexes['firstname']]);
        $lead->setLastname($values[$indexes['lastname']]);
        $lead->setEmail($values[$indexes['email']]);
        $lead->setPhone($values[$indexes['phone']]);
        $lead->setUpload($params['leadUpload']);
        $lead->setDepartment($params['department']);
        $lead->setCountry($this->em->getReference(Country::class(), $params['countryId']));
        if (!empty($indexes['langcode'])) {
            $lead->setLangcode($values[$indexes['langcode']]);
        }
        elseif(!empty($lead->getCountry())){
             $lead->setLangcode($lead->getCountry()->getLangCode());
        }
        if (!empty($indexes['birthday'])) {
            $lead->setBirthday(\DateTime::createFromFormat('Y-m-d', $values[$indexes['birthday']]));
        }
        return $lead;
    }

    protected function getCountryId($values, $indexes){
        if (array_key_exists($values[$indexes['country']], $this->countryMappings)) {
            return $this->countryMappings[$values[$indexes['country']]];
        }
        else{
            if(!$this->countryMappingsName){
                $this->countryMappingsName = $this->countryRepo->getAllCountriesByAllNames();
            }
            if (array_key_exists($values[$indexes['country']], $this->countryMappingsName)) {
                return $this->countryMappingsName[$values[$indexes['country']]];
            }
        }
        $this->numberOfFailedLeads['other']++;
        $this->failedLeads .= implode(',', $values) . " - missing country(code/name) mapping[{$values[$indexes['country']]}]" . PHP_EOL;
        return false;
    }

    protected function validateLead($lead, $values){
        //validation
        if ($this->em->getFilters()->isEnabled('softdeleteable')){
            $this->em->getFilters()->disable('softdeleteable');
        }
        $clientRepo = $this->em->getRepository(Client::class());
        $clientByPhone = $clientRepo->findOneBy(['phone' => $lead->getPhone()]);
        if ($clientByPhone) {
            $this->numberOfFailedLeads['phone_duplicate']++;
            $this->failedLeads .= implode(',', $values) . ' - client with that phone already exists' . PHP_EOL;
            return false;
        }

        $clientByEmail = $clientRepo->findOneBy(['email' => $lead->getEmail()]);
        if($clientByEmail) {
            $this->numberOfFailedLeads['email_duplicate']++;
            $this->failedLeads .= implode(',', $values) . ' - client with that email already exists' . PHP_EOL;
            return false;
        }

        $errors = $this->validator->validate($lead);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            $this->numberOfFailedLeads['other']++;
            $this->failedLeads .= implode(',', $values) . ' - ' . implode(' ', $errorMessages) . PHP_EOL;
            return false;
        }

        if(in_array($lead->getEmail(), $this->duplicates['email'])){
            $this->numberOfFailedLeads['other']++;
            $this->failedLeads .= implode(',', $values) . ' - lead with that email already exists at this file' . PHP_EOL;
            return false;
        }
        if(in_array($lead->getPhone(), $this->duplicates['phone'])){
            $this->numberOfFailedLeads['other']++;
            $this->failedLeads .= implode(',', $values) . ' - lead with that phone already exists at this file' . PHP_EOL;
            return false;
        }

        $this->duplicates['email'][]= $lead->getEmail();
        $this->duplicates['phone'][]= $lead->getPhone();
        return true;
    }

    protected function getManager(){
        $managerIndex = $this->getNextManagerIndex();
        $managerId = $this->managerLeads[$managerIndex]['managerId'];
        $this->managerLeads[$managerIndex]['uploadedLeads']++;
        $manager = $this->em->getReference(Manager::class(), $managerId);
        $this->managerLeads[$managerIndex]['existingLeadsCount']++;
        return $manager;
    }

    protected function loadLeadsCsv(UploadedFile $file, $fields, $delimiter, $department, $leadUpload)
    {
        if(!in_array($delimiter, $this->available_delimiters)){
            throw new HttpException(400, 'Delimiter is not supported '.$delimiter);
        }
        if($this->IsBinary($file->getPathname())){
            throw new HttpException(400, 'Wrong file. CSV can`t be binary');
        }
        $csvString = file_get_contents($file->getPathname());
        $encoding = mb_detect_encoding($csvString, "auto" );
        $csvString = iconv($encoding, 'UTF-8//IGNORE', $csvString);

        $numberOfUploadedLeads = 0;
        $indexes = [];
        $this->failedLeads = '';

        $this->countryRepo = $this->em->getRepository(Country::class());
        $this->countryMappings = $this->countryRepo->getAllCountriesByAllCodes();

        foreach (str_getcsv($csvString, "\n") as $line => $row) {
            $this->totalRows++;
            $values = str_getcsv($row, $delimiter);

            //fill indexes
            if ($line == 0) {
                foreach ($values as $index => &$value) {
                    $value = trim($value);
                    if (in_array($value, $fields)) {
                        $indexes[array_search($value, $fields)] = $index;
                    } else {
                        // throw new MissingMandatoryParametersException("Missing field definition for first row value $value");
                    }
                }
            }

            if ((count($values) < count($indexes))) {
                $this->numberOfFailedLeads['other']++;
                $this->failedLeads .= implode(',', $values) . ' - invalid fields count' . PHP_EOL;
                continue;
            }

            $values = $this->preProcessValues($values, $indexes);

            $countryId = $this->getCountryId($values, $indexes);
            if(!$countryId){
                continue;
            }

            $lead = $this->setLeadValues($values, $indexes, [
                'department' => $department,
                'leadUpload' => $leadUpload,
                'countryId' => $countryId,
            ]);

            if(!$this->validateLead($lead, $values)){
                continue;
            }
            //validation ok
            $lead->setManager($this->getManager());
            $this->em->persist($lead);
            $numberOfUploadedLeads++;
        }

        return $numberOfUploadedLeads;
    }

    protected function loadLeadsExcel(UploadedFile $file, $fields, $department, $leadUpload)
    {
        $phpExcelObject = $this->phpExcel->createPHPExcelObject($file->getPathname());
        $activeSheet = $phpExcelObject->getActiveSheet();

        $numberOfUploadedLeads = 0;
        $indexes = [];
        $this->failedLeads = '';

        $this->countryRepo = $this->em->getRepository(Country::class());
        $this->countryMappings = $this->countryRepo->getAllCountriesByAllCodes();

        $highestColumn = $activeSheet->getHighestDataColumn();
        for ($i = 1; $i <= $activeSheet->getHighestDataRow(); $i++) {
            $this->totalRows++;
            $values = $phpExcelObject->getActiveSheet()
                ->rangeToArray(
                    'A' . $i .
                    ':' .
                    $highestColumn . $i, ''
                )[0];

            //fill indexes
            if ($i == 1) {
                foreach ($values as $index => &$value) {
                    $value = trim($value);
                    if (in_array($value, $fields)) {
                        $indexes[array_search($value, $fields)] = $index;
                    } else {
                        // throw new MissingMandatoryParametersException("Missing field definition for first row value $value");
                    }
                }
            }

            if ((count($values) < count($indexes))) {
                $this->numberOfFailedLeads['other']++;
                $this->failedLeads .= implode(',', $values) . ' - invalid fields count' . PHP_EOL;
                continue;
            }

            $values = $this->preProcessValues($values, $indexes);

            $countryId = $this->getCountryId($values, $indexes);
            if(!$countryId){
                continue;
            }

            $lead = $this->setLeadValues($values, $indexes, [
                'department' => $department,
                'leadUpload' => $leadUpload,
                'countryId' => $countryId,
            ]);

            if(!$this->validateLead($lead, $values)){
                continue;
            }
            //validation - ok
            $lead->setManager($this->getManager());
            $this->em->persist($lead);
            $numberOfUploadedLeads++;
        }

        return $numberOfUploadedLeads;
    }

    public function getNumberOfFailedLeads()
    {
        return $this->numberOfFailedLeads;
    }

    public function getTotalRows()
    {
        return $this->totalRows;
    }

    public function getFailedLeads()
    {
        return trim($this->failedLeads);
    }

    protected function loadManagersLeadCount(Department $department)
    {
        $repo = $this->em->getRepository(Manager::class());
        $qb = $repo->createQueryBuilder('m');
        $qb
            ->select(
                'm.id managerId',
                'd.id departmentId',
                'COUNT(l) existingLeadsCount',
                '0 uploadedLeads'
            )
            ->leftJoin('m.department', 'd')
            ->leftJoin('m.leads', 'l')
            ->where('d.lft >= :lft')
            ->andWhere('d.rgt <= :rgt')
            ->andWhere('d.root = :root')
            ->groupBy('m')
            ->orderBy('existingLeadsCount')
            ->setParameters([
                ':lft' => $department->getLft(),
                ':rgt' => $department->getRgt(),
                ':root' => $department->getRoot()
            ]);
        $this->managerLeads = $qb->getQuery()->getResult();
    }

    protected function getNextManagerIndex()
    {
        $nextManagerIndex = $this->currentManagerIndex + 1;
        //if it is last manager - return to start
        if (!isset($this->managerLeads[$nextManagerIndex])) {
            $this->currentManagerIndex = 0;
        }
        else {
            $currentManagerLeadCount = $this->managerLeads[$this->currentManagerIndex]['existingLeadsCount'];
            $nextManagerLeadCount = $this->managerLeads[$nextManagerIndex]['existingLeadsCount'];
            if ($currentManagerLeadCount <= $nextManagerLeadCount) {
                $this->currentManagerIndex = 0;
            }
            elseif ($currentManagerLeadCount > $nextManagerLeadCount) {
                $this->currentManagerIndex = $nextManagerIndex;
            }
        }
        return $this->currentManagerIndex;
    }

    protected function tryDetectMime(UploadedFile $file)
    {
        $mime = mime_content_type($file->getPathname());

        return $mime;
    }

    protected function IsBinary($file)
    {
        $fh  = fopen($file, "r");
        $blk = fread($fh, 512);
        fclose($fh);
        clearstatcache();

        return preg_match('~[^\x20-\x7E\t\r\n]~', $blk) > 0;
    }
}