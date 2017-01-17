{*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author Antonio Riera <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<p>{l s='Su orden ha sido %s confirmada.' sprintf=$shop_name mod='cashondelivery2'}
	<br /><br />
	{l s='Usted ha elegido pago con Dinero Eletrónico' mod='cashondelivery2'}
	<br /><br /><span class="bold">{l s='Su orden será enviada muy pronto.' mod='cashondelivery2'}</span>
	<br /><br />{l s='Si dispone de preguntas o necesita mas información, puede contactarse con nuestro' mod='cashondelivery2'} <a href="{$link->getPageLink('contact-form', true)|escape:'html'}">{l s='departamento de soporte' mod='cashondelivery2'}</a>.
</p>
