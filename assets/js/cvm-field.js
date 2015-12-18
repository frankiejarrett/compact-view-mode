/* globals cvm_global */
jQuery( document ).ready( function( $ ) {

	var checked = ( cvm_global.is_compact ) ? 'checked="checked"' : '';

	$( '#adv-settings .view-mode legend' ).after( "<label for='compact-view-mode'><input id='compact-view-mode' type='radio' name='mode' value='compact' " + checked + " /> " + cvm_global.i18n.field_label + "</label>" );

});
