<?php

namespace lalocespedes\Finkok;

use SoapClient;
use SimpleXMLElement;
use Exception;

/**
 *
 */
class Finkok
{
    /**
    * @var array
    */
    protected $errors = [];

    /**
    * @var bool
    */
    protected $valid = true;

    protected $response;
    protected $url;
    protected $username;
    protected $password;
    protected $client_active = false;
    protected $previamente_timbrado = false;

    function __construct() {
        $this->username = getenv('FINKOK_USERNAME');
        $this->password = getenv('FINKOK_PASSWORD');
        $this->url = getenv('FINKOK_URL_TIMBRADO');
    }

    public function setCredentials($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->url = getenv('FINKOK_URL_TIMBRADO');

    }

    public function setEmisor($rfc = null)
    {

        if(is_null($rfc) || empty($rfc)) {

            throw new Exception('Falta parametro rfc cliente');

        }

        $isClientRegister = $this->getClient($rfc);

        if(property_exists($isClientRegister, 'users') && !count(get_object_vars($isClientRegister->users))) {

            $this->createNewClient($rfc);
            $this->client_active = true;
            return $this;
        }

        if(($isClientRegister->users->ResellerUser->status == "S")) {

            $this->errors = [
                "message" => "cuenta suspendida"
            ];

            $this->valid = false;
            $this->response = $xml;
            return $this;
        }

        $this->client_active = true;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getClient($rfc= null)
    {
        if(is_null($rfc)) {

            throw new Exception('Falta parametro rfc cliente');

        }

        $soap = new SoapClient("{$this->url}registration.wsdl");

        $response = $soap->__soapCall("get", [
                [
                    "reseller_username" => $this->username,
                    "reseller_password" => $this->password,
                    "taxpayer_id" => $rfc
                ]
        ]);

        if(property_exists($response->getResult, 'message') && $response->getResult->message == "Cuenta Suspendida") {

            throw new Exception('Cuenta Suspendida');

        }

        return $response->getResult;
    }

    public function createNewClient($rfc= null)
    {
        if(is_null($rfc)) {

            throw new Exception('Falta parametro rfc cliente');

        }

        $soap = new SoapClient("{$this->url}registration.wsdl", [
            'trace' => 1
            ]);

        $response = $soap->__soapCall('add', [
            [
                "reseller_username" => $this->username,
                "reseller_password" => $this->password,
                "taxpayer_id" => $rfc
            ]
        ]);

        if ($response->addResult->success) {
            return $response->addResult->message;
        }

        return false;
    }

    public function failed()
    {
        return !empty($this->errors);
    }

    public function errors()
    {
        return $this->errors;
    }

    public function previamente_timbrado()
    {
        return $this->previamente_timbrado;
    }
}
