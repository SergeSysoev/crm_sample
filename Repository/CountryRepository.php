<?php

namespace NaxCrmBundle\Repository;

class CountryRepository extends AbstractEntityRepository
{

    public function getCountryIdsByMappings()
    {
        $qb = $this->createQueryBuilder('countries')
            ->select(['mappings.code', 'countries.id'])
            ->join('countries.mappings', 'mappings')
        ;

        return $qb->getQuery()->getResult();
    }

    public function getCountryIdsByCodes()
    {
        $qb = $this->createQueryBuilder('countries')
            ->select(['countries.code', 'countries.id'])
        ;

        return $qb->getQuery()->getResult();
    }
    public function getCountryIdsByName()
    {
        $qb = $this->createQueryBuilder('countries')
            ->select(['countries.name', 'countries.id'])
        ;

        return $qb->getQuery()->getResult();
    }

    public function getAllCountriesByAllCodes()
    {
        $countryCodes = [];
        foreach ($this->getCountryIdsByCodes() as $countryCode) {
            $countryCode['code'] = strtolower($countryCode['code']);
            $countryCodes[$countryCode['code']] = $countryCode['id'];
        }
        foreach ($this->getCountryIdsByMappings() as $countryMapping) {
            $countryMapping['code'] = strtolower($countryMapping['code']);
            $countryCodes[$countryMapping['code']] = $countryMapping['id'];
        }
        return $countryCodes;
    }

    public function getAllCountriesByAllNames()
    {
        $countryCodes = [];
        foreach ($this->getCountryIdsByName() as $countryCode) {
            $countryCode['name'] = strtolower($countryCode['name']);
            $countryCodes[$countryCode['name']] = $countryCode['id'];
        }
        // foreach ($this->getCountryIdsByMappingsName() as $countryMapping) {
        //     $countryCodes[$countryMapping['name']] = $countryMapping['id'];
        // }
        return $countryCodes;
    }

    public function getDistinctCurrency()
    {
        $currency = [];
        $q = "SELECT DISTINCT `currency` AS `alias` FROM `countries`";
        $this->setSql($q);
        $res = $this->fetchAll();
        foreach ($res as $v) {
            $currency = $v['currency'];
        }
        return $currency;
    }
}