{*
* 2015-2016 YDRAL.COM
*
* NOTICE OF LICENSE
*
*  @author YDRAL.COM <info@ydral.com>
*  @copyright 2015-2016 YDRAL.COM
*  @license GNU General Public License version 2
*
* You can not resell or redistribute this software.
*}

<h2>Nº CLIENTE: {$correos_config.client_number|escape:'htmlall':'UTF-8'}, Nº CONTRATO: {$correos_config.contract_number|escape:'htmlall':'UTF-8'}</h2><br><br>
**************************************************************************<br><br>
Datos de Recogida<br><br>
Teléfono: {$collection_req_phone|escape:'htmlall':'UTF-8'}/{$collection_req_mobile_phone|escape:'htmlall':'UTF-8'}<br/>
Nombre: {$collection_req_name|escape:'htmlall':'UTF-8'}<br/>
Calle: {$collection_req_address|escape:'htmlall':'UTF-8'}<br/>
CP: {$collection_req_postalcode|escape:'htmlall':'UTF-8'}<br/>
Población: {$collection_req_city|escape:'htmlall':'UTF-8'}<br/>
Provincia: {$collection_req_state|escape:'htmlall':'UTF-8'}<br/><br/>
Número de Bulto(s): {$collection_req_pieces|escape:'htmlall':'UTF-8'}<br>
Fecha de recogida: {$collection_req_date|escape:'htmlall':'UTF-8'}<br>
Horario: {$collection_req_time|escape:'htmlall':'UTF-8'}<br>
Observaciones: {$collection_req_comments|escape:'htmlall':'UTF-8'}<br><br>
Código de envío: {$shipping_codes|escape:'htmlall':'UTF-8'}