<?php

declare(strict_types=1);

namespace Drupal\origins_tour\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a feedback form for the Origins Tour help page.
 */
final class FeedbackForm extends FormBase {

  /**
   * Constructs a new FeedbackForm.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   The mail manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(
    private readonly MailManagerInterface $mailManager,
    private readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('plugin.manager.mail'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'origins_tour_feedback_form';
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   *   The form render array.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#prefix'] = '<div id="origins-tour-feedback-wrapper">';
    $form['#suffix'] = '</div>';

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
   * AJAX form submit callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function ajaxSubmit(array $form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#origins-tour-feedback-wrapper', $form));
      return $response;
    }

    $success = [
      '#prefix' => '<div id="origins-tour-feedback-wrapper">',
      '#suffix' => '</div>',
      'message' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['messages', 'messages--status']],
        'text' => ['#markup' => $this->t('Thank you for your feedback.')],
      ],
      'close' => [
        '#type' => 'link',
        '#title' => $this->t('Close'),
        '#url' => Url::fromRoute('origins_tour.close_modal'),
        '#attributes' => ['class' => ['use-ajax', 'button']],
      ],
      '#attached' => ['library' => ['core/drupal.ajax']],
    ];

    $response->addCommand(new ReplaceCommand('#origins-tour-feedback-wrapper', $success));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->mailManager->mail(
      'origins_tour',
      'feedback_email',
      'tour-feedback@finance-ni.gov.uk',
      $this->currentUser->getPreferredLangcode(),
      [
        'subject' => 'Origins Tour Feedback',
        'message' => $form_state->getValue('message'),
      ],
    );
  }

}
