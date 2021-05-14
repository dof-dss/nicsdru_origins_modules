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
        $('label[for^="' + labelFor + '"]').first().after($(this));
      });
    }
  };
})(jQuery, Drupal);

