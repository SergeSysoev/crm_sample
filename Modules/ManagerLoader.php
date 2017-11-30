<?php

namespace NaxCrmBundle\Modules;

use Doctrine\ORM\EntityManager;
use NaxCrmBundle\Entity\Country;
use NaxCrmBundle\Entity\Manager;
use NaxCrmBundle\Repository\CountryRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Liuggio\ExcelBundle\Factory;

class ManagerLoader
{
    private $em;

    private $validator;

    /**
     * @var Factory
     */
    private $phpExcel;

    /**
     * @var UserPasswordEncoder
     */
    private $passwordEncoder;

    public function __construct(EntityManager $entityManager, ValidatorInterface $validator, $phpExcel, UserPasswordEncoder $passwordEncoder) {
        $this->em               = $entityManager;
        $this->validator        = $validator;
        $this->phpExcel         = $phpExcel;
        $this->passwordEncoder  = $passwordEncoder;
    }

    public function getFirstRow(UploadedFile $file, $delimiter) {
        $mimeType = $file->getClientMimeType();
        if ($mimeType == 'text/csv') {
            return $this->getFirstRowCsv($file, $delimiter);
        } elseif (in_array($mimeType, [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ])) {
            return $this->getFirstRowExcel($file);
        } else {
            throw new \InvalidArgumentException('You should use CSV or Excel files for loading');
        }
    }

    public function loadManagers(UploadedFile $file, $fields, $delimiter, $department, $password = '') {
        $mimeType = $file->getClientMimeType();
        if ($mimeType == 'text/csv') {
            return $this->loadManagersCsv($file, $fields, $delimiter, $department, $password);
        } elseif (in_array($mimeType, [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ])) {
            return $this->loadManagersExcel($file, $fields, $department, $password);
        } else {
            throw new \InvalidArgumentException('You should use CSV or Excel files for loading');
        }
    }

    private function getFirstRowCsv(UploadedFile $file, $delimiter) {
        $csvString = file_get_contents($file->getPathname());
        $rows = str_getcsv($csvString, "\n");
        $firstRow = reset($rows);
        $values = str_getcsv($firstRow, $delimiter);
        return $values;
    }

    private function getFirstRowExcel(UploadedFile $file) {
        $phpExcelObject = $this->phpExcel->createPHPExcelObject($file->getPathname());
        $values = $phpExcelObject->getActiveSheet()
            ->rangeToArray(
                'A' . 1 .
                ':' .
                $phpExcelObject->getActiveSheet()->getHighestDataColumn() . 1
            )[0];
        return $values;
    }

    private function loadManagersCsv(UploadedFile $file, $fields, $delimiter, $department, $password) {
        $csvString = file_get_contents($file->getPathname());

        $numberOfUploadedManagers = 0;
        $indexes = [];

        foreach (str_getcsv($csvString, "\n") as $line => $row) {
            $values = str_getcsv($row, $delimiter);

            //fill indexes
            if ($line == 0) {
                foreach ($values as $index => $value) {
                    if (in_array($value, $fields)) {
                        $indexes[array_search($value, $fields)] = $index;
                    } else {
                        throw new MissingMandatoryParametersException("Missing field definition for first row value $value");
                    }
                }
            }

            if ((count($values) != count($indexes))) {
                continue;
            }

            $manager = Manager::createInst();
            $manager->setName($values[$indexes['name']]);
            $manager->setSkype($values[$indexes['skype']]);
            $manager->setEmail($values[$indexes['email']]);
            $manager->setPhone($values[$indexes['phone']]);
            $manager->setPassword($this->passwordEncoder->encodePassword($manager, $password));

            $manager->setDepartment($department);

            $errors     = $this->validator->validate($manager);

            if (count($errors) > 0) {
                continue;
            } else {
                $this->em->persist($manager);
                $numberOfUploadedManagers++;
            }
        }
        $this->em->flush();

        return $numberOfUploadedManagers;
    }

    private function loadManagersExcel(UploadedFile $file, $fields, $department, $password) {
        $phpExcelObject = $this->phpExcel->createPHPExcelObject($file->getPathname());
        $activeSheet = $phpExcelObject->getActiveSheet();

        $numberOfUploadedManagers = 0;
        $indexes = [];

        $highestColumn = $activeSheet->getHighestDataColumn();
        for ($i = 1; $i <= $activeSheet->getHighestDataRow(); $i++) {
            $values = $phpExcelObject->getActiveSheet()
                ->rangeToArray(
                    'A' . $i .
                    ':' .
                    $highestColumn . $i, ''
                )[0];

            //fill indexes
            if ($i == 1) {
                foreach ($values as $index => $value) {
                    if (in_array($value, $fields)) {
                        $indexes[array_search($value, $fields)] = $index;
                    } else {
                        throw new MissingMandatoryParametersException("Missing field definition for first row value $value");
                    }
                }
            }

            if ((count($values) != count($indexes))) {
                continue;
            }

            $manager = Manager::createInst();
            $manager->setName($values[$indexes['name']]);
            $manager->setSkype($values[$indexes['skype']]);
            $manager->setEmail($values[$indexes['email']]);
            $manager->setPhone($values[$indexes['phone']]);
            $manager->setPassword($this->passwordEncoder->encodePassword($manager, $password));

            $manager->setDepartment($department);

            $errors     = $this->validator->validate($manager);

            if (count($errors) > 0) {
                continue;
            } else {
                $this->em->persist($manager);
                $numberOfUploadedManagers++;
            }
        }
        $this->em->flush();

        return $numberOfUploadedManagers;
    }
}