<?php
namespace Plugin\Sacombank;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\PaymentRepository;
use Plugin\Sacombank\Entity\PaidLogs;
use Plugin\Sacombank\Repository\PaidLogsRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SacombankEvent implements EventSubscriberInterface
{
    /** @var PaidLogsRepository */
    protected $paidLogsRepository;

    /** @var PaymentRepository */
    protected $paymentRepository;

    /** @var EccubeConfig */
    protected $eccubeConfig;

    /**
     * @var \Twig_Environment
     */
    protected $twigEnvironment;

    /**
     * OnepayEvent constructor.
     * @param PaidLogsRepository $paidLogsRepository
     * @param PaymentRepository $paymentRepository
     * @param EccubeConfig $eccubeConfig
     * @param \Twig_Environment $twigEnvironment
     */
    public function __construct(
        PaidLogsRepository $paidLogsRepository,
        PaymentRepository $paymentRepository,
        EccubeConfig $eccubeConfig,
        \Twig_Environment $twigEnvironment
    ) {
        $this->paidLogsRepository = $paidLogsRepository;
        $this->paymentRepository = $paymentRepository;
        $this->eccubeConfig = $eccubeConfig;
        $this->twigEnvironment = $twigEnvironment;
    }


    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Order/edit.twig' => 'adminOrderEditIndexInitialize'
        ];
    }

    /**
     * @param TemplateEvent $event
     */
    public function adminOrderEditIndexInitialize(TemplateEvent $event)
    {
        $parameter = $event->getParameters();
        /** @var Order $Order */
        $Order = $parameter['Order'];

        /** @var PaidLogs $PaidLogs */
        $PaidLogs = $this->paidLogsRepository->findOneBy(["Order" => $Order]);
        if ($PaidLogs) {
            $parameter['payment'] = $this->paymentRepository->find($Order->getPayment()->getId());
            $paidLog = $PaidLogs->getPaidInformation(true);

            $locale = $this->eccubeConfig->get('locale');
            $currency = $this->eccubeConfig->get('currency');
            $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

            $paidLog['auth_amount'] = $formatter->formatCurrency($paidLog['auth_amount'], $currency);

            if(isset($paidLog['payer_authentication_proof_xml'])){
                unset($paidLog['payer_authentication_proof_xml']);
            }
            if(isset($paidLog['signed_field_names'])){
                unset($paidLog['signed_field_names']);
            }

            $parameter['paidLog'] = $paidLog;
            $event->setParameters($parameter);

            $twig = '@Sacombank/admin/paid_log.twig';
            $event->addSnippet($twig);
        }
    }
}
