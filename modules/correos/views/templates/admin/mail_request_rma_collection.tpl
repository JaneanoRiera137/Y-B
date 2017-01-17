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
Datos de Recogida RMA<br><br>
Teléfono: {if isset($smarty.post.collection_phone)}{$smarty.post.collection_phone|escape:'htmlall':'UTF-8'}{/if}/
{if isset($smarty.post.collection_mobile_phone)}{$smarty.post.collection_mobile_phone|escape:'htmlall':'UTF-8'}{/if}<br/>
Nombre: {if isset($smarty.post.collection_clientname)}{$smarty.post.collection_clientname|escape:'htmlall':'UTF-8'}{/if}<br/>
Calle: {if isset($smarty.post.collection_address)}{$smarty.post.collection_address|escape:'htmlall':'UTF-8'}{/if}<br/>
CP: {if isset($smarty.post.collection_postalcode)}{$smarty.post.collection_postalcode|escape:'htmlall':'UTF-8'}{/if}<br/>
Población: {if isset($smarty.post.collection_city)}{$smarty.post.collection_city|escape:'htmlall':'UTF-8'}{/if}<br/>
Provincia: {if isset($smarty.post.collection_state)}{$smarty.post.collection_state|escape:'htmlall':'UTF-8'}{/if}<br/><br/>
Número de Bulto(s): {if isset($smarty.post.collection_pieces)}{$smarty.post.collection_pieces|escape:'htmlall':'UTF-8'}{/if}<br>
Servicio: RMA<br>
Fecha de recogida: {if isset($smarty.post.collection_date)}{$smarty.post.collection_date|escape:'htmlall':'UTF-8'}{/if}<br>
Horario: {if isset($smarty.post.collection_time)}{$smarty.post.collection_time|escape:'htmlall':'UTF-8'}{/if}<br>
Observaciones: {if isset($smarty.post.collection_comments)}{$smarty.post.collection_comments|escape:'htmlall':'UTF-8'}{/if}<br>