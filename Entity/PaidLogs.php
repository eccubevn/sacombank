<?php
namespace Plugin\Sacombank\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="plg_sacombank_paid_logs")
 * @ORM\Entity(repositoryClass="Plugin\Sacombank\Repository\PaidLogsRepository")
 */
class PaidLogs
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned": true})
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \Eccube\Entity\Order
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Order", inversedBy="PaidLogs")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     * })
     */
    protected $Order;

    /**
     * @var string
     *
     * @ORM\Column(name="paid_information", type="text", nullable=true)
     */
    protected $paid_information;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return \Eccube\Entity\Order
     */
    public function getOrder()
    {
        return $this->Order;
    }

    /**
     * @param \Eccube\Entity\Order $Order
     */
    public function setOrder($Order)
    {
        $this->Order = $Order;
    }

    /**
     * @param bool $assoc
     * @return mixed
     */
    public function getPaidInformation($assoc = false)
    {
        return json_decode($this->paid_information, $assoc);
    }

    /**
     * @param string $paid_information
     */
    public function setPaidInformation($paid_information)
    {
        $this->paid_information = $paid_information;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param \DateTime $created_at
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetimetz")
     */
    protected $created_at;


}
