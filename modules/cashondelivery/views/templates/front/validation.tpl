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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" rel="nofollow" title="{l s='Regresar a la caja' mod='cashondelivery'}">{l s='Checkout' mod='cashondelivery'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Pago contra entrega (PCE)' mod='cashondelivery'}
{/capture}

{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Detalles de Pedido' mod='cashondelivery'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<h3>{l s='Pago contra entrega (PCE)' mod='cashondelivery'}</h3>

<form action="{$link->getModuleLink('cashondelivery', 'validation', [], true)|escape:'html'}" method="post">
	<input type="hidden" name="confirm" value="1" />
	<p>
		<img src="{$this_path_cod}cashondelivery.jpg" alt="{l s='Pago contra entrega (PCE)' mod='cashondelivery'}" style="float:left; margin: 0px 10px 5px 0px;" />
		{l s='Usted ha elegido pago contra entrega (PCE).' mod='cashondelivery'}
		<br/><br />
		{l s='El total de su orden es' mod='cashondelivery'}
		<span id="amount_{$currencies.0.id_currency}" class="price">{convertPrice price=$total}</span>
		{if $use_taxes == 1}
		    {l s='(tax incl.)' mod='cashondelivery'}
		{/if}
	</p>
	<p>
		<br /><br />
		<br /><br />
		<b>{l s='Porfavor confirme su orden dando click en \'Yo confirmo mi orden\'.' mod='cashondelivery'}</b>
	</p>
	<p class="cart_navigation" id="cart_navigation">
		<a href="{$link->getPageLink('order', true)}?step=3" class="button_large">{l s='otros métodos de pago' mod='cashondelivery'}</a>
		<input type="submit" value="{l s='Yo confirmo mi orden' mod='cashondelivery'}" class="exclusive_large" />
	</p>
</form>
