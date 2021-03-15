<?php

namespace App\Classes;

class Contipay
{
    protected $api_key;
    protected $response;
    protected $api_secret;
    protected $webhookUrl;
    protected $succesUrl;
    protected $cancelUrl;
    protected $merchantId;

    const URL = 'https://api2-test.contipay.co.zw';
    const ADJUST = "/transaction/update";
    const ACQUIRE = "/acquire/payment";
    const RESPONSE = "/acquire/response";
    const DISBURSE = "/disburse/payment";
    const PUT = 'PUT';

    public function __construct(string $api_key, string $api_secret)
    {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }

    public function payment(array $data)
    {
        // prepare auth token 
        $auth = $this->prepareAuth();

        // initialize http client
        $client = Util::http($auth);

        // prepare post data/payload
        $payload = $this->preparePostPaymentData($data);

        // send a request
        $res = $client->request(self::PUT, self::ACQUIRE, [
            'json' => $payload
        ]);

        // decode json response
        $response = json_decode($res->getBody()->getContents(), true);

        // check if error exist, if does not, redirect to contipay
        Util::checkError($response);
    }

    public function prepareAuth()
    {
        $arr = [
            $this->getApi_key(),
            $this->getApi_secret()
        ];

        $basic_auth = Util::basicAuth($arr);

        return 'Basic ' . $basic_auth;
    }

    public function preparePostPaymentData($arr_1 = [])
    {
        $arr_2 = [
            'reference' =>  Util::generate(5),
            'merchantId' => $this->getMerchantId(),
            'webhookUrl' => $this->getWebhookUrl(),
            'successUrl' => $this->getSuccesUrl(),
            'cancelUrl' => $this->getCancelUrl()
        ];

        $data = array_merge($arr_1, $arr_2);

        return $data;
    }

    /**
     * Get the value of api_key
     */
    public function getApi_key()
    {
        return $this->api_key;
    }

    /**
     * Set the value of api_key
     *
     * @return  self
     */
    public function setApi_key($api_key)
    {
        $this->api_key = $api_key;

        return $this;
    }

    /**
     * Get the value of api_secret
     */
    public function getApi_secret()
    {
        return $this->api_secret;
    }

    /**
     * Set the value of api_secret
     *
     * @return  self
     */
    public function setApi_secret($api_secret)
    {
        $this->api_secret = $api_secret;

        return $this;
    }

    /**
     * Get the value of succesUrl
     */
    public function getSuccesUrl()
    {
        return $this->succesUrl;
    }

    /**
     * Set the value of succesUrl
     *
     * @return  self
     */
    public function setSuccesUrl($succesUrl)
    {
        $this->succesUrl = $succesUrl;

        return $this;
    }

    /**
     * Get the value of cancelUrl
     */
    public function getCancelUrl()
    {
        return $this->cancelUrl;
    }

    /**
     * Set the value of cancelUrl
     *
     * @return  self
     */
    public function setCancelUrl($cancelUrl)
    {
        $this->cancelUrl = $cancelUrl;

        return $this;
    }

    /**
     * Get the value of webhookUrl
     */
    public function getWebhookUrl()
    {
        return $this->webhookUrl;
    }

    /**
     * Set the value of webhookUrl
     *
     * @return  self
     */
    public function setWebhookUrl($webhookUrl)
    {
        $this->webhookUrl = $webhookUrl;

        return $this;
    }

    /**
     * Get the value of merchantId
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * Set the value of merchantId
     *
     * @return  self
     */
    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;

        return $this;
    }
}
