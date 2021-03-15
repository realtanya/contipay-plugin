<?php

namespace App\Classes;

/**
 *
 * @SuppressWarnings("StaticAccess")
 */
class HTTPCustom
{

    protected $execute;

    protected $header;

    protected $soapClient;

    protected $contentLength = FALSE;

    protected $timeout = 5;

    protected $curlResponse = "";

    protected $curlError;

    protected $curlInfo = [];

    protected $soapResponse;

    protected $soapLastRequest;

    protected $location = "";

    protected $json = TRUE;

    protected $responseWaiting = TRUE;

    protected $verifySSL = TRUE;

    protected $verifyHOST = FALSE;

    public const POST = "POST";

    public const PUT = "PUT";

    public const GET = "GET";

    public const DELETE = "DELETE";

    public const LINK = "LINK";

    public const UNLINK = "UNLINK";

    public const PATCH = "PATCH";

    /**
     * Url Property / location
     *
     * @var string
     */
    public $url;

    public function __construct(string $method = self::POST)
    {
        $this->method = $method;
    }

    /**
     * Set the header for the request to be prepare for
     *
     * @param array $header in form of array and the keys
     * @return void
     */
    public function setHeader(array $header)
    {
        $this->header = (is_array($this->header)) ? $this->header : array();

        foreach ($header as $key => $value) {

            $this->header[$key] = $value;
        }

        return $this;
    }
    /**
     * This Function will prepare execute variable for curl requests
     *
     * @param boolean $post if set to true request will be sent out as a post
     * @param array $fields
     * @return void
     */
    public function curlPrepare($fields = "", string $password = null)
    {

        $this->execute = curl_init();

        $this->location = (empty($this->url)) ? $this->location : $this->url;

        curl_setopt($this->execute, CURLOPT_URL, $this->location);

        $this->params = ($this->json) ? json_encode($fields) : $fields;

        if ($this->contentLength) {

            array_push($this->header, "Content-Length: " . strlen($this->params));
        }

        if ($this->method != self::GET) {

            $this->paramError = json_last_error();

            curl_setopt($this->execute, CURLOPT_CUSTOMREQUEST, $this->method);

            if (!empty($this->params)) {

                curl_setopt($this->execute, CURLOPT_POSTFIELDS, $this->params);
            }
        }

        if (!$this->getResponseWaiting()) {

            curl_setopt($this->execute, CURLOPT_NOSIGNAL, 1);
        }

        curl_setopt($this->execute, CURLOPT_FOLLOWLOCATION, 1);

        curl_setopt($this->execute, CURLOPT_MAXREDIRS, 5);

        curl_setopt($this->execute, CURLOPT_TIMEOUT, $this->timeout);

        curl_setopt($this->execute, CURLOPT_HEADER, FALSE);

        curl_setopt($this->execute, CURLOPT_RETURNTRANSFER, TRUE);

        curl_setopt($this->execute, CURLOPT_HTTPHEADER, $this->header);

        curl_setopt($this->execute, CURLOPT_ENCODING, "");

        // curl_setopt($this->execute, CURLOPT_SSL_VERIFYHOST, $this->getVerifySSL());

        curl_setopt($this->execute, CURLOPT_SSL_VERIFYPEER, $this->getVerifyHOST());

        if ($password != null) {

            curl_setopt($this->execute, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

            curl_setopt($this->execute, CURLOPT_USERPWD, $password);
        }

        return $this;
    }

    /**
     * Post a Curl Request
     *
     * @param   bool  $logRequest  set to false to ignore logging the resqeuest data
     *
     * @return  self
     */
    public function curlExecute(bool $logRequest = true)
    {

        $this->curlResponse = curl_exec($this->execute);

        $this->curlError = curl_error($this->execute);

        $this->curlInfo = curl_getinfo($this->execute);

        if ($this->curlError) {

            // $log = new Log;

            // $log
            //     ->setData((object)["message" => $this->curlError])
            //     ->setUserId(0)
            //     ->setSession(session_id())
            //     ->setAction("Curl-Error")
            //     ->save();

            throw new  \Exception("Error Processing Request:" . $this->curlError, 408);
        }

        curl_close($this->execute);
    }

    public function soapPrepare(string $url)
    {

        $this->soapClient = new \SoapClient($url, $this->header);

        $this->soapClient->__setLocation($url);

        return $this;
    }

    public function soapExecute($preparedData)
    {

        $this->soapResponse = $this->soapClient->Execute($preparedData);

        $this->soapLastRequest = $this->soapClient->__getLastRequest();

        if (!$this->soapResponse) {

            // Tools::logRequest($this->soapLastRequest, "SoapError");

            throw new  \Exception("Error Processing Request:" . $this->soapLastRequest);
        }

        return $this;
    }

    public function logSoapRequest()
    {

        $request = json_encode($this->soapClient->__getLastRequest(), JSON_UNESCAPED_SLASHES);

        // Tools::logRequest($request, $this->requester . "Request");

        return $this;
    }

    public function logSoapResponse()
    {

        $request = json_encode($this->soapClient->__getLastResponse(), JSON_UNESCAPED_SLASHES);

        // Tools::logRequest($request, $this->requester . "Response");

        return $this;
    }

    /**
     * Get url Property / location
     *
     * @return  string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set url Property / location
     *
     * @param  string  $url  Url Property / location
     *
     * @return  self
     */
    public function setUrl(string $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get the value of timeout
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Set the value of timeout
     *
     * @return  self
     */
    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Get the value of curlResponse
     */
    public function getCurlResponse()
    {
        return $this->curlResponse;
    }

    /**
     * Get the value of location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set the value of location
     *
     * @return  self
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get the value of curlError
     */
    public function getCurlError()
    {
        return $this->curlError;
    }



    /**
     * Set the value of json
     *
     * @return  self
     */
    public function asJson()
    {
        $this->json = TRUE;

        return $this;
    }

    /**
     * Set the value of json
     *
     * @return  self
     */
    public function asNoneJson()
    {
        $this->json = FALSE;

        return $this;
    }

    /**
     * Get the value of curlInfo
     */
    public function getCurlInfo()
    {
        return $this->curlInfo;
    }

    /**
     * Set the value of contentLength
     *
     * @return  self
     */
    public function setContentLength()
    {
        $this->contentLength = true;

        return $this;
    }

    /**
     * Get the value of soapResponse
     */
    public function getSoapResponse()
    {
        return $this->soapResponse;
    }

    /**
     * Get the value of soapLastRequest
     */
    public function getSoapLastRequest()
    {
        return $this->soapLastRequest;
    }


    /**
     * Get the value of responseWaiting
     */
    public function getResponseWaiting()
    {
        return $this->responseWaiting;
    }

    /**
     * Set the value of responseWaiting
     *
     * @return  self
     */
    public function setResponseWaiting($responseWaiting)
    {
        $this->responseWaiting = $responseWaiting;

        return $this;
    }

    /**
     * Get the value of verifySSL
     */
    public function getVerifySSL()
    {
        return $this->verifySSL;
    }

    /**
     * Set the value of verifySSL
     *
     * @return  self
     */
    public function setVerifySSL($verifySSL)
    {
        $this->verifySSL = $verifySSL;

        return $this;
    }

    /**
     * Get the value of verifyHOST
     */
    public function getVerifyHOST()
    {
        return $this->verifyHOST;
    }

    /**
     * Set the value of verifyHOST
     *
     * @return  self
     */
    public function setVerifyHOST($verifyHOST)
    {
        $this->verifyHOST = $verifyHOST;

        return $this;
    }
}
