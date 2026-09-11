/**
 * @file
 * Tweaks to the Tour module/ShepherdJS for Origins Help.
 *
 * @see https://www.drupal.org/project/tour
 * @see https://docs.shepherdjs.dev
 */
(function (Drupal, drupalSettings) {

  Drupal.behaviors.originsHelpModalButton = {
    attach: function () {

      // Inserts a 'Feedback' link adjacent to the last element in the tip footer.
      function addFeedbackButton(step) {

        const footer = step.el && step.el.querySelector('.shepherd-footer');
        if (!footer) return;

        // Exit of it's already attached.
        if (footer.querySelector('.origins-help-modal-btn')) return;

        // Site and Tour data to send in the Feedback message.
        const siteName = drupalSettings.originsHelp?.siteName || '';
        const tourName = step.id || document.title || 'Unknown Tour';
        const currentUrl = window.location.href;

        // Feedback form path for Core's Ajax Dialog Box.
        const feedbackUrl = new URL(
            '/origins-help/feedback',
            window.location.origin
        );

        // Attach the site and tour data as querystring to the feedback modal route.
        feedbackUrl.searchParams.set('site', siteName);
        feedbackUrl.searchParams.set('tour', tourName);
        feedbackUrl.searchParams.set('page', currentUrl);

        const button = document.createElement('a');

        button.textContent = 'Feedback';
        button.href = feedbackUrl.toString();
        button.className = 'button shepherd-button use-ajax origins-help-modal-btn';
        button.style.marginLeft = '0.8rem';
        button.style.backgroundColor = '#fff';
        button.style.color = 'var(--button-bg-color--primary)';
        button.style.fontWeight = '600';
        button.style.borderRadius = '6px';
        button.style.boxShadow = 'none';
        button.setAttribute('data-dialog-type', 'modal');
        button.setAttribute('data-dialog-options', '{"width":800}');

        footer.lastElementChild.insertAdjacentElement('afterend', button);

        // Attach so that Core can trigger the Ajax modal via 'use-ajax'.
        Drupal.attachBehaviors(button);
      }

      let watchedTour = null;

      // Poll the Shepard object for an active tour.
      function watchForTour() {
        if (typeof Shepherd === 'undefined' || !Shepherd.activeTour) return;
        if (Shepherd.activeTour === watchedTour) return;

        // Store the active tour so we know in the next poll if we need to
        // attach the show event handler.
        watchedTour = Shepherd.activeTour;

        // Attach the handler to the last step of the tour when the 'show' event is triggered.
        const lastStep = watchedTour.steps[watchedTour.steps.length - 1];
        lastStep.on('show', function () {
          addFeedbackButton(lastStep);
        });
      }

      setInterval(watchForTour, 1300);
    }
  };

})(Drupal, drupalSettings);
