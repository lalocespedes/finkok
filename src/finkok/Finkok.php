<?php

namespace lalocespedes\finkok;

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

    public function setCredentials($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->url = "http://demo-facturacion.finkok.com/servicios/soap/";

    }

    public function setClient($rfc = null)
    {

        if(is_null($rfc)) {

            throw new Exception('Falta parametro rfc cliente');

        }

        $isClientRegister = $this->getClient($rfc);

        if(!isset($isClientRegister->users->ResellerUser->status)) {

            $this->createNewClient($rfc);

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

    public function Cancelar(array $uuids, $rfc)
    {
        if(is_null($this->username) || is_null($this->password)) {

            $this->errors = [
                "please setCredentials node"
            ]; 
            
            $this->valid = false;
            return $this;

        }

        if(is_null($xml)) {

            $this->errors = [
                "Falta parametro xml"
            ]; 
            
            $this->valid = false;
            return $this;

        }

        $soap = new SoapClient("{$this->url}cancel.wsdl", [
            'trace' => 1
            ]);

        $response = $soap->__soapCall("cancel", [
            [
                "UUIDS" => [
                    'uuids' => $uuids
                ],
                "username" => $this->username,
                "password" => $this->password,
                "taxpayer_id" => $rfc,
                "cer" => $csd->getCerPem(),
                "key" => $csd->getKeyPem()
            ]
        ]);

        if(isset($response->cancelResult->Acuse)) {

            $file = new XmlSave();
            $file->save($UUID.'-cancel.xml', $response->cancelResult->Acuse);

            return $response->cancelResult->CodEstatus;
        }
        

    }

    public function Recuperar(string $uuid= null, $rfc)
    {
        if(is_null($this->username) || is_null($this->password)) {

            $this->errors = [
                "please setCredentials node"
            ]; 
            
            $this->valid = false;
            return $this;

        }

        if(is_null($uuid)) {

            $this->errors = [
                "Falta parametro uuid"
            ]; 
            
            $this->valid = false;
            return $this;

        }

        $soap = new SoapClient("{$this->url}utilities.wsdl", [
            'trace' => 1
            ]);

        $response = $soap->__soapCall("get_xml", [
            "uuid" => $uuid,
            "username" => $this->username,
            "password" => $this->password,
            "taxpayer_id" => $rfc
        ]);

        return $response->get_xmlResult->xml;
        
    }

    public function failed()
    {
        return !empty($this->errors);
    }

    public function errors()
    {
        return $this->errors;
    }

}
