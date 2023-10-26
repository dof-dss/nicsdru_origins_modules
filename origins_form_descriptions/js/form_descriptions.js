(function ($, Drupal) {
  Drupal.behaviors.formDescriptions = {
    attach: function attach (context) {
      $('.form-item [class$=description]').each(function(){
        // Find parent field ID, derive from the description id attribute.
        const descId = $(this).attr('id');

        // check if descID is defined
        if (descId) {
          const suffix = '--description';

          // Remove the description suffix to find the ID of the
          // element it is describing.
          const labelFor = descId.substring(0, descId.indexOf(suffix));

          // Don't move the description if it is the fieldset__description, as
          // it will be moved down below the first item label.
          if ($(this).is('.fieldset__description')) {
            return;
          }

          // Move element adjacent to it. The selector for this has to be
          // a little greedy, because some elements vary their suffixes, but
          // we do at least have a common prefix. Filter selects are fiddly,
          // because they share almost the exact same label attribute values,
          // but we can use jQuery to only use the first label to avoid duplicates.

          if (!$(this).parent().is('.fieldset-wrapper') && !$(this).is('#edit-field-featured-content--description')) {
            $('label[for^="' + labelFor + '"]').first().after($(this));
          } else if ($(this).is('#edit-field-featured-content--description')) {
            const desc = $(this).html();
            $(this).prev().children('tbody').children('tr').first().before("<tr><td id='#edit-field-featured-content--description' class='form-item__description' colspan='2'>" + desc + "</td></tr>");
            $(this).remove();
          }
        }
      });
    }
  };
})(jQuery, Drupal);

