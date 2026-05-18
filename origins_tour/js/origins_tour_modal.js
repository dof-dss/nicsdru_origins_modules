(function (Drupal) {

  Drupal.behaviors.originsTourModalButton = {
    attach: function () {

      function addModalButton() {

        const steps = document.querySelectorAll(
          '.shepherd-element.shepherd-enabled.tip-bulk-individual-select, ' +
          '.shepherd-element.shepherd-enabled.tip-save-or-preview, ' +
          '.shepherd-element.shepherd-enabled.tip-save, ' +
          '.shepherd-element.shepherd-enabled.tip-operations, ' +
          '.shepherd-element.shepherd-enabled.tip-compare-selected-revisions, ' +
          '.shepherd-element.shepherd-enabled.tip-save-delete, ' +
          '.shepherd-element.shepherd-enabled.tip-what-links-here'
        );

        if (!steps.length) {
          return;
        }

        steps.forEach(function (step) {

          const footer = step.querySelector('.shepherd-footer');

          if (!footer) {
            return;
          }

          // Prevent duplicate button per step
          if (footer.querySelector('.origins-tour-modal-btn')) {
            return;
          }

          const button = document.createElement('a');

          button.textContent = 'Feedback';
          button.href = '/origins-tour/feedback';

          button.className =
            'button shepherd-button use-ajax origins-tour-modal-btn';

          button.style.marginLeft = '0.8rem';
          button.style.backgroundColor = '#D4A017';
          button.style.borderColor = '#D4A017';
          button.style.color = '#ffffff';
          button.style.fontWeight = '600';
          button.style.borderRadius = '6px';

          button.setAttribute('data-dialog-type', 'modal');
          button.setAttribute('data-dialog-options', '{"width":800}');

          const primaryButton = footer.querySelector('.button--primary');

          if (primaryButton) {
            primaryButton.insertAdjacentElement('afterend', button);
          }
          else {
            footer.appendChild(button);
          }

          Drupal.attachBehaviors(button);
        });
      }

      setInterval(addModalButton, 300);

    }
  };

})(Drupal);
