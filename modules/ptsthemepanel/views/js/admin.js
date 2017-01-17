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

	//show new item panel
	$('.button-new-item').on('click', function() {
		$('.new-item').find('.item-container').slideToggle();
	});
	// cancel new item
	$('.button-new-item-cancel').on('click',function() {
		$('.new-item').find('.item-container').slideToggle();
	});
	//show item content edit panel
	$('.button-edit').on('click', function(e) {
		e.preventDefault();
		var $item_container = $(this).closest('.item');
		$item_container.find('.item-container').slideToggle();
		$(this).find('.button-edit-edit').toggleClass('hide');
		$(this).find('.button-edit-close').toggleClass('hide');
	});
	//cancel item edit
	$('.button-item-edit-cancel').on('click',function(){
		$(this).closest('form').find('.button-edit .button-edit-edit').toggleClass('hide');
		$(this).closest('form').find('.button-edit .button-edit-close').toggleClass('hide');
		$(this).closest('form').find('.item-container').slideToggle();
	})

	//delete item
	$('.link-item-delete').on('click',function(e){
		e.preventDefault();
		var $form = $(this).closest('form');
		var $hiddenField = $("<input type='hidden' name='removeItem'/>");
		$hiddenField.appendTo($form);
		$form.submit();
	});

	// set language for new item
	$('.new-lang-flag').on('click', function() {
		var lang_id = (this.id).substr(5);
		$('.new-lang-flag').each(function () {
			$(this).removeClass('active');
		});
		$(this).addClass('active');
		$("#lang-id").val(lang_id)
	});


	if(document.location.hash!='') {
 
	    var tabName = window.location.hash;
	 	$("#myTab "+tabName+'-button').tab('show');
    }else{
	   $('#myTab a:first').tab('show')
    }
});