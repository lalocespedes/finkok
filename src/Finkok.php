<?php

namespace lalocespedes;

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

    public function setCredentials($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->url = "http://demo-facturacion.finkok.com/servicios/soap/";

    }

    public function Timbrar($xml= null)
    {

        if(is_null($xml)) {

            $this->errors = [
                "Falta parametro xml"
            ]; 
            
            $this->valid = false;
            return $this;

        }
        
        if(is_null($this->username) || is_null($this->password)) {

            $this->errors = [
                "please setCredentials node"
            ]; 
            
            $this->valid = false;
            return $this;

        }

        $parse = new SimpleXMLElement($xml);
        $ns = $parse->getNamespaces(true);
        $parse->registerXPathNamespace("c", $ns['cfdi']);
        $emisor = $parse->xpath("//c:Emisor");

        $isClientRegister = $this->getClient($emisor[0]['rfc']);

        if(!isset($isClientRegister->users->ResellerUser->status)) {

            $this->createNewClient($emisor[0]['rfc']);

        }

        if(($isClientRegister->users->ResellerUser->status == "S")) {

                $this->errors = [
                    "message" => "cuenta suspendida"
                ];

                $this->valid = false;
                $this->response = $xml;
                return $this;

        }

        $soap = new SoapClient("{$this->url}stamp.wsdl", [
            'trace' => 1
        ]);

        $response = $soap->__soapCall('stamp', [
            [
                "xml" => $xml,
                "username" => $this->username,
                "password" => $this->password
            ]
        ]);

        if (!isset($response->stampResult->UUID)) {

            if($response->stampResult->Incidencias->Incidencia->CodigoError) {

                $this->errors = [
                    "message" => $response->stampResult->Incidencias->Incidencia->MensajeIncidencia
                ];

                $this->valid = false;
                $this->response = $xml;
                return $this;

            }

            foreach($response->stampResult->Incidencias->Incidencia as $error) {

                array_push($this->errors, $error->MensajeIncidencia);

                $this->valid = false;
                $this->response = $xml;
                return $this;

            }

        }

        // Check CFDI contiene un timbre previo
        if(isset($response->stampResult->Incidencias->Incidencia->CodigoError) && $response->stampResult->Incidencias->Incidencia->CodigoError == 307) {

            $response = $soap->__soapCall('quick_stamp', [
                [
                    "xml" => $xml,
                    "username" => $this->username,
                    "password" => $this->password
                ]
            ]);

            $this->response = $response->quick_stampResult->xml;

        }

        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getClient($rfc= null)
    {
        if(is_null($rfc)) {

            return "falta parametro rfc";

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

            return "falta parametro rfc";

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
