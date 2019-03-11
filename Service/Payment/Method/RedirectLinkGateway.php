<?php

namespace Plugin\Sacombank\Service\Payment\Method;

use Eccube\Common\EccubeConfig;
use Eccube\Repository\OrderRepository;
use Plugin\Sacombank\Entity\Config;
use Plugin\Sacombank\Repository\PaidLogsRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\Sacombank\Repository\ConfigRepository;

abstract class RedirectLinkGateway implements PaymentMethodInterface
{
    const CREDIT_CHECK_ORDER_ID     = 99999999999;
    const DOMESTIC_CHECK_ORDER_ID   = 99999999998;

    protected $isCheck = false;

    /**
     * @var \Eccube\Entity\Order
     */
    protected $Order;

    /**
     * @var \Symfony\Component\Form\FormInterface
     */
    protected $form;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var PurchaseFlow
     */
    protected $purchaseFlow;

    /**
     * @var Config
     */
    protected $SacomConfig;

    /**
     * @var PaidLogsRepository
     */
    protected $PaidLogsRepo;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * RedirectLinkGateway constructor.
     *
     * @param OrderStatusRepository $orderStatusRepository
     * @param PurchaseFlow $shoppingPurchaseFlow
     * @param ConfigRepository $configRepository
     * @param PaidLogsRepository $paidLogsRepository
     * @param EccubeConfig $eccubeConfig
     * @param OrderRepository $orderRepository
     * @param ContainerInterface $container
     */
    public function __construct(
        OrderStatusRepository $orderStatusRepository,
        PurchaseFlow $shoppingPurchaseFlow,
        ConfigRepository $configRepository,
        PaidLogsRepository $paidLogsRepository,
        EccubeConfig $eccubeConfig,
        OrderRepository $orderRepository,
        ContainerInterface $container
    ) {
        $this->orderStatusRepository = $orderStatusRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->SacomConfig = $configRepository->get();
        $this->PaidLogsRepo = $paidLogsRepository;
        $this->eccubeConfig = $eccubeConfig;
        $this->orderRepository = $orderRepository;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     *
     * @return PaymentResult
     */
    public function verify()
    {
        $result = new PaymentResult();
        $result->setSuccess(true);

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @return PaymentResult
     */
    public function checkout()
    {
        $result = new PaymentResult();
        $result->setSuccess(true);

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Symfony\Component\Form\FormInterface $form
     * @return $this
     */
    public function setFormType(\Symfony\Component\Form\FormInterface $form)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @param \Eccube\Entity\Order $Order
     * @return $this
     */
    public function setOrder(\Eccube\Entity\Order $Order)
    {
        $this->Order = $Order;
        return $this;
    }

    /**
     * Check connect to Onepay input card page
     *
     * @param Config $Config
     * @return mixed
     */
    abstract public function checkConn(Config $Config);

    /**
     * Generate url endpoint which will be redirect to process payment
     *
     * @return string
     */
    abstract public function getCallUrl();

    /**
     * Handle response via Request object
     *
     * @param Request $request
     * @return mixed
     */
    abstract public function handleRequest(Request $request);
}

