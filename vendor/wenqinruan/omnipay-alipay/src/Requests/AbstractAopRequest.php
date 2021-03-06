<?php

namespace Omnipay\Alipay\Requests;

use Omnipay\Alipay\Common\Signer;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;
abstract class AbstractAopRequest extends \Omnipay\Common\Message\AbstractRequest
{
    protected $method;
    protected $privateKey;
    protected $encryptKey;
    protected $alipayPublicKey;
    protected $endpoint = 'https://openapi.alipay.com/gateway.do';
    protected $returnable = false;
    protected $notifiable = false;
    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */
    public function getData()
    {
        $this->validateParams();
        $this->setDefaults();
        $this->convertToString();
        $data = $this->parameters->all();
        $data['method'] = $this->method;
        ksort($data);
        $data['sign'] = $this->sign($data, $this->getSignType());
        return $data;
    }
    public function validateParams()
    {
        $this->validate('app_id', 'format', 'charset', 'sign_type', 'timestamp', 'version', 'biz_content');
    }
    protected function setDefaults()
    {
        if (!$this->getTimestamp()) {
            $this->setTimestamp(date('Y-m-d H:i:s'));
        }
    }
    /**
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->getParameter('timestamp');
    }
    /**
     * @param $value
     *
     * @return $this
     */
    public function setTimestamp($value)
    {
        return $this->setParameter('timestamp', $value);
    }
    protected function convertToString()
    {
        foreach ($this->parameters->all() as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $this->parameters->set($key, json_encode($value));
            }
        }
    }
    protected function sign($params, $signType)
    {
        $signer = new \Omnipay\Alipay\Common\Signer($params);
        $signer->setIgnores(array('sign'));
        $signType = strtoupper($signType);
        if ($signType == 'RSA') {
            $sign = $signer->signWithRSA($this->getPrivateKey());
        } elseif ($signType == 'RSA2') {
            $sign = $signer->signWithRSA($this->getPrivateKey(), OPENSSL_ALGO_SHA256);
        } else {
            throw new \Omnipay\Common\Exception\InvalidRequestException('The signType is invalid');
        }
        return $sign;
    }
    /**
     * @return mixed
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }
    /**
     * @param $value
     *
     * @return $this
     */
    public function setPrivateKey($value)
    {
        $this->privateKey = $value;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getSignType()
    {
        return $this->getParameter('sign_type');
    }
    /**
     * @return mixed
     */
    public function getAlipayPublicKey()
    {
        return $this->alipayPublicKey;
    }
    /**
     * @param $value
     *
     * @return $this
     */
    public function setAlipayPublicKey($value)
    {
        $this->alipayPublicKey = $value;
        return $this;
    }
    public function sendData($data)
    {
        $url = $this->getRequestUrl($data);
        $body = $this->getRequestBody();
        $response = $this->httpClient->post($url)->setBody($body, 'application/x-www-form-urlencoded')->send()->getBody();
        $response = $this->decode($response);
        return $response;
    }
    /**
     * @param $data
     *
     * @return string
     */
    protected function getRequestUrl($data)
    {
        $queryParams = $data;
        unset($queryParams['biz_content']);
        ksort($queryParams);
        $url = sprintf('%s?%s', $this->getEndpoint(), http_build_query($queryParams));
        return $url;
    }
    /**
     * @return mixed
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }
    /**
     * @param $value
     *
     * @return $this
     */
    public function setEndpoint($value)
    {
        $this->endpoint = $value;
        return $this;
    }
    /**
     * @return string
     */
    protected function getRequestBody()
    {
        $params = array('biz_content' => $this->getBizContent());
        $body = http_build_query($params);
        return $body;
    }
    /**
     * @return mixed
     */
    public function getBizContent()
    {
        return $this->getParameter('biz_content');
    }
    protected function decode($data)
    {
        return json_decode($data, true);
    }
    /**
     * @param null $key
     * @param null $default
     *
     * @return mixed
     */
    public function getBizData($key = null, $default = null)
    {
        if (is_string($this->getBizContent())) {
            $data = json_decode($this->getBizContent(), true);
        } else {
            $data = $this->getBizContent();
        }
        if (is_null($key)) {
            return $data;
        } else {
            return array_get($data, $key, $default);
        }
    }
    /**
     * @return mixed
     */
    public function getAppId()
    {
        return $this->getParameter('app_id');
    }
    /**
     * @param $value
     *
     * @return $this
     */
    public function setAppId($value)
    {
        return $this->setParameter('app_id', $value);
    }
    /**
     * @return mixed
     */
    public function getFormat()
    {
        return $this->getParameter('format');
    }
    /**
     * @param $value
     *
     * @return $this
     */
    public function setFormat($value)
    {
        return $this->setParameter('format', $value);
    }
    /**
     * @return mixed
     */
    public function getCharset()
    {
        return $this->getParameter('charset');
    }
    /**
     * @param $value
     *
     * @return $this
     */
    public function setCharset($value)
    {
        return $this->setParameter('charset', $value);
    }
    /**
     * @param $value
     *
     * @return $this
     */
    public function setSignType($value)
    {
        return $this->setParameter('sign_type', $value);
    }
    /**
     * @param $value
     *
     * @return $this
     */
    public function setBizContent($value)
    {
        return $this->setParameter('biz_content', $value);
    }
    /**
     * @return mixed
     */
    public function getAlipaySdk()
    {
        return $this->getParameter('alipay_sdk');
    }
    /**
     * @param $value
     *
     * @return $this
     */
    public function setAlipaySdk($value)
    {
        return $this->setParameter('alipay_sdk', $value);
    }
    /**
     * @return mixed
     */
    public function getNotifyUrl()
    {
        return $this->getParameter('notify_url');
    }
    /**
     * @param $value
     *
     * @return $this
     */
    public function setNotifyUrl($value)
    {
        return $this->setParameter('notify_url', $value);
    }
    /**
     * @return mixed
     */
    public function getReturnUrl()
    {
        return $this->getParameter('return_url');
    }
    /**
     * @param $value
     *
     * @return $this
     */
    public function setReturnUrl($value)
    {
        return $this->setParameter('return_url', $value);
    }
    /**
     * @return mixed
     */
    public function getEncryptKey()
    {
        return $this->encryptKey;
    }
    /**
     * @param $value
     *
     * @return $this
     */
    public function setEncryptKey($value)
    {
        $this->encryptKey = $value;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->getParameter('version');
    }
    /**
     * @param $value
     *
     * @return $this
     */
    public function setVersion($value)
    {
        return $this->setParameter('version', $value);
    }
    public function validateBizContent()
    {
        $data = $this->getBizContent();
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        foreach (func_get_args() as $key) {
            if (!array_has($data, $key)) {
                throw new \Omnipay\Common\Exception\InvalidRequestException("The biz_content {$key} parameter is required");
            }
        }
    }
    public function validateBizContentOne()
    {
        $data = $this->getBizContent();
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        $keys = func_get_args();
        $allEmpty = true;
        foreach ($keys as $key) {
            if (array_has($data, $key)) {
                $allEmpty = false;
                break;
            }
        }
        if ($allEmpty) {
            throw new \Omnipay\Common\Exception\InvalidRequestException(sprintf('The biz_content (%s) parameter must provide one at least', implode(',', $keys)));
        }
    }
    protected function filter($data)
    {
        if (!$this->returnable) {
            unset($data['return_url']);
        }
        if (!$this->notifiable) {
            unset($data['notify_url']);
        }
    }
    protected function validateOne()
    {
        $keys = func_get_args();
        if ($keys && is_array($keys[0])) {
            $keys = $keys[0];
        }
        $allEmpty = true;
        foreach ($keys as $key) {
            $value = $this->parameters->get($key);
            if (!empty($value)) {
                $allEmpty = false;
                break;
            }
        }
        if ($allEmpty) {
            throw new \Omnipay\Common\Exception\InvalidRequestException(sprintf('The parameters (%s) must provide one at least', implode(',', $keys)));
        }
    }
}