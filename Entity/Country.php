<?php

namespace NaxCrmBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * Country
 *
 * @ORM\Table(name="countries", indexes={
 *     @ORM\Index(name="fk_currency", columns={"currency"})
 * })
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\CountryRepository")
 */
class Country extends BaseEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=2, unique=true)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=95, unique=true)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="lang_code", type="string", length=2, options={"default" : "en"})
     */
    protected $langCode = 'en';

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, options={"default" : "USD"})
     */
    protected $currency = 'USD';

    /**
     * @ORM\OneToMany(targetEntity="NaxCrmBundle\Entity\CountryMapping", mappedBy="country")
     * @Exclude
     */
    protected $mappings;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Country
     */
    public function setCode($code)
    {
        // $code = strtoupper($code);
        // $code = str_replace('UK', 'GB', $code);
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        $code = strtoupper($this->code);
        $code = str_replace('UK', 'GB', $code);
        return $code;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Country
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set langCode
     *
     * @param string $langCode
     *
     * @return Country
     */
    public function setLangCode($langCode)
    {
        $langCode = strtolower($langCode);
        $this->langCode = $langCode;

        return $this;
    }

    /**
     * Get langCode
     *
     * @return string
     */
    public function getLangCode()
    {
        $langCode = strtolower($this->langCode);
        return $langCode?:'en';
    }

    /**
     * Set currency
     *
     * @param string $currency
     *
     * @return Country
     */
    public function setCurrency($currency)
    {
        $currency = strtolower($currency);
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency()
    {
        $currency = strtolower($this->currency);
        return $currency?:'USD';
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->mappings = new ArrayCollection();
    }

    /**
     * Add mapping
     *
     * @param \NaxCrmBundle\Entity\CountryMapping $mapping
     *
     * @return Country
     */
    public function addMapping(CountryMapping $mapping)
    {
        $this->mappings[] = $mapping;

        return $this;
    }

    /**
     * Remove mapping
     *
     * @param \NaxCrmBundle\Entity\CountryMapping $mapping
     */
    public function removeMapping(CountryMapping $mapping)
    {
        $this->mappings->removeElement($mapping);
    }

    /**
     * Get mappings
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMappings()
    {
        return $this->mappings;
    }
}
