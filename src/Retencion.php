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
        if(isset($response->stampResult->Incidencias->Incidencia->CodigoError) && $response->stampResult->Incidencias->Incidencia->CodigoError == 307) {

            $this->previo();
            return $this;

        }

        if (isset($response->stampResult->Incidencias)) {
            
            foreach($response->stampResult->Incidencias->Incidencia as $error) {

                array_push($this->errors, $error->MensajeIncidencia);

            }

            $this->response = $response->stampResult;
            return $this;
        }

        $this->response = $response->stampResult;

        $this->valid = true;
        return $this;
    }

    public function previo()
    {
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

        return $this->response = $response->quick_stampResult;
    }
}
