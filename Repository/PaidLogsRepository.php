<?php
namespace Plugin\Sacombank\Repository;

use Plugin\Sacombank\Entity\PaidLogs;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Eccube\Repository\AbstractRepository;
use Symfony\Component\HttpFoundation\Request;

class PaidLogsRepository extends AbstractRepository
{
    /**
     * PaidLogsRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PaidLogs::class);
    }

    /**
     * Get current config
     *
     * @param int $id
     * @return object
     */
    public function get($id = 1)
    {
        return $this->find($id);
    }

    /**
     * @param $Order
     * @param $postData
     * @throws \Doctrine\ORM\ORMException
     */
    public function saveLogs($Order, $postData)
    {
        $PaidLog = new PaidLogs();
        $PaidLog->setOrder($Order);
        $PaidLog->setPaidInformation(json_encode($postData));
        $PaidLog->setCreatedAt(new \DateTime());
        $this->getEntityManager()->persist($PaidLog);
    }
}
