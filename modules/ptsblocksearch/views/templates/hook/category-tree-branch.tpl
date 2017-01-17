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
<option value="{$node.id|escape:'htmlall':'UTF-8'}" {if $current_category == $node.id}selected="selected"{/if}>
    {$node.prefix|escape:'htmlall':'UTF-8'} {$node.name|escape:'html':'UTF-8'}
</option>
{if $node.children|@count > 0}
    {foreach from=$node.children item=child name=ptsblocksearchTreeBranch}
        {if $smarty.foreach.ptsblocksearchTreeBranch.last}
            {include file="$ptsbranche_tpl_path" node=$child last='true'}
        {else}
            {include file="$ptsbranche_tpl_path" node=$child last='false'}
        {/if}
    {/foreach}
{/if}
