/**
 * @file
 * Move Tour button to after H1.
 *
 * @see https://www.drupal.org/project/tour
 */

(function (Drupal, once) {
    Drupal.behaviors.moveTourButton = {
        attach(context) {
            once('move-tour-button', 'h1.page-title', context).forEach((pageTitle) => {
                const tourButton = document.querySelector(
                    '.js-tour-start-toolbar'
                );

                if (!tourButton) {
                    return;
                }

                pageTitle.insertAdjacentElement('afterend', tourButton);
            });
        }
    };
})(Drupal, once);