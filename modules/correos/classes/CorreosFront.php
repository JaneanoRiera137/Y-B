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

class CorreosFront
{
    public static function updateOfficeInfo($params)
    {
        
        $data = array(
                "id_collection_office" => $params['selected_office'],
                "mobile" => array("number" => str_replace("'", "", $params['mobile']), "lang" => $params['lang']),
                "email" => str_replace("'", "\'", $params['email']),
                "offices" => $params['offices'],
            );

        Db::getInstance()->Execute(
            "INSERT INTO `"._DB_PREFIX_."correos_request` (`type`, `id_cart`, `id_order`, `id_carrier`, `reference`, `data`) 
            VALUES ('quote', ".(int) $params['id_cart'].", 0, ".(int) $params['id_carrier'].", 
            '".pSQL($params['postcode'])."', '".pSQL(Tools::jsonEncode($data))."') 
            ON DUPLICATE KEY UPDATE data = '".pSQL(Tools::jsonEncode($data))."'"
        );
    }
    public static function updateHoursSelect($params)
    {
    
        $data = array("id_schedule" => $params['id_schedule']);
        Db::getInstance()->Execute(
            "INSERT INTO `"._DB_PREFIX_."correos_request` (`type`, `id_cart`, `id_order`, `id_carrier`, `data`) 
            VALUES ('quote', ".(int) $params['id_cart'].", 0, ".(int) $params['id_carrier'].", '".pSQL(Tools::jsonEncode($data))."') 
            ON DUPLICATE KEY UPDATE data = '".pSQL(Tools::jsonEncode($data))."'"
        );

    }
    public static function updateInternationalMobile($params)
    {
    
        $data = array("mobile" => $params['mobile']);
        Db::getInstance()->Execute(
            "INSERT INTO `"._DB_PREFIX_."correos_request` (`type`, `id_cart`, `id_order`, `id_carrier`, `data`) 
            VALUES ('quote', ".(int) $params['id_cart'].", 0, ".(int) $params['id_carrier'].", '".pSQL(Tools::jsonEncode($data))."') 
            ON DUPLICATE KEY UPDATE data = '".pSQL(Tools::jsonEncode($data))."'"
        );

    }
    public static function updatePaq($params)
    {
        $request = Db::getInstance()->getValue(
            "SELECT `data` FROM `"._DB_PREFIX_."correos_request` 
            WHERE `type` = 'quote' AND `id_cart` = ".(int) $params['id_cart']." AND id_carrier = ".(int) $params['id_carrier']." 
            ORDER BY `date` DESC"
        );

        if ($request) {
            $data = Tools::jsonDecode($request);
            $data->homepaq_code = $params['selectedpaq_code'];
            $data->mobile = array("number" => str_replace("'", "\'", $params['mobile']), "lang" => 1);
            $data->email = str_replace("'", "\'", $params['email']);
   
            Db::getInstance()->Execute(
                "UPDATE `"._DB_PREFIX_."correos_request` 
                SET `data` = '".pSQL(Tools::jsonEncode($data))."' 
                WHERE `type` = 'quote' AND `id_cart` = ".(int) $params['id_cart']." AND `id_carrier` = ".(int) $params['id_carrier']
            );
        }

        
    }
}
