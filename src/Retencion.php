<?php

namespace lalocespedes\Finkok;

use SoapClient;
use SimpleXMLElement;
use Exception;

/**
 * 
 */
class Retencion extends \lalocespedes\Finkok\Finkok
{
    protected $xml;
    protected $valid = false;
    protected $previamente_timbrado = false;
    protected $errors = [];

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

        $soap = new SoapClient("{$this->url}retentions.wsdl", [
            'trace' => 1
        ]);

        $response = $soap->__soapCall('stamp', [
            [
                "xml" => $xml,
                "username" => $this->username,
                "password" => $this->password
            ]
        ]);

        // Check CFDI contiene un timbre previo
        if(property_exists($response->stampResult->Incidencias, 'Incidencia') && $response->stampResult->Incidencias->Incidencia->CodigoError == 307) {

            // Get previous cfdi

            $soap = new SoapClient("{$this->url}retentions.wsdl", [
                'trace' => 1
            ]);
            $response = $soap->__soapCall('stamped', [
                [
                    "xml" => $this->xml,
                    "username" => $this->username,
                    "password" => $this->password
                ]
            ]);

            $this->response = $response->stampedResult;
            $this->previamente_timbrado = true;
            $this->valid = true;
            return $this;

        }

        if (property_exists($response->stampResult->Incidencias, 'Incidencia') && !empty($response->stampResult->Incidencias)) {

            if(is_array($response->stampResult->Incidencias)) {

                foreach($response->stampResult->Incidencias->Incidencia->MensajeIncidencia as $error) {

                    array_push($this->errors, $error->MensajeIncidencia);

                }
            } else {

                array_push($this->errors, $response->stampResult->Incidencias->Incidencia->MensajeIncidencia);
            }

            $this->response = $xml;
            return $this;
        }
        
        $this->response = $response->stampResult;
        
        $this->valid = true;
        return $this;

    }
}
