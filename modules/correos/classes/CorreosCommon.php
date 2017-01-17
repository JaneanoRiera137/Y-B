<?php
/**
* 2015-2016 YDRAL.COM
*
* NOTICE OF LICENSE
*
*  @author    YDRAL.COM <info@ydral.com>
*  @copyright 2015-2016 YDRAL.COM
*  @license   GNU General Public License version 2
*
* You can not resell or redistribute this software.
*/

if (!defined('_PS_VERSION_')) {
    exit;
}
   
class CorreosCommon
{
    public static function getCorreosConfiguration($key = false)
    {
        if ($key) {
            return Configuration::get('CORREOS_' . Tools::strtoupper($key));
        }
         
        $config = array(
          'production_environment' => 1,
          'bank_account_number' => Configuration::get('CORREOS_BANK_ACCOUNT_NUMBER'),
          'client_number' => Configuration::get('CORREOS_CLIENT_NUMBER'),
          'contract_number' => Configuration::get('CORREOS_CONTRACT_NUMBER'),
          'correos_key' => Configuration::get('CORREOS_KEY'),
          'correos_password' => Configuration::get('CORREOS_PASSWORD'),
          'correos_user' => Configuration::get('CORREOS_USER'),
          'customs_zone' => Configuration::get('CORREOS_CUSTOMS_ZONE'),
          'show_config' => Configuration::get('CORREOS_SHOW_CONFIG') ? 1 : 0,
          'insurance' => Configuration::get('CORREOS_INSURANCE'),
          'insurance_value' => Configuration::get('CORREOS_INSURANCE_VALUE'),
          'mails_inquiry' => Configuration::get('CORREOS_MAILS_INQUIRY'),
          'mail_collection_cc' => Configuration::get('CORREOS_MAIL_COLLECTION_CC'),
          'order_states' => Configuration::get('CORREOS_ORDER_STATES'),
          'presentation_mode' => Configuration::get('CORREOS_PRESENTATION_MODE') ?
              Configuration::get('CORREOS_PRESENTATION_MODE') : 'standard',
          'S0236_enabletimeselect' => Configuration::get('CORREOS_S0236_ENABLETIMESELECT') ?
              Configuration::get('CORREOS_S0236_ENABLETIMESELECT') : '0',
        );
      
        $sql = 'SELECT * FROM '._DB_PREFIX_.'correos_configuration';
        if ($results = Db::getInstance()->ExecuteS($sql)) {
            foreach ($results as $row) {
                $config[$row['name']] = $row['value'];
            }
        }
         
        if (Configuration::get('CORREOS_SENDERS')) {
            $senders = Configuration::get('CORREOS_SENDERS');
            if (_PS_MAGIC_QUOTES_GPC_) {
                $senders = str_replace("\u00", "u00", $senders); //clear slashes after post
                $senders = str_replace("u00", "\u00", $senders);
            }
            $senders = Tools::jsonDecode($senders);
            
            for ($i = 0; $i < count($senders); ++$i) {
                
                $config['sender_' . ($i+1)] = Tools::jsonEncode($senders[$i]);
            }
        }
        return $config;
    }
    public static function getCarriers($instaled = true, $where = '', $order_by = '')
    {
        $sql = "SELECT `code`, `title`, `delay`, `id_reference` FROM `"._DB_PREFIX_."correos_carrier` cc WHERE 1 ";
         
        if ($instaled) {
            $sql .= " AND `id_reference` <> 0";
        }
         
        if ($where != '') {
            $sql .= " AND ".$where;
        }
         
        if ($order_by != '') {
            $sql .= " ORDER BY ".$order_by;
        }
        $correos_carriers = Db::getInstance()->executeS($sql);
        if (count($correos_carriers) == 0) {
            return false;
        } elseif (count($correos_carriers) == 1) {
            return $correos_carriers[0];
        } else {
            return $correos_carriers;
        }
    }
    public static function getActiveCarriers()
    {
        $sql = "SELECT pc.`id_carrier`, cc.`code` 
         FROM `"._DB_PREFIX_."carrier` pc 
         INNER JOIN `"._DB_PREFIX_."correos_carrier` cc ON pc.`id_reference` = cc.`id_reference`
         WHERE pc.`active` = 1 AND pc.`deleted` = 0";
            
        $correos_carriers = Db::getInstance()->executeS($sql);

        if (count($correos_carriers) == 0) {
            return false;
        }
        
        return $correos_carriers;
        
    }
    public static function getActiveCarriersByGroup()
    {
        $carriers = array(
          'carriers_ids'             => array(),
          'carriers_office'          => array(),
          'carriers_homepaq'         => array(),
          'carriers_citypaq'         => array(),
          'carriers_hourselect'      => array(),
          'carriers_international'   => array()
        );
      
         
        $correos_carriers = self::getActiveCarriers();
        
        if (!$correos_carriers) {
            return $carriers;
        }
      
        $correos = new Correos();
  
        foreach ($correos_carriers as $carrier) {
            $carriers['carriers_ids'][] = $carrier['id_carrier'];
     
            if (in_array($carrier['code'], $correos->carriers_codes_office)) {
                $carriers['carriers_office'][] = $carrier['id_carrier'];
            } elseif (in_array($carrier['code'], $correos->carriers_codes_homepaq)) {
                $carriers['carriers_homepaq'][] = $carrier['id_carrier'];
            } elseif (in_array($carrier['code'], $correos->carriers_codes_citypaq)) {
                $carriers['carriers_citypaq'][] = $carrier['id_carrier'];
            } elseif (in_array($carrier['code'], $correos->carriers_codes_hourselect)) {
                $carriers['carriers_hourselect'][] = $carrier['id_carrier'];
            } elseif (in_array($carrier['code'], $correos->carriers_codes_international)) {
                $carriers['carriers_international'][] = $carrier['id_carrier'];
            }
        }
        return $carriers;
    }
    public static function getOfficies($postcode, $id_carrier)
    {
         
        try {
            $context = Context::getContext();
            $id_cart = $context->cart->id;
            $collection_office = 0;
            $email = "";
            $mobile = "";
            if ($id_cart) {
                $request = Db::getInstance()->getValue(
                    "SELECT `data` FROM `"._DB_PREFIX_."correos_request` 
                    WHERE `id_cart` = ".(int) $id_cart." AND reference = '".pSQL($postcode)."' ORDER BY date DESC"
                );
              
                if ($request) {
                    if (_PS_MAGIC_QUOTES_GPC_) {
                        $request = str_replace("u00", "\u00", $request);
                    }
                    $request = Tools::jsonDecode($request);
                    if (isset($request->id_collection_office)) {
                        $collection_office = $request->id_collection_office;
                    }
                  
                    $offices = $request->offices;
                    
                    for ($i=0; $i < count($offices); $i++) {
                        $offices[$i]->selected = 0;
                        if ($offices[$i]->unidad == $collection_office) {
                            $offices[$i]->selected = 1;
                        }
                    }
                    if (count($offices)) {
                        return Tools::jsonEncode($offices);
                    }
                }
               
                $cart = new Cart($id_cart);
                $address = new Address($cart->id_address_delivery);
                $mobile = $address->phone_mobile;
                $customer = new Customer($cart->id_customer);
                $email = $customer->email;
            }
           
            $data = self::getOfficesWs($postcode);
            if (!$data) {
                return "";
            }
            $request_data = array(
               "id_collection_office" => $data[0]['unidad'],
               "mobile" => array("number" => $mobile, "lang" => 1),
               "email" => $email,
               "offices" => $data
            );
            
            Db::getInstance()->Execute(
                "INSERT INTO `"._DB_PREFIX_."correos_request` (`type`, `id_cart`, `id_order`, `id_carrier`, `reference`, `data`) 
                VALUES ('quote', ".(int) $id_cart.", 0, ".(int) $id_carrier.", '".pSQL($postcode)."','".pSQL(Tools::jsonEncode($request_data))."') 
                ON DUPLICATE KEY UPDATE data = '".pSQL(Tools::jsonEncode($request_data))."', reference = '".pSQL($postcode)."'"
            );
                
            return Tools::jsonEncode($data);
        } catch (Exception $e) {
            return $e->getMessage();
        }
         
    }
    public static function getOfficesWs($postcode)
    {
        $context = Context::getContext();
        $context->smarty->assign('postcode', $postcode);
        $xmlSend = $context->smarty->fetch(
            _PS_MODULE_DIR_ . 'correos/views/templates/admin/soap_requests/offices.tpl'
        );
        $data = self::sendXmlCorreos('url_office_locator', $xmlSend);
        if (!strstr(Tools::strtolower($data), 'soapenv:envelope')) {
            return false;
        }
      
        if (self::isValidXml($data)) {
            $dataXml = simplexml_load_string($data, null, null, "http://schemas.xmlsoap.org/soap/envelope/");
            $dataXml->registerXPathNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
            
            $error = $dataXml->xpath('//soapenv:Fault');
            if (!empty($error)) {
                return  false;
            }
         
            $dataXml->registerXPathNamespace('ns', 'http://ejb.mauo.correos.es');
            
            $tmpData = array();
            $tmpData['unidad'] = $dataXml->xpath('//ns:unidad');
            $tmpData['nombre'] = $dataXml->xpath('//ns:nombre');
            $tmpData['direccion'] = $dataXml->xpath('//ns:direccion');
            $tmpData['localidad'] = $dataXml->xpath('//ns:descLocalidad');
            $tmpData['cp'] = $dataXml->xpath('//ns:cp');
            $tmpData['telefono'] = $dataXml->xpath('//ns:telefono');
            $tmpData['horariolv'] = $dataXml->xpath('//ns:horarioLV');
            $tmpData['horarios'] = $dataXml->xpath('//ns:horarioS');
            $tmpData['horariof'] = $dataXml->xpath('//ns:horarioF');
            $tmpData['coorx'] = $dataXml->xpath('//ns:coorXWGS84');
            $tmpData['coory'] = $dataXml->xpath('//ns:coorYWGS84');
            $offices = array();
            for ($i = 0; $i< count($tmpData['unidad']); $i++) {
                foreach ($tmpData as $_data => $value) {
                    $offices[$i][$_data] =  str_replace("'", "", (string) $value[$i]);
                }
            }
            
            if (empty($offices)) {
                return false;
            } else {
                return $offices;
            }
        } else {
            return false;
        }
         
    }
    public static function sendXmlCorreos($url, $xmlSend, $userpwd = false, $SOAPAction = false, $certificate = false, $correos_config = false)
    {
        if (!$correos_config) {
            $correos_config = CorreosCommon::getCorreosConfiguration();
        }
         
         
        if ($correos_config['production_environment'] == 1) {
            $URL_SOAP = $correos_config[$url];
        } else {
            $URL_SOAP = $correos_config[$url."_pre"];
        }
      
 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL_SOAP);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlSend);
        if ($SOAPAction) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: text/xml; charset=utf-8",
                "SOAPAction: \"{$SOAPAction}\""
                ));
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8"));
        }
      
        if ($userpwd) {
            curl_setopt($ch, CURLOPT_USERPWD, $correos_config['correos_user'].":".$correos_config['correos_password']);
        }
        if ($certificate) {
            $certificate_path = ($correos_config['production_environment'] == 1 ?
                "correos_y_telegrafos.cer" : "correos_y_telegrafos_pre.cer");
          //curl_setopt($ch, CURLOPT_CAINFO, getcwd()."/".$certificate_path);
            curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__).'/'.$certificate_path);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
      
        $result = curl_exec($ch);
      
        if ($result === false) {
            $err = 'Curl error: ' . curl_error($ch);
            curl_close($ch);
            print $err;
        } else {
            curl_close($ch);
        }
        return $result;
      
    }
    public static function getCorreosPaqWSService($params)
    {
         
        $request = Db::getInstance()->getValue(
            "SELECT `data` FROM `"._DB_PREFIX_."correos_request` 
            WHERE `type` = 'quote' AND `id_cart` = ".(int)$params['id_cart']." 
            AND `reference` = '".pSQL($params['user'])."' AND id_carrier = ".(int) $params['id_carrier']." 
            ORDER BY date DESC"
        );
        
        if ($request) {
            $request = str_replace("defaultHomepaq", "defaultpaq", $request); //from previous versions
            $request = str_replace("defaultCitypaq", "defaultpaq", $request);
            $request = str_replace("homepaqCode", "code", $request);
            if (_PS_MAGIC_QUOTES_GPC_) {
                $request = str_replace("u00", "\u00", $request);
            }
            $data = Tools::jsonDecode($request);
            if (isset($data->homepaqs) && is_array($data->homepaqs)) {
                return Tools::jsonEncode($data->homepaqs);
            }
        }
        
        $result = self::getPaqsWs($params);
        if (isset($result['errorCode'])) {
            return Tools::jsonEncode($result);
        }
         
        $token = $result['token'];
        $correospaqs = $result['correospaqs'];
        if (count($correospaqs) > 0) {
            $index = 0;
            for ($i = 0; $i < count($correospaqs); ++$i) {
                if (isset($correospaqs[$i]['defaultpaq']) && $correospaqs[$i]['defaultpaq'] == "true") {
                    $index = $i;
                }
            }
            
            $request_data = array(
            "homepaq_code" => $correospaqs[$index]['code'],
            "token" => $token,
            "mobile" => array("number" => str_replace("'", "\'", $params['paq_mobile']), "lang" => 1),
            "homepaqs" => $correospaqs
            );
        } else {
            $request_data = array(
            "homepaq_code" => "",
            "token" => $token,
            "mobile" => array("number" => str_replace("'", "\'", $params['paq_mobile']), "lang" => 1),
            "homepaqs" => $correospaqs
            );
        }
        if (isset($params['paqtype']) && $params['paqtype'] == 'citypaqs') {
            //get homepaq_user from previous request
            $request = Db::getInstance()->getValue(
                "SELECT `data` FROM `"._DB_PREFIX_."correos_request` 
                WHERE `type` = 'quote' AND `id_cart` = ".(int)$params['id_cart']." 
                AND id_carrier = ".(int) $params['id_carrier']." 
                ORDER BY date DESC"
            );
            if (_PS_MAGIC_QUOTES_GPC_) {
                $request = str_replace("u00", "\u00", $request);
            }
            $data = Tools::jsonDecode($request);
            if (isset($data->homepaq_user)) {
                $request_data['homepaq_user'] = $data->homepaq_user;
            }
            
        } else {
            if (isset($params['user'])) {
                $request_data['homepaq_user'] = $params['user'];
            }
        }
        Db::getInstance()->Execute(
            "INSERT INTO `"._DB_PREFIX_."correos_request` (`type`, `id_cart`, `id_order`, `id_carrier`, `reference`, `data`) VALUES 
            ('quote', ".(int) $params['id_cart'].", 0, ".(int)$params['id_carrier'].", 
            '".pSQL($params['user'])."','".pSQL(Tools::jsonEncode($request_data))."') 
            ON DUPLICATE KEY UPDATE data = '".pSQL(Tools::jsonEncode($request_data))."', reference = '".pSQL($params['user'])."'"
        );
         
        return Tools::jsonEncode($correospaqs);
    }
      
      
    public static function getPaqsWs($params)
    {
        $correos = new Correos();
        $context = Context::getContext();
        $context->smarty->assign(array(
            "params"    => $params,
            "ip"        => $_SERVER['REMOTE_ADDR']
        ));
        $xmlSend = $context->smarty->fetch(
            _PS_MODULE_DIR_ . 'correos/views/templates/admin/soap_requests/paqs.tpl'
        );
        $result = self::sendXmlCorreos("url_servicepaq", $xmlSend, true, false, true);
         
        $result = str_replace("defaultHomepaq", "defaultpaq", $result);
        $result = str_replace("defaultCitypaq", "defaultpaq", $result);
        $result = str_replace("homepaqCode", "code", $result);
         
        if (!strstr(Tools::strtolower($result), 'soapenv:envelope')) {
            return array(
                'description' => $correos->l('We are sorry, the service is temporary unavailable. Please try later'),
                'errorCode' => "0",
                'url' => ""
            );
        }
        if (self::isValidXml($result)) {
             $dataXml = simplexml_load_string($result, null, null, "http://schemas.xmlsoap.org/soap/envelope/");
             $dataXml->registerXPathNamespace('n', 'http://jaxws.ws.paq.correos.es/');
             $error = $dataXml->xpath('//faultcode');
            
        
            if (!empty($error)) {
                $faultcode = $dataXml->xpath('//faultcode');
                $errorCode = $dataXml->xpath('//errorCode');
                $description = $dataXml->xpath('//description');
                $faultstring = $dataXml->xpath('//faultstring');
                if (!$errorCode) {
                    return array(
                        'description' => (string)$faultstring[0],
                        'errorCode' => (string)$faultcode[0],
                        'url' => ""
                    );
                }
                $description = (string)$description[0];
                $url = $dataXml->xpath('//url');
                $correos = new Correos();
                if ((string)$errorCode[0] == "1000") {
                    $description = $correos->l('User no valid');
                }
               
                if (!empty($errorCode) && !empty($description) && !empty($url)) {
                    return array(
                        'description' => $description,
                        'errorCode' => (string)$errorCode[0],
                        'url' => (string)$url[0]
                    );
                } else {
                    return array(
                        'description' => $correos->l(
                            'We are sorry, the service is temporary unavailable. Please try later'
                        ),
                        'errorCode' => "0",
                        'url' => ""
                    );
                }
            } else {
                $data = Tools::jsonDecode(Tools::jsonEncode($dataXml->xpath('//return')), true);
                $token = isset($data[0]['token']) ? $data[0]['token'] : "";
                $_tmpData = array();
                $_correospaqs = array();
                $_tmpData['code'] = $dataXml->xpath('//code');
                $_tmpData['alias'] = $dataXml->xpath('//alias');
                $_tmpData['postalCode'] = $dataXml->xpath('//postalCode');
                $_tmpData['admissionType'] = $dataXml->xpath('//admissionType');
                $_tmpData['streetType'] = $dataXml->xpath('//streetType');
                $_tmpData['address'] = $dataXml->xpath('//address');
                $_tmpData['schedule'] = $dataXml->xpath('//schedule');
                $_tmpData['latitude_wgs84'] = $dataXml->xpath('//latitude_wgs84');
                $_tmpData['longitude_wgs84'] = $dataXml->xpath('//longitude_wgs84');
                $_tmpData['city'] = $dataXml->xpath('//city');
                $_tmpData['state'] = $dataXml->xpath('//state');
                $_tmpData['block'] = $dataXml->xpath('//block');
                $_tmpData['defaultpaq'] = $dataXml->xpath('//defaultpaq');
                $_tmpData['door'] = $dataXml->xpath('//door');
                $_tmpData['floor'] = $dataXml->xpath('//floor');
                $_tmpData['number'] = $dataXml->xpath('//number');
               
                for ($index = 0; $index< count($_tmpData['code']); $index++) {
                    foreach ($_tmpData as $_data => $_value) {
                        if (isset($_value[$index])) {
                            $_correospaqs[$index][$_data] =  str_replace("'", "", (string)$_value[$index]);
                        } else {
                            $_correospaqs[$index][$_data] = "";
                        }
                    }
                }
                return array("token" => $token, "correospaqs" => $_correospaqs);
            }
        } else {
            return array(
                "errorCode" => "0",
                "description" => "CorreosPaq Web Service Error:\n".strip_tags($result),
                "url" => ""
            );
        }
    }
    public static function getStatesWithCitypaq()
    {
        $context = Context::getContext();
        $xmlSend = $context->smarty->fetch(
            _PS_MODULE_DIR_ . 'correos/views/templates/admin/soap_requests/citypaq_states.tpl'
        );
        $result = self::sendXmlCorreos("url_servicepaq", $xmlSend, true, false, true);
        if (self::isValidXml($result)) {
            $dataXml = simplexml_load_string($result, null, null, "http://schemas.xmlsoap.org/soap/envelope/");
            $dataXml->registerXPathNamespace('n', 'http://jaxws.ws.paq.correos.es/');
            $states = Tools::jsonDecode(Tools::jsonEncode($dataXml->xpath('//state')), true);
            return Tools::jsonEncode($states);
        } else {
            return false;
        }
    }
    public static function addCityPaqtofavorites($params)
    {
        $context = Context::getContext();
        $context->smarty->assign(array(
            "params"        => $params,
            "url_callback"  => $context->shop->getBaseURL().'modules/correos/correospaq_favoritiescallback.html'
        ));
        $xmlSend = $context->smarty->fetch(
            _PS_MODULE_DIR_ . 'correos/views/templates/admin/soap_requests/citypaq_add_to_favorites.tpl'
        );
    
        $result = self::sendXmlCorreos("url_servicepaq", $xmlSend, true, false, true);
      
        if (self::isValidXml($result)) {
            $dataXml = simplexml_load_string($result, null, null, "http://schemas.xmlsoap.org/soap/envelope/");
            $dataXml->registerXPathNamespace('n', 'http://jaxws.ws.paq.correos.es/');
            $url = $dataXml->xpath('//url');
      
            return (string)$url[0];
        } else {
            return false;
        }
    }
    public static function isValidXml($xml)
    {
        try {
            if (empty($xml)) {
                return false;
            }
      
            libxml_use_internal_errors(true);
            $doc = new DOMDocument('1.0', 'utf-8');
      
            $doc->loadXML($xml);
      
            $errors = libxml_get_errors();
      
            return empty($errors);
        } catch (Exception $e) {
            return false;
        }
      
      
    }
}
