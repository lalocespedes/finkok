<?php

namespace lalocespedes\Finkok;

use SoapClient;
use SimpleXMLElement;
use Exception;

/**
 * 
 */
class Cfdi extends \lalocespedes\Finkok\Finkok
{
    protected $xml;
    protected $valid = false;
    protected $errors = [];

    public function __construct () {
        
    }

    public function Timbrar($xml = null)
    {
        $this->xml = $xml;

        if(is_null($xml)) {

            $this->errors = [
                "Falta parametro xml"
            ]; 
            
            return $this;
        }

        if(is_null($this->username) || is_null($this->password)) {

            $this->errors = [
                "please setCredentials node"
            ];
            
            return $this;
        }

        $soap = new SoapClient("{$this->url}stamp.wsdl", [
            'trace' => 1
        ]);

        $this->response = $soap->__soapCall('stamp', [
            [
                "xml" => $xml,
                "username" => $this->username,
                "password" => $this->password
            ]
        ]);

        if($this->previo()) {

            return $this->response;

        }

        if (isset($this->response->stampResult->Incidencias)) {

            foreach($this->response->stampResult->Incidencias->Incidencia as $error) {

                array_push($this->errors, $error->MensajeIncidencia);

            }

            $this->response = $xml;
            return $this;
        }
        
        $this->valid = true;
        return $this;
    }

    public function previo()
    {
        // Check CFDI contiene un timbre previo
        if(isset($this->response->stampResult->Incidencias->Incidencia->CodigoError) && $this->response->stampResult->Incidencias->Incidencia->CodigoError == 307) {

            $soap = new SoapClient("{$this->url}stamp.wsdl", [
                'trace' => 1
            ]);

            $response = $soap->__soapCall('quick_stamp', [
                [
                    "xml" => $this->xml,
                    "username" => $this->username,
                    "password" => $this->password
                ]
            ]);

            $this->response = $response->quick_stampResult;

            $this->valid = true;
            return true;
        }

        return false;
    }
}
