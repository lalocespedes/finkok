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
    protected $previamente_timbrado = false;
    protected $errors = [];

    public function Timbrar($xml = null)
    {
        $this->xml = $xml;

        if (is_null($xml)) {
            $this->errors = [
                "Falta parametro xml"
            ];

            return $this;
        }

        if (is_null($this->username) || is_null($this->password)) {
            $this->errors = [
                "please setCredentials node"
            ];

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

        // Check CFDI contiene un timbre previo
        if (property_exists($response->stampResult->Incidencias, 'Incidencia') && $response->stampResult->Incidencias->Incidencia->CodigoError == 307) {
            $this->response = $response->stampResult;
            $this->previamente_timbrado = true;
            $this->valid = true;
            return $this;
        }

        if (property_exists($response->stampResult->Incidencias, 'Incidencia') && !empty($response->stampResult->Incidencias)) {
            if (is_array($response->stampResult->Incidencias)) {
                foreach ($response->stampResult->Incidencias->Incidencia->MensajeIncidencia as $error) {
                    array_push($this->errors, $error->MensajeIncidencia);
                }
            } else {
                $this->errors = [
                    "message" => "Respuesta PAC " . $response->stampResult->Incidencias->Incidencia->MensajeIncidencia
                ];
            }

            $this->response = $xml;
            return $this;
        }

        $this->response = $response->stampResult;

        $this->valid = true;
        return $this;
    }

    public function Cancelar(array $uuids, $rfc, $cerpem, $keypem)
    {
        $soap = new SoapClient("{$this->url}cancel.wsdl", ['trace' => 1]);

        $response = $soap->__soapCall("cancel", [
            [
                "UUIDS" => [
                    'uuids' => $uuids
                ],
                "username" => getenv('FINKOK_USER'),
                "password" => getenv('FINKOK_PASSWORD'),
                "taxpayer_id" => $rfc,
                "cer" => $cerpem,
                "key" => $keypem
            ]
        ]);

        if (isset($response->cancelResult->Acuse)) {
            return $response->cancelResult->Acuse;
        }

        return false;
    }

    public function Recuperar(string $uuid, string $rfc)
    {
        if (is_null($this->username) || is_null($this->password)) {
            $this->errors = ["please setCredentials node"];
            $this->valid = false;
            return $this;
        }

        if (is_null($uuid)) {
            $this->errors = ["Falta parametro uuid"];
            $this->valid = false;
            return $this;
        }

        $soap = new SoapClient("{$this->url}utilities.wsdl", ['trace' => 1]);

        $response = $soap->__soapCall("get_xml", [[
            "uuid" => $uuid,
            "username" => $this->username,
            "password" => $this->password,
            "taxpayer_id" => $rfc
        ]]);

        return $response->get_xmlResult->xml;
    }
}
