<?php

namespace NaxCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

/**
 * CompanyStatistic
 *
 * @ORM\Table(name="company_statistics")
 * @ORM\Entity(repositoryClass="NaxCrmBundle\Repository\CompanyStatisticRepository")
 */
class CompanyStatistic extends BaseEntity
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
     * @var int
     *
     * @ORM\Column(name="number_of_traders", type="integer")
     */
    protected $numberOfTraders;

    /**
     * @var int
     *
     * @ORM\Column(name="orders_closed", type="integer")
     */
    protected $ordersClosed;

    /**
     * @var int
     *
     * @ORM\Column(name="orders_opened", type="integer")
     */
    protected $ordersOpened;

    /**
     * @var int
     *
     * @ORM\Column(name="company_profit", type="integer")
     */
    protected $companyProfit;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date")
     * @Type("DateTime<'Y-m-d'>")
     */
    protected $date;

    /**
     * @ORM\ManyToOne(targetEntity="NaxCrmBundle\Entity\Department")
     * @ORM\JoinColumn(name="department_id", referencedColumnName="id", nullable=false)
     * @Exclude
     */
    protected $department;

    /**
     * @VirtualProperty
     * @SerializedName("departmentId")
     */
    public function getDepartmentId()
    {
        return $this->getDepartment()->getId();
    }

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
     * Set numberOfTraders
     *
     * @param integer $numberOfTraders
     *
     * @return CompanyStatistic
     */
    public function setNumberOfTraders($numberOfTraders)
    {
        $this->numberOfTraders = $numberOfTraders;

        return $this;
    }

    /**
     * Get numberOfTraders
     *
     * @return int
     */
    public function getNumberOfTraders()
    {
        return $this->numberOfTraders;
    }

    /**
     * Set ordersClosed
     *
     * @param integer $ordersClosed
     *
     * @return CompanyStatistic
     */
    public function setOrdersClosed($ordersClosed)
    {
        $this->ordersClosed = $ordersClosed;

        return $this;
    }

    /**
     * Get ordersClosed
     *
     * @return int
     */
    public function getOrdersClosed()
    {
        return $this->ordersClosed;
    }

    /**
     * Set ordersOpened
     *
     * @param integer $ordersOpened
     *
     * @return CompanyStatistic
     */
    public function setOrdersOpened($ordersOpened)
    {
        $this->ordersOpened = $ordersOpened;

        return $this;
    }

    /**
     * Get ordersOpened
     *
     * @return int
     */
    public function getOrdersOpened()
    {
        return $this->ordersOpened;
    }

    /**
     * Set companyProfit
     *
     * @param integer $companyProfit
     *
     * @return CompanyStatistic
     */
    public function setCompanyProfit($companyProfit)
    {
        $this->companyProfit = $companyProfit;

        return $this;
    }

    /**
     * Get companyProfit
     *
     * @return int
     */
    public function getCompanyProfit()
    {
        return $this->companyProfit;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return CompanyStatistic
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set department
     *
     * @param Department $department
     *
     * @return CompanyStatistic
     */
    public function setDepartment(Department $department)
    {
        $this->department = $department;

        return $this;
    }

    /**
     * Get department
     *
     * @return Department
     */
    public function getDepartment()
    {
        return $this->department;
    }
}
