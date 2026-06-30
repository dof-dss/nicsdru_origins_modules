<?php

declare(strict_types=1);

namespace Drupal\origins_tour\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a feedback form for the Origins Tour help page.
 */
final class FeedbackForm extends FormBase {

  /**
   * Form constructor.
   */
  public function __construct(
    protected MailManagerInterface $mailManager,
    protected AccountProxyInterface $currentUser,
    protected $requestStack,
    protected $loggerFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('plugin.manager.mail'),
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('logger.factory'),
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
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['#prefix'] = '<div id="origins-tour-feedback-wrapper">';
    $form['#suffix'] = '</div>';

    $query = $this->requestStack->getCurrentRequest()->query;

    // Hidden contextual values passed in via URL query string.
    $form['site_name'] = [
      '#type' => 'hidden',
      '#value' => $query->get('site', 'Unknown Site'),
    ];

    $form['tour_name'] = [
      '#type' => 'hidden',
      '#value' => $query->get('tour', 'Unknown Tour'),
    ];

    $form['page_url'] = [
      '#type' => 'hidden',
      '#value' => $query->get('page', 'Unknown URL'),
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
      $response->addCommand(new ReplaceCommand('#origins-tour-feedback-wrapper', $form));
      return $response;
    }

    $response->addCommand(new ReplaceCommand(
      '#origins-tour-feedback-wrapper',
      ['#markup' => '<div class="messages messages--status">Thank you for your feedback.</div>'],
    ));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $site_name = $form_state->getValue('site_name');
    $tour_name = $form_state->getValue('tour_name');
    $page_url = $form_state->getValue('page_url');
    $message = $form_state->getValue('message');

    $this->loggerFactory->get('origins_tour')->notice(
      'FEEDBACK | Site: @site | Tour: @tour | Page: @page | Message: @message',
      [
        '@site' => $site_name,
        '@tour' => $tour_name,
        '@page' => $page_url,
        '@message' => $message,
      ]
    );

    $result = $this->mailManager->mail(
      'origins_tour',
      'tour_feedback',
      'eddwebdev@finance-ni.gov.uk',
      $this->currentUser->getPreferredLangcode(),
      [
        'site_name' => $site_name,
        'tour_name' => $tour_name,
        'page_url' => $page_url,
        'message' => $message,
      ]
    );

    if (!$result['result']) {
      $this->messenger()->addError($this->t('Unable to send feedback email.'));
    }
  }

}
