<?php

namespace Plugin\Sacombank\Service\Payment\Method;

use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\Sacombank\Entity\Config;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * Create form, hidden fields and auto submit to Cybersource
     *
     * @return string
     * @throws \Exception
     */
    protected function getHtmlContent()
    {
        $params = $this->getParameters();
        $this->PaidLogsRepo->savePayLogs($this->Order, $params);

        $html = '';
        $html .= '<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>';
        $html .= '<script type="text/javascript">';
        $html .= '$(document).ready(function() {';
        $html .= '$("input#submit").click();';
        $html .= '});';
        $html .= '</script>';

        $html .= '<form id="scb_payment_confirmation" action="' . $this->getCallUrl() . '" method="post" style="text-align: center">';
        foreach ($params as $name => $value) {
            $html .= "<input type=\"hidden\" id=\"" . $name . "\" name=\"" . $name . "\" value=\"" . $value . "\"/>\n";
        }
        $html .= "<input type=\"hidden\" id=\"signature\" name=\"signature\" value=\"" . $this->sign($params) . "\"/>\n";
        $html .= '<input type="submit" id="submit" value="Đang chuyển trang..." style="border: 0; background: none">';
        $html .= '</form>';

        return $html;
    }

    /**
     * @param $params
     * @return string
     */
    protected function sign($params)
    {
        return $this->signData($this->buildDataToSign($params), $this->SacomConfig->getSecret());
    }

    /**
     * @param $data
     * @param $secretKey
     * @return string
     */
    protected function signData($data, $secretKey)
    {
        return base64_encode(hash_hmac('sha256', $data, $secretKey, true));
    }

    /**
     * @param $params
     * @return string
     */
    protected function buildDataToSign($params)
    {
        $signedFieldNames = explode(",", $params["signed_field_names"]);
        foreach ($signedFieldNames as $field) {
            $dataToSign[] = $field . "=" . $params[$field];
        }
        return $this->commaSeparate($dataToSign);
    }

    /**
     * @param $dataToSign
     * @return string
     */
    protected function commaSeparate($dataToSign)
    {
        return implode(",", $dataToSign);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getCallUrl()
    {
        return $this->SacomConfig->getCallUrl();
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getParameters()
    {
        return [
            'access_key' => $this->SacomConfig->getAccessKey(),
            'profile_id' => $this->SacomConfig->getProfileId(),
            'transaction_uuid' => $this->getTransactionId(),
            'signed_field_names' => 'access_key,profile_id,transaction_uuid,signed_field_names,unsigned_field_names,signed_date_time,locale,transaction_type,reference_number,amount,currency,bill_to_forename,bill_to_surname,bill_to_email,bill_to_address_line1,bill_to_address_city,bill_to_address_country,bill_state',
            'unsigned_field_names' => '',
            'signed_date_time' => gmdate("Y-m-d\TH:i:s\Z"),
            'locale' => 'vn',
            'transaction_type' => 'authorization',
            'reference_number' => (new \DateTime())->getTimestamp(),
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
    }

    /**
     * Unique transaction id
     *
     * @return string
     */
    protected function getTransactionId()
    {
        if ($this->isCheck) {
            return md5(date('dmYHis'));
        }

        return $this->Order->getPreOrderId();
    }

    /**
     * {@inheritdoc}
     *
     * @param Request $request
     * @return mixed
     */
    public function handleRequest(Request $request)
    {
        $reasonCode = $request->get('reason_code');
        $result['message'] = $this->getResponseCodeDescription($reasonCode);

        if ($reasonCode == 100) {
            $result['status'] = 'success';
        } else {
            $result['status'] = 'error';
        }

        return $result;
    }

    /**
     * Get description of response code
     *
     * @param $responseCode
     * @return string
     */
    protected function getResponseCodeDescription($responseCode)
    {
        if (in_array($responseCode, [100, 110])) {
            // Giao dịch thành công
            return trans("Sacombank.response.credit.msg.successful_transaction");
        }

        if (in_array($responseCode, [200, 201, 230, 520])) {
            // Ủy quyền bị từ chối
            return trans("Sacombank.response.credit.msg.authorization_was_declined");
        }

        if (in_array($responseCode, [102, 200, 202, 203, 204, 205, 207, 208, 210, 211, 221, 222, 231, 232, 233, 234, 236, 240, 475, 476, 481])){
            // Giao dịch bị từ chối
            return trans("Sacombank.response.credit.msg.transaction_was_declined");
        }

        if (in_array($responseCode, [104, 150, 151, 152])) {
            // Lỗi: truy cập bị từ chối hoặc không tìm thấy trang hoặc lỗi máy chủ.
            return trans("Sacombank.response.credit.msg.access_denied");
        }

        return trans("Sacombank.response.credit.msg.reasonCode_default");
    }
}
