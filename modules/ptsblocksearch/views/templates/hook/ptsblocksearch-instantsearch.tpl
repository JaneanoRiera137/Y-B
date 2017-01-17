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
{if $instantsearch}
	<script type="text/javascript">
	// <![CDATA[
		function tryToCloseInstantSearch() {
			if ($('#old_center_column').length > 0)
			{
				$('#center_column').remove();
				$('#old_center_column').attr('id', 'center_column');
				$('#center_column').show();
				return false;
			}
		}
		
		instantSearchQueries = new Array();
		function stopInstantSearchQueries(){
			for(i=0;i<instantSearchQueries.length;i++) {
				instantSearchQueries[i].abort();
			}
			instantSearchQueries = new Array();
		}
		
		$("#pts_search_query_{$ptsblocksearch_type|escape:'htmlall':'UTF-8'}").keyup(function(){
			if($(this).val().length > 0){
				stopInstantSearchQueries();
				instantSearchQuery = $.ajax({
					url: '{if $search_ssl == 1}{$link->getPageLink('search', true)|addslashes|escape:'htmlall':'UTF-8'}{else}{$link->getPageLink('search')|addslashes|escape:'htmlall':'UTF-8'}{/if}',
					data: {
						instantSearch: 1,
						id_lang: {$cookie->id_lang|escape:'htmlall':'UTF-8'},
						q: $(this).val(),
						category_filter: $("#category_filter").val()
					},
					dataType: 'html',
					type: 'POST',
					success: function(data){
						if($("#pts_search_query_{$ptsblocksearch_type|escape:'htmlall':'UTF-8'}").val().length > 0)
						{
							tryToCloseInstantSearch();
							$('#center_column').attr('id', 'old_center_column');
							$('#old_center_column').after('<div id="center_column" class="' + $('#old_center_column').attr('class') + '">'+data+'</div>');
							$('#old_center_column').hide();
							// Button override
							ajaxCart.overrideButtonsInThePage();
							$("#instant_search_results a.close").click(function() {
								$("#pts_search_query_{$ptsblocksearch_type|escape:'htmlall':'UTF-8'}").val('');
								return tryToCloseInstantSearch();
							});
							return false;
						}
						else
							tryToCloseInstantSearch();
					}
				});
				instantSearchQueries.push(instantSearchQuery);
			}
			else
				tryToCloseInstantSearch();
		});
	// ]]>
	</script>
{/if}


    <script type="text/javascript">
    // <![CDATA[
            $('document').ready( function() {
                    var ac = $("#pts_search_query_{$ptsblocksearch_type|escape:'htmlall':'UTF-8'}")
                            .autocomplete(
                                    '{if $search_ssl == 1}{$link->getPageLink('search', true)|addslashes|escape:'htmlall':'UTF-8'}{else}{$link->getPageLink('search')|addslashes|escape:'htmlall':'UTF-8'}{/if}', {
                                            minChars: 3,
                                            max: 10,
                                            width: 350,
                                            selectFirst: false,
                                            scroll: false,
                                            dataType: "json",
                                            formatItem: function(data, i, max, value, term) {
                                                    return value;
                                            },
                                            parse: function(data) {
                                                    var mytab = new Array();
                                                    for (var i = 0; i < data.length; i++)
                                                        mytab[mytab.length] = { 
                                                            data: data[i], 
                                                            value: '<img class="pull-left" src="'+ data[i].image+'" style="margin-right:10px;"><div class="name">'+ data[i].pname +'</div><div class="price">{l s='Price' mod='ptsblocksearch'} '+ data[i].dprice +'</div> '
                                                        };
                                                    return mytab;
                                            },
                                            extraParams: {
                                                ajaxSearch: 1,
                                                id_lang: {$cookie->id_lang|escape:'htmlall':'UTF-8'},
                                                category_filter: $("#category_filter").val()
                                            }
                                    }
                            )
                            .result(function(event, data, formatted) {
                                    $('#pts_search_query_{$ptsblocksearch_type|escape:'htmlall':'UTF-8'}').val(data.pname);
                                    document.location.href = data.product_link;
                            });
                            
                            $("#category_filter").change(function() {
                                ac.setOptions({
                                    extraParams: {
                                        ajaxSearch: 1,
                                        id_lang: {$cookie->id_lang|escape:'htmlall':'UTF-8'},
                                        category_filter: $("#category_filter").val()
                                    }
                                });
                            });
                              
            });
    // ]]>
    </script>