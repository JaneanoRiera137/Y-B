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

header('Content-Type: application/json');
class AdminCorreosController extends ModuleAdminController
{
    public function ajaxProcessGetPointAdminOrder()
    {
        die(
            CorreosAdmin::getOfficesAdminOrder(
                Tools::getValue('postcode'),
                (int) Tools::getValue('id_carrier'),
                (int) Tools::getValue('id_cart'),
                (int) Tools::getValue('id_order')
            )
        );
    }
    public function ajaxProcessGetPointAdminNewOrder()
    {
        $customer = new Customer((int) Tools::getValue('id_customer'));
        $address = new Address((int) Tools::getValue('id_address_delivery'));
        $offices = CorreosAdmin::getOfficesAdminOrder(
            $address->postcode,
            (int) Tools::getValue('id_carrier'),
            (int) Tools::getValue('id_cart'),
            0
        );
        $data = array(
            'offices' => Tools::jsonDecode($offices),
            'email' => $customer->email,
            'cr_order_state' => Configuration::get('CORREOS_ORDER_STATE_ID'),
            'postcode' => $address->postcode
        );
        die( Tools::jsonEncode($data) );
        
    }
    public function ajaxProcessgetCustomerMailAdmin()
    {
        $customer = new Customer((int) Tools::getValue('id_customer'));
        die(
            Tools::jsonEncode(
                array( 'email' => $customer->email)
            )
        );
    }
    public function ajaxProcessGetPointAdmin()
    {
        $customer = new Customer((int) Tools::getValue('id_customer'));
        $address = new Address((int) Tools::getValue('id_address_delivery'));
        $data = array(
            'offices'           => CorreosCommon::getOfficesWs($address->postcode),
            'email'             => $customer->email,
            'cr_order_state'    => (int) Configuration::get('CORREOS_ORDER_STATE_ID'),
            'postcode'          => $address->postcode
        );
    
        die( Tools::jsonEncode($data) );
    }
    public function ajaxProcessGetPaqsFavourites()
    {
        die(
            Tools::jsonEncode(
                CorreosCommon::getPaqsWs(
                    array(
                        'action' => 'GetCorreosPaqs',
                        'user' => (string) Tools::getValue('homepaq_user')
                    )
                )
            )
        );
    }
    public function ajaxProcessupdateOfficeInfoAdmin()
    {
        CorreosFront::updateOfficeInfo($_POST);
    }
    public function ajaxProcessGetCorreosPaqs()
    {
        die( CorreosCommon::getCorreosPaqWSService($_POST) );
    }
    public function ajaxProcessupdatePaq()
    {
        CorreosFront::updatePaq($_POST);
    }
    public function ajaxProcessgetStatesWithCitypaq()
    {
        die( CorreosCommon::getStatesWithCitypaq() );
    }
    public function ajaxProcessgetCitypaqs()
    {
        die( CorreosCommon::getCorreosPaqWSService($_POST) );
    }
    public function ajaxProcessaddCityPaqtofavorites()
    {
        die(
            Tools::jsonEncode(
                array( 'url' => CorreosCommon::addCityPaqtofavorites($_POST))
            )
        );
    }
}
