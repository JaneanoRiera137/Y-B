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

<ul>
    <li>Código etiquetador: {$correos_config.correos_key|escape:'htmlall':'UTF-8'}</li>
    <li>Número de contrato: {$correos_config.contract_number|escape:'htmlall':'UTF-8'}</li>
    <li>Número de cliente: {$correos_config.client_number|escape:'htmlall':'UTF-8'}</li>
    <li>Transportista de Correos: {$carrier_name|escape:'htmlall':'UTF-8'}</li>
    <li>Status: {$status|escape:'htmlall':'UTF-8'}</li>
    <li>Fecha: {$date|escape:'htmlall':'UTF-8'}</li>
    <li>Resto transportistas Correos activados: {$active_carriers|escape:'htmlall':'UTF-8'}</li>
    <li>Versión módulo: {$correos_version|escape:'htmlall':'UTF-8'}</li>
    <li>Url home: {$shop_url|escape:'htmlall':'UTF-8'}</li>
</ul>