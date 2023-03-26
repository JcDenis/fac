$(function(){
	/* toogle admin form sidebar */
	$('#fac h5').toggleWithLegend(
		$('#fac').children().not('h5'),
		{cookie:'dcx_fac_admin_form_sidebar',legend_click:true}
	);
});