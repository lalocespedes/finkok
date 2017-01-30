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

    public function Timbrar($xml = null)
    {
        $this->xml = $xml;

        if(is_null($xml)) {

            $this->errors = [
                "Falta parametro xml"
            ]; 
            
            $this->valid = false;
            return false;
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

        if (!isset($this->response->stampResult->UUID)) {
            
            if($this->response->stampResult->Incidencias->Incidencia->CodigoError) {

                $this->errors = [
                    "message" => $this->response->stampResult->Incidencias->Incidencia->MensajeIncidencia
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

            return true;
        }

        return false;
    }
}
