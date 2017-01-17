/**
 *  Pts Prestashop Theme Framework for Prestashop 1.6.x
 *
 * @package   ptsthemepanel
 * @version   5.0
 * @author    http://www.prestabrain.com
 * @copyright Copyright (C) October 2013 prestabrain.com <@emai:prestabrain@gmail.com>
 *               <info@prestabrain.com>.All rights reserved.
 * @license   GNU General Public License version 2
 * 
 */
jQuery(document).ready(function() {

	$( "body" ).on( "hover", ".thumbs_list li a", function() {
		displayImage($(this));
	});

	function displayImage(domAAroundImgThumb, no_animation)
	{
		if (typeof(no_animation) == 'undefined')
			no_animation = false;
		if (domAAroundImgThumb.prop('href'))
		{
			var new_src = domAAroundImgThumb.attr('href').replace('thickbox', 'large');
			var new_title = domAAroundImgThumb.attr('title');
			var new_href = domAAroundImgThumb.attr('href');
			
			if (domAAroundImgThumb.parent().parent().parent().parent().parent().find('.pts-image').prop('src') != new_src)
			{
				domAAroundImgThumb.parent().parent().parent().parent().parent().find('.pts-image').attr({
					'src' : new_src,
					'alt' : new_title,
					'title' : new_title
				}).load(function(){
					if (typeof(jqZoomEnabled) != 'undefined' && jqZoomEnabled)
						$(this).attr('rel', new_href);
				});
			}
			$('.thumbs_list li a').removeClass('shown');
			$(domAAroundImgThumb).addClass('shown');
		}
	}
});