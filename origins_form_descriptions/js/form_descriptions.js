(function ($, Drupal) {
  Drupal.behaviors.formDescriptions = {
    attach: function attach (context) {
      $('.form-item .description').each(function(){
        // Find parent field ID, derive from the description id attribute.
        const descId = $(this).attr('id');
        const suffix = '--description';
        const labelFor = descId.substr(0, descId.indexOf(suffix));

        // Move element adjacent to it. The selector for this has to be
        // a little greedy, because some elements vary their suffixes, but
        // we do at least have a common prefix. Filter selects are fiddly,
        // because they share almost the exact same label attribute values
        // but we can use jQuery to only use the first label to avoid duplicates.
        // if (!$(this).parents().is('fieldset')) {
        if (!$(this).parent().is('.fieldset-wrapper') && !$(this).is('#edit-field-featured-content--description'))
        // if (!$(this).is('#edit-field-consultation-dates-0--description')
        //   && !$(this).is('#edit-field-featured-content--description')
        //   && !$(this).is('#edit-field-last-updated-0--description'))
        {
          $('label[for^="' + labelFor + '"]').first().after($(this));
        } else if ($(this).is('#edit-field-featured-content--description')) {
          const desc = $(this).html();
          $(this).prev().children('tbody').children('tr').first().before("<tr><td colspan='2'>" + desc + "</td></tr>");
          $(this).remove();
        }
      });
    }
  };
})(jQuery, Drupal);

