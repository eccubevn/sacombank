<?php

namespace Plugin\Sacombank\Service\Payment\Method;

use Eccube\Common\EccubeConfig;
use Eccube\Repository\OrderRepository;
use Plugin\Sacombank\Entity\Config;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\Sacombank\Repository\ConfigRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Date;

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
     * @param EccubeConfig $eccubeConfig
     * @param OrderRepository $orderRepository
     * @param ContainerInterface $container
     */
    public function __construct(
        OrderStatusRepository $orderStatusRepository,
        PurchaseFlow $shoppingPurchaseFlow,
        ConfigRepository $configRepository,
        EccubeConfig $eccubeConfig,
        OrderRepository $orderRepository,
        ContainerInterface $container
    ) {
        $this->orderStatusRepository = $orderStatusRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->SacomConfig = $configRepository->get();
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
     * @return PaymentDispatcher
     * @throws \Eccube\Service\PurchaseFlow\PurchaseException
     */
    public function apply()
    {
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);
        $this->Order->setOrderStatus($OrderStatus);

        $this->purchaseFlow->prepare($this->Order, new PurchaseContext());

        $html = $this->getHtmlContent();
        $response = new Response();
        $response->setContent($html);

        $dispatcher = new PaymentDispatcher();
        $dispatcher->setResponse($response);

        return $dispatcher;
    }

    public function getHtmlContent()
    {
        $params = [
            'access_key' => $this->SacomConfig->getAccessKey(),
            'profile_id' => $this->SacomConfig->getProfileId(),
            'transaction_uuid' => $this->Order->getPreOrderId(),
            'signed_field_names' => 'access_key,profile_id,transaction_uuid,signed_field_names,unsigned_field_names,signed_date_time,locale,transaction_type,reference_number,amount,currency,bill_to_forename,bill_to_surname,bill_to_email,bill_to_address_line1,bill_to_address_city,bill_to_address_country,bill_state',
            'unsigned_field_names' => '',
            'signed_date_time' => gmdate("Y-m-d\TH:i:s\Z"),
            'locale' => 'vn',
            'transaction_type' => 'authorization',
            'reference_number' =>(new \DateTime())->getTimestamp(),
            'amount' => $this->Order->getTotal(),
            'currency' => 'VND',
            'bill_to_forename' => 'Alan',
            'bill_to_surname' => 'Smith',
            'bill_to_email' => 'joesmith@example.com',
            'bill_to_address_line1' => '1 My Apartment',
            'bill_to_address_city' => 'Mountain View',
            'bill_to_address_country' => 'VN',
            'bill_state' => 'HCM',
        ];

        $html = '';
        $html .= '<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>';
        $html .= '<script type="text/javascript">';
        $html .= '$(document).ready(function() {';
        $html .= '$("input#submit").click();';
        $html .= '});';
        $html .= '</script>';

        $html .= '<form id="scb_payment_confirmation" action="'.$this->getCallUrl().'" method="post">';
        foreach($params as $name => $value) {
            $html .= "<input type=\"hidden\" id=\"" . $name . "\" name=\"" . $name . "\" value=\"" . $value . "\"/>\n";
        }
        $html .= "<input type=\"hidden\" id=\"signature\" name=\"signature\" value=\"" . $this->sign($params) . "\"/>\n";
        $html.= '<input type="submit" id="submit" value="Đang chuyển trang..." style="border: 0; background: none">';
        $html.= '</form>';

        return $html;
    }

    public function sign($params)
    {
        return $this->signData($this->buildDataToSign($params), $this->SacomConfig->getSecret());
    }

    public function signData($data, $secretKey) {
        return base64_encode(hash_hmac('sha256', $data, $secretKey, true));
    }

    public function buildDataToSign($params) {
        $signedFieldNames = explode(",",$params["signed_field_names"]);
        foreach ($signedFieldNames as $field) {
            $dataToSign[] = $field . "=" . $params[$field];
        }
        return $this->commaSeparate($dataToSign);
    }

    public function commaSeparate ($dataToSign) {
        return implode(",",$dataToSign);
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

