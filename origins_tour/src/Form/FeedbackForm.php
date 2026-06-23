<?php

declare(strict_types=1);

namespace Drupal\origins_tour\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a feedback form for the Origins Tour help page.
 */
final class FeedbackForm extends FormBase {

    public function __construct(
  private readonly MailManagerInterface $mailManager,
      private readonly AccountProxyInterface $currentUser,
  ) {}

    /**
     * Create interface container.
     */
    public static function create(ContainerInterface $container): self {
  return new self(
            $container->get('plugin.manager.mail'),
            $container->get('current_user'),
        );
    }

    public function getFormId(): string {
        return 'origins_tour_feedback_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state): array {

        $form['#prefix'] = '<div id="origins-tour-feedback-wrapper">';
        $form['#suffix'] = '</div>';

        // Hidden contextual values passed in via URL query string.
        $form['site_name'] = [
            '#type' => 'hidden',
            '#value' => \Drupal::request()->query->get('site', 'Unknown Site'),
        ];

        $form['tour_name'] = [
            '#type' => 'hidden',
            '#value' => \Drupal::request()->query->get('tour', 'Unknown Tour'),
        ];

        $form['page_url'] = [
            '#type' => 'hidden',
            '#value' => \Drupal::request()->query->get('page', 'Unknown URL'),
        ];

        $form['message'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Your feedback'),
            '#required' => TRUE,
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Send feedback'),
            '#ajax' => [
                'callback' => '::ajaxSubmit',
                'wrapper' => 'origins-tour-feedback-wrapper',
                'progress' => [
                    'type' => 'throbber',
                    'message' => $this->t('Sending…'),
                ],
            ],
        ];

        return $form;
    }

    /**
     * AJAX callback for form submission.
     */
    public function ajaxSubmit(array $form, FormStateInterface $form_state): AjaxResponse {

        $response = new AjaxResponse();

        if ($form_state->hasAnyErrors()) {
            $response->addCommand(
                new ReplaceCommand('#origins-tour-feedback-wrapper', $form)
            );
            return $response;
        }

        $success = [
            '#markup' => '<div class="messages messages--status">Thank you for your feedback.</div>',
        ];

        $response->addCommand(
            new ReplaceCommand('#origins-tour-feedback-wrapper', $success)
        );

        return $response;
    }

    /**
     * Main submission handler (email sending happens here).
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void {

        $site_name = $form_state->getValue('site_name');
        $tour_name = $form_state->getValue('tour_name');
        $page_url = $form_state->getValue('page_url');
        $message = $form_state->getValue('message');

        // Log it.
        \Drupal::logger('origins_tour')->notice(
            'FEEDBACK | Site: @site | Tour: @tour | Page: @page | Message: @message',
            [
                '@site' => $site_name,
                '@tour' => $tour_name,
                '@page' => $page_url,
                '@message' => $message,
            ]
        );

        // Send email.
        $mailManager = \Drupal::service('plugin.manager.mail');

        $module = 'origins_tour';
        $key = 'tour_feedback';

        $to = 'eddwebdev@finance-ni.gov.uk';

        $params = [
            'site_name' => $site_name,
            'tour_name' => $tour_name,
            'page_url' => $page_url,
            'message' => $message,
        ];

        $langcode = \Drupal::currentUser()->getPreferredLangcode();

        $result = $mailManager->mail(
            $module,
            $key,
            $to,
            $langcode,
            $params
        );

        if (!$result['result']) {
            \Drupal::messenger()->addError(t('Unable to send feedback email.'));
        }
    }

}
