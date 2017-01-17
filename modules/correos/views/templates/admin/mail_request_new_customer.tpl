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

<h2>Prestashop Nativo - Solicitud Alta Cliente</h2><br><br>
**************************************************************************<br><br>
Empresa: {if isset($smarty.post.customer_company)}{$smarty.post.customer_company|escape:'htmlall':'UTF-8'}{/if}<br/>
Persona de contacto: {if isset($smarty.post.customer_contact_person)}{$smarty.post.customer_contact_person|escape:'htmlall':'UTF-8'}{/if}<br/>
Provincia: {if isset($smarty.post.customer_state)}{$smarty.post.customer_state|escape:'htmlall':'UTF-8'}{/if}<br/><br/>
Teléfono de contacto: {if isset($smarty.post.customer_phone)}{$smarty.post.customer_phone|escape:'htmlall':'UTF-8'}{/if}<br/>
E-Mail: {if isset($smarty.post.customer_email)}{$smarty.post.customer_email|escape:'htmlall':'UTF-8'}{/if}<br/>
Comentario: {if isset($smarty.post.customer_comments)}{$smarty.post.customer_comments|escape:'htmlall':'UTF-8'}{/if}<br/>