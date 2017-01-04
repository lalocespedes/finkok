<?php

namespace lalocespedes;

use SoapClient;

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

        $soap = new SoapClient("{$this->url}stamp.wsdl", [
            'trace' => 1
        ]);

        $params = array(
            "xml" => $xml,
            "username" => $this->username,
            "password" => $this->password
        );

        $response = $soap->__soapCall('stamp', array($params));

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

        if(isset($response->stampResult->Incidencias->Incidencia->CodigoError) && $response->stampResult->Incidencias->Incidencia->CodigoError == 307) {

            $response = $client->__soapCall('quick_stamp', array($params));

            $this->errors = [
                "error" => $response->quick_stampResult->xml
            ];

            $this->response = $response->quick_stampResult->xml;

        }

        return $this;
    }

    public function getResponse()
    {
        return $this->response;
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
