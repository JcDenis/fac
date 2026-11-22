/*global $, dotclear, datePicker */
'use strict';

$(() => {
  const title = document.querySelector('#fac h5');
  if (title) {
    dotclear.toggleWithLegend(title, document.querySelectorAll('#fac > :not(h5)'), {
      user_pref: 'dcx_fac_admin_form_sidebar',
      legend_click: true,
    });
  }
});