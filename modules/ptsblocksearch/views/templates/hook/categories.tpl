{*
* Pts Prestashop Theme Framework for Prestashop 1.6.x
*
* @package   ptsblocksearch
* @version   5.0
* @author    http://www.prestabrain.com
* @copyright Copyright (C) October 2013 prestabrain.com <@emai:prestabrain@gmail.com>
*               <info@prestabrain.com>.All rights reserved.
* @license   GNU General Public License version 2
*}
{if $ptsblocksearchCategTree && $ptsblocksearchCategTree.children|@count}
    <select name="category_filter" id="category_filter">
        <option value="0"  {if $current_category == 0}selected="selected"{/if}>{l s='All Categories' mod='ptsblocksearch'}</option>
        <option value="{$home_category->id|escape:'htmlall':'UTF-8'}"  {if $current_category == $home_category->id}selected="selected"{/if}>-- {$home_category->name|escape:'htmlall':'UTF-8'}</option>
        {foreach from=$ptsblocksearchCategTree.children item=child name=ptsblocksearchCategTree}
            {if $smarty.foreach.ptsblocksearchCategTree.last}
                {include file="$ptsbranche_tpl_path" node=$child last='true'}
            {else}
                {include file="$ptsbranche_tpl_path" node=$child}
            {/if}
        {/foreach}
    </select>
{/if}
