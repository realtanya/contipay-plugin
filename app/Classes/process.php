<?php
/**
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

class ContiPayProcessor extends ObjectModel
{

    /**
     * ContiPay Log Table ID
     *
     * @var int
     */
    public $id_contipay;

    /**
     * Currency ID
     * @var int
     */
    public $id_currency;

    /**
     * Order ID
     *
     * @var int
     */
    public $id_order;

    /**
     * Cart ID
     *
     * @var int
     */
    public $id_cart;

    /**
     * CUstomer ID
     *
     * @var int
     */
    public $id_customer;

    /**
     * STatus Code
     *
     * @var int
     */
    public $status_code;

    /**
     * Payment Provider Code
     *
     * @var string
     */
    public $provider_code;

    /**
     * Payment Provider Name
     *
     * @var string
     */
    public $provider_name;

    /**
     * Correlator GUID for inquiries with ContiPay
     *
     * @var string
     */
    public $correlator;

    /**
     * Order Amount
     *
     * @var float
     */
    public $merchant_amount;

    /**
     * Amount Charged to merchant
     *
     * @var float
     */
    public $merchant_charge;

    /**
     * Amount Charged to customer
     *
     * @var float
     */
    public $customer_charge;

    /**
     * Merchant reference / correlator for inter system identification
     *
     * @var string
     */
    public $merchant_ref;

    /**
     * Description of Order
     *
     * @var string
     */
    public $description;

    /**
     * ContiPay Reference Code
     *
     * @var string
     */
    public $contipay_ref;

    /**
     * ContiPay Response
     *
     * @var string
     */
    public $response;

    /**
     * ContiPay Raw Response
     *
     * @var string
     */
    public $data;

    /**
     * Date This Transaction was Created
     *
     * @var string
     */
    public $date_add;

    /**
     * Date This Transaction was Updated
     *
     * @var string
     */
    public $date_upd;

    /**
     * Module ID
     *
     * @var int
     */
    protected $moduleId;

    /**
     * URL To Take user to if present or necessary
     *
     * @var string
     */
    protected $redirectUrl;

    /**
     * Full Unedited Response Body
     *
     * @var string
     */
    protected $responseBody;

    /**
     * Full Prepared Request
     *
     * @var array
     */
    protected $requestBody;

    /**
     * Is this Payment for a delivery order
     *
     * @var bool
     */
    protected $isDelivery = false;

    /**
     * Allow payments to be done by offline cash/swipe
     *
     * @var bool
     */
    protected $allowOfflinePayment = false;

    const ADJUST = "/transaction/update";

    const ACQUIRE = "/acquire/payment";

    const RESPONSE = "/acquire/response";

    const DISBURSE = "/disburse/payment";

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'contipay_logs',
        'primary' => 'id_contipay',
        'fields' => array(
            'id_currency' =>    array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_order' =>       array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false),
            'id_cart' =>        array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_customer' =>    array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'status_code' =>    array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false),
            'merchant_amount' =>array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
            'merchant_charge' =>array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => false),
            'customer_charge' =>array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => false),
            'merchant_ref' =>   array('type' => self::TYPE_STRING),
            'description' =>    array('type' => self::TYPE_STRING),
            'correlator' =>     array('type' => self::TYPE_STRING),
            'provider_code' =>  array('type' => self::TYPE_STRING),
            'provider_name' =>  array('type' => self::TYPE_STRING),
            'contipay_ref' =>   array('type' => self::TYPE_STRING),
            'response' =>       array('type' => self::TYPE_STRING),
            'data' =>           array('type' => self::TYPE_STRING),
            'date_add' =>       array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' =>       array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    public function __construct($id = null, $id_lang = null)
    {
        parent::__construct($id, $id_lang);
        $this->context = Context::getContext();
        $this->data = json_encode(["Transaction Initiated"]);
        $this->merchant_ref = time();
        return $this;
    }

    public function setByCartId(
        int $cartId,
        array $statusCodes = [ContiPay::PENDING, ContiPay::PAID, ContiPay::ERROR, ContiPay::CONFIRMED]
    ) {
        $statuses = implode(",", $statusCodes);
        $result = Db::getInstance()
            ->executeS(
                "SELECT `id_contipay`
                    FROM `". _DB_PREFIX_ . self::$definition['table'] . "`
                    WHERE id_cart = $cartId
                    AND status_code IN ($statuses)
                    ORDER BY `id_contipay` DESC LIMIT 1"
            );
        parent::__construct($result[0]['id_contipay']);
        return $this;
    }

    public function setByOrderId(
        int $orderId,
        array $statusCodes = [ContiPay::PENDING, ContiPay::PAID, ContiPay::ERROR, ContiPay::CONFIRMED, ContiPay::APPROVED, ContiPay::DECLINED]
    ) {
        $statuses = implode(",", $statusCodes);
        $result = Db::getInstance()
            ->executeS(
                "SELECT `id_contipay`
                    FROM `". _DB_PREFIX_ . self::$definition['table'] . "`
                    WHERE id_order = $orderId
                    AND status_code IN ($statuses)
                    ORDER BY `id_contipay` DESC LIMIT 1"
            );
        parent::__construct($result[0]['id_contipay']);
        return $this;
    }

    public function preparePayment(Cart $cart, Customer $customer)
    {
        try {
            $currency = new Currency($cart->id_currency);
            $address = $customer->getSimpleAddress($cart->id_address_delivery);
            $this->id_cart = $cart->id;
            $this->id_customer = $customer->id;
            $this->id_currency = $currency->id;
            $this->merchant_amount = $cart->getOrderTotal(true, Cart::BOTH);
            $this->description =  "Shopping Cart Order #" . $this->id_cart;
            $this->add();
            $params = [
                "webhookUrl" => $this->context->link->getModuleLink(
                    "contipay",
                    'webhook',
                    array(
                        "reference" => $this->id
                    ),
                    true
                ),
                "type" => "charge",
                "amount" => $this->merchant_amount,
                "reference" => $this->merchant_ref,
                "description" => $this->description,
                "merchantId" => (int)Configuration::get("CONTIPAY_CID"),
                "successUrl" =>  $this->context->link->getPageLink(
                    "order-confirmation",
                    null,
                    null,
                    [
                        "id_cart" => $this->id_cart,
                        "id_module" => $this->getModuleId(),
                        "key" => $customer->secure_key
                    ]
                ),
                "cancelUrl" =>  $this->context->link->getModuleLink(
                    "contipay",
                    'redirect',
                    array(
                        "reference" => $this->id
                    ),
                    true
                ),
                "currencyCode" => $currency->iso_code
            ];
            if (!$customer->is_guest) {
                if ($this->getAllowOfflinePayment()) {
                    $params["coc"] = ($this->getIsDelivery()) ? false : true;
                    $params["cod"] = ($this->getIsDelivery()) ? true : false;;
                }
                $params["customer"] = (object)[
                        "nationalId"    => null,
                            "firstName" => $customer->firstname,
                            "surname"   => $customer->lastname,
                            "middleName"=> "",
                            "email"     => $customer->email,
                            "cell"      => $address["phone_mobile"],
                            "countryCode"=> $address["country_iso"]
                    ];
            }
            $this->setRequestBody(
                $params
            );
            return $this;
        } catch (Exception $error) {
            return false;
        }
    }

    public function prepareSeamlessPayment(Order $order, Customer $customer, $account, $method)
    {
        try {
            $currency = new Currency($order->id_currency);
            $address = $customer->getSimpleAddress($order->id_address_delivery);
            $this->id_cart = $order->id_cart;
            $this->id_customer = $customer->id;
            $this->id_currency = $currency->id;
            $this->merchant_amount = (float)$order->total_paid ;
            $this->description =  "Shopping Cart Order #" . $order->id;
            $this->add();
            $params = [
                "customer"      => [
                    "nationalId"    => "",
                    "surname"       => $customer->lastname,
                    "firstName"     => $customer->firstname,
                    "middleName"    => "",
                    "email"         => $customer->email,
                    "cell"          => $address["phone_mobile"],
                    "countryCode"   => $address["country_iso"]
                ],
                "transaction"   => [
                    "currencyCode"  => $currency->iso_code,
                    "providerCode"  => $method->methodCode,
                    "providerName"  => $method->methodName,
                    "amount"        => $this->merchant_amount,
                    "webhookUrl"    => $this->context->link->getModuleLink(
                        "contipay",
                        'webhook',
                        array(
                            "reference" => $this->id
                        ),
                        true
                    ),
                    "merchantId"    => (int)Configuration::get("CONTIPAY_CID"),
                    "description"   => $this->description,
                    "reference"     => $this->merchant_ref
                ],
                "accountDetails"=> [
                    "accountNumber" => $account["accountNumber"],
                    "accountName"   =>  $account["accountName"],
                    "accountExtra"  =>  (object) [
                        "account"       => $account["accountNumber"],
                        "accountName"   => $account["accountName"],
                        "code"          => $account["accountCode"],
                        "smsNumber"     => $account["accountCell"],
                        "expiry"        => $account["accountExpiry"]
                    ]
                ]
            ];
            $this->setRequestBody(
                $params
            );
            return $this;
        } catch (Exception $error) {
            return false;
        }
    }

    public function orderAdjustment(Order $order)
    {
        try {
            $currency = new Currency($this->id_currency);
            $this->merchant_amount = $order->total_paid;
            $params = [
                "currencyCode"  => $currency->iso_code,
                "providerCode"  => $this->provider_code,
                "amount"        => $this->merchant_amount,
                "merchantId"    => Configuration::get("CONTIPAY_CID"),
                "description"   => "Order Adjustment by Merchant",
                "contiPayRef"   => $this->contipay_ref
            ];
            $this->setRequestBody(
                $params
            );
            return $this;
        } catch (Exception $error) {
            return false;
        }
    }


    public function prepareStatusResponse()
    {
        $this->setRequestBody(
            [
                "amount"        => $this->merchant_amount,
                "description"   => $this->description,
                "statusCode"    => $this->status_code,
                "correlator"    => $this->correlator
            ]
        );
    }

    public function post(string $action = self::ACQUIRE, string $postAction = "post")
    {
        $client = new \GuzzleHttp\Client([
            "base_url"  =>  (Configuration::get("CONTIPAY_LIVE_MODE") == true) ? ContiPay::LIVE_API : ContiPay::UAT_API,
            "timeout"   =>  15,
            "http_errors" => false,
            "allow_redirects"   => true
        ]);
        try {
            $response = $client->{$postAction}(
                $action,
                [
                    "auth"  =>  [
                        Configuration::get("CONTIPAY_AUTH_KEY"),
                        Configuration::get("CONTIPAY_AUTH_PASS")
                    ],
                    "headers"   =>  [
                        "Content-Type"  =>  "application/json",
                        "Accept"        =>  "application/json"
                    ],
                    "body"  =>  $this->getRequestBody(),
                ]
            );
            $body = $response->getBody();
            $this->setResponseBody($body->getContents());
            $responseObject = $this->getResponseBody();
            $this->response = $responseObject->message;
            $this->status_code = $responseObject->statusCode;
            $this->contipay_ref = $responseObject->contiPayRef;
            if ($responseObject->redirectUrl) {
                $this->setRedirectUrl($responseObject->redirectUrl);
            }
        } catch (RequestException | Exception $error) {
            switch($error->getCode()) {
                case 409:
                case 404:
                case 401:
                case 200:
                    try {
                        $this->status_code = ContiPay::DECLINED;
                        $body = $error
                            ->getResponse()
                            ->getBody();
                        $this->setResponseBody($body->getContents());
                        $responseObject = $this->getResponseBody();
                        $this->response = $responseObject->message;
                    }
                    catch(Throwable $e) {
                        $this->response = $e->getMessage();
                    }
                break;
                default:
                    $this->status_code = ContiPay::ERROR;
                    $this->response = $error->getMessage();
                break;
            }
        }
        $this->save();
        return $this;
    }

    /**
     * Get full Unedited Response Body
     *
     * @return  string
     */
    public function getResponseBody()
    {
        return json_decode($this->responseBody);
    }

    /**
     * Set full Unedited Response Body
     *
     * @param  string  $responseBody  Full Unedited Response Body
     *
     * @return  self
     */
    public function setResponseBody(string $responseBody)
    {
        $this->responseBody = $this->data = $responseBody;
        return $this;
    }

    /**
     * Get uRL To Take user to if present or necessary
     *
     * @return  string
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * Set uRL To Take user to if present or necessary
     *
     * @param  string  $redirectUrl  URL To Take user to if present or necessary
     *
     * @return  self
     */
    public function setRedirectUrl(string $redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
        return $this;
    }

    /**
     * Get full Prepared Request
     *
     * @return  array
     */
    public function getRequestBody()
    {
        return json_encode($this->requestBody);
    }

    /**
     * Set full Prepared Request
     *
     * @param  array  $requestBody  Full Prepared Request
     *
     * @return  self
     */
    public function setRequestBody(array $requestBody)
    {
        $this->requestBody = $requestBody;
        return $this;
    }

    /**
     * Get module ID
     *
     * @return  int
     */
    public function getModuleId()
    {
        return $this->moduleId;
    }

    /**
     * Set module ID
     *
     * @param  int  $moduleId  Module ID
     *
     * @return  self
     */
    public function setModuleId(int $moduleId)
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    /**
     * Get is this Payment for a delivery order
     *
     * @return  bool
     */
    public function getIsDelivery()
    {
        return $this->isDelivery;
    }

    /**
     * Set is this Payment for a delivery order
     *
     * @param  bool  $isDelivery  Is this Payment for a delivery order
     *
     * @return  self
     */
    public function setIsDelivery(bool $isDelivery = true)
    {
        $this->isDelivery = $isDelivery;

        return $this;
    }

    /**
     * Get allow payments to be done by offline cash/swipe
     *
     * @return  bool
     */
    public function getAllowOfflinePayment()
    {
        return $this->allowOfflinePayment;
    }

    /**
     * Set allow payments to be done by offline cash/swipe
     *
     * @param  bool  $allowOfflinePayment  Allow payments to be done by offline cash/swipe
     *
     * @return  self
     */
    public function setAllowOfflinePayment(bool $allowOfflinePayment = true)
    {
        $this->allowOfflinePayment = $allowOfflinePayment;

        return $this;
    }
}
