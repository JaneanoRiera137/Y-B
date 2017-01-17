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
<div id="search_block_left" class="block exclusive">
	<h4 class="title_block">{l s='Search' mod='ptsblocksearch'}</h4>
	<form method="get" action="{$link->getPageLink('search')|escape:'html':'UTF-8'}" id="searchbox">
            <input type="hidden" name="controller" value="search" />
            <p class="block_content">
                {$category_html}{* HTML, can not escape *}
                <label for="search_query_block">{l s='Search products:' mod='ptsblocksearch'}</label>
                <input type="hidden" name="orderby" value="position" />
                <input type="hidden" name="orderway" value="desc" />
                <input class="search_query" type="text" id="search_query_block" name="search_query" value="{$search_query|escape:'html':'UTF-8'|stripslashes}" />
                <input type="submit" id="search_button" class="button_mini" value="{l s='Go' mod='ptsblocksearch'}" />
            </p>
	</form>
</div>
{include file=$instance_tpl_path}
{if $tags}
    <div id="search_tags" class="search_tags">
        {foreach $tags as $tag}
            <a href="{$tag.link|escape:'htmlall':'UTF-8'}" title="{$tag.tag|escape:'htmlall':'UTF-8'}">{$tag.tag|escape:'htmlall':'UTF-8'}</a>
        {/foreach}
    </div>
{/if}
<!-- /Block search module -->
