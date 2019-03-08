<?php
namespace Plugin\Sacombank\Service\Payment\Method;

use Eccube\Entity\Order;
use Plugin\Sacombank\Entity\Config;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LinkCreditCard extends RedirectLinkGateway
{
    /**
     * @param Config $Config
     * @return string
     */
    public function checkConn(Config $Config)
    {
        $this->isCheck = true;
        $this->Order = new Order();
        $this->Order->setTotal(10000);
        $this->SacomConfig = $Config;
        $url = $this->getCallUrl();

        return $url;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getCallUrl()
    {
        $vpcURL = $this->SacomConfig->getCallUrl();

        return $vpcURL;
    }

    protected function getParameters()
    {
        $Config = $this->SacomConfig;

        return [
            'vpc_Merchant' => $Config->getCreditMerchantId(),
            'vpc_AccessCode' => $Config->getCreditMerchantAccessCode(),
            'vpc_MerchTxnRef' => $this->getTransactionId(), // transaction id
            'vpc_OrderInfo' => $this->getOrderInfo(),
            'vpc_Amount' => $this->Order->getTotal() * 100,
            'vpc_ReturnURL' => $this->getReturnURL(),
            'vpc_Version' => '2',
            'vpc_Command' => 'pay',
            'vpc_Locale' => 'en',
            'vpc_TicketNo' => $_SERVER['REMOTE_ADDR'],
            'AgainLink' => isset($_SERVER['HTTP_REFERER']) ? urlencode($_SERVER['HTTP_REFERER']) : null,
            'Title' => 'VPC 3-Party',
            'AVS_Street01' => $this->Order->getAddr02(),
            'AVS_City' => $this->Order->getPref() ? $this->Order->getPref()->getName() : '',
            'AVS_StateProv' => $this->Order->getAddr01(),
            'AVS_PostCode' => $this->Order->getPostalCode(),
            'AVS_Country' => 'VN',
            'vpc_SHIP_Street01' => '39A Ngo Quyen',
            'vpc_SHIP_Provice' => 'Hoan Kiem',
            'vpc_SHIP_City' =>  'Ha Noi',
            'vpc_SHIP_Country' => 'Viet Nam',
            'vpc_Customer_Phone' => '840904280949',
            'vpc_Customer_Email' => 'support@onepay.vn',
            'vpc_Customer_Id' => 'thanhvt',
        ];
    }

    protected function getReturnURL()
    {
        if ($this->isCheck){
            return $this->container->get('router')->generate('onepay_admin_config_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
        }
        return $this->container->get('router')->generate('onepay_back', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Unique transaction id
     *
     * @return string
     */
    protected function getTransactionId()
    {
        if ($this->isCheck){
            return md5(date('dmYHis'));
        }

        return $this->Order->getPreOrderId();
    }

    /**
     * Order info
     *
     * @return string
     */
    protected function getOrderInfo()
    {
        if ($this->isCheck){
            return self::DOMESTIC_CHECK_ORDER_ID;
        }

        return str_pad($this->Order->getId(), 11, '0', STR_PAD_LEFT);
    }

    /**
     * Get description of response code
     *
     * @param $responseCode
     * @return string
     */
    public function getResponseCodeDescription($responseCode)
    {
        switch ($responseCode) {
            case "100" :
            case "102" :
            case "104" :
            case "110" :
            case "150" :
            case "151" :
            case "152" :
            case "200" :
            case "201" :
            case "202" :
            case "203" :
            case "204" :
            case "205" :
            case "207" :
            case "208" :
            case "210" :
            case "211" :
            case "221" :
            case "222" :
            case "230" :
            case "231" :
            case "232" :
            case "233" :
            case "234" :
            case "236" :
            case "240" :
            case "475" :
            case "481" :
            case "520" :
                $result = "Sacombank.response.credit.msg.reasonCode_".$responseCode;
                break;

            default  :
                $result = "Sacombank.response.credit.msg.reasonCode_default";
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param Request $request
     * @return mixed
     */
    public function handleRequest(Request $request)
    {
//        $Config = $this->SacomConfig;
        $reasonCode = $request->get('reason_code');
        $reason = $this->getResponseCodeDescription($reasonCode);
        $result['status'] = $reason;

        return $result;
    }
}
