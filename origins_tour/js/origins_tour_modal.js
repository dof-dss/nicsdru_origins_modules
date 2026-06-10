(function (Drupal, drupalSettings) {

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
          '.shepherd-element.shepherd-enabled.tip-tasks-sidebar, ' +
          '.shepherd-element.shepherd-enabled.tip-what'
        );

        if (!steps.length) return;

        steps.forEach(function (step) {

          const footer = step.querySelector('.shepherd-footer');
          if (!footer) return;

          if (footer.querySelector('.origins-tour-modal-btn')) return;

          const siteName = drupalSettings.originsTour?.siteName || '';
          const tourName = drupalSettings.originsTour?.tourName || '';
          const currentUrl = window.location.href;

          const feedbackUrl = new URL(
            '/origins-tour/feedback',
            window.location.origin
          );

          feedbackUrl.searchParams.set('site', siteName);
          feedbackUrl.searchParams.set('tour', tourName);
          feedbackUrl.searchParams.set('page', currentUrl);

          const button = document.createElement('a');

          button.textContent = 'Feedback';
          button.href = feedbackUrl.toString();

          button.className =
            'button shepherd-button use-ajax origins-tour-modal-btn';

          button.style.marginLeft = '0.8rem';
          button.style.backgroundColor = '#fff';
          button.style.color = 'var(--button-bg-color--primary)';
          button.style.fontWeight = '600';
          button.style.borderRadius = '6px';
          button.style.boxShadow = 'none';

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

      // 🔥 THIS is the key line you must keep
      setInterval(addModalButton, 300);
    }
  };

})(Drupal, drupalSettings);
