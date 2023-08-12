/*global $, dotclear, datePicker */
'use strict';

$(() => {
  $('#fac h5').toggleWithLegend(
  	$('#fac').children().not('h5'),
	{user_pref:'dcx_fac_admin_form_sidebar',legend_click:true}
  );
});