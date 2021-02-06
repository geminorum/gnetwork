/* global gNetworkTaxonomyActions */

jQuery(function ($) {
  const actions = [];

  $.each(gNetworkTaxonomyActions, function (key, title) {
    actions.unshift({
      action: 'extra-' + key,
      name: title,
      el: $('#gnetwork-taxonomy-input-' + key)
    });
  });

  $('.actions select')
    .each(function () {
      const $option = $(this).find('option:first');

      $.each(actions, function (i, actionObj) {
        $option.after($('<option>', {
          value: actionObj.action,
          html: actionObj.name
        }));
      });
    })
    .on('change', function () {
      const $select = $(this);

      $.each(actions, function (i, actionObj) {
        if ($select.val() === actionObj.action) {
          actionObj.el
            .insertAfter($select)
            .css('display', 'inline')
            .find(':input').trigger('focus');
        } else {
          actionObj.el
            .css('display', 'none');
        }
      });
    });
});
