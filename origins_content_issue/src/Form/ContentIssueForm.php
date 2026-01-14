<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\origins_content_issue\ContentIssueManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the content issue entity edit forms.
 */
final class ContentIssueForm extends ContentEntityForm {

  /**
   * Drupal renderer service instance.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;


  /**
   * Content Issue Manager service.
   *
   * @var \Drupal\origins_content_issue\ContentIssueManager
   */
  protected $contentIssueManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, ContentIssueManager $content_issue_manager, RendererInterface $renderer) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->contentIssueManager = $content_issue_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('content_issue.manager'),
      $container->get('renderer'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // @phpstan-ignore-next-line
    $entity = $form_state->getFormObject()->getEntity();
    $is_new = $entity->isNew();
    $current_user = \Drupal::currentUser()->id();
    $assigned_to = $entity->get('assigned_to')->getString();

    if ($is_new) {
      $form["state"]["#type"] = 'hidden';
    }

    $form['advanced']['#type'] = 'hidden';

    $form["label"]["widget"][0]["value"]['#value'] = $form_state->getValue('label') ?? $entity->get('label')->getString();

    $form['content_entity_id'] = [
      '#type' => 'hidden',
      '#default_value' => $form_state->getValue('content_entity_id') ?? $entity->get('content_entity_id')->getString(),
    ];

    $form['content_entity_revision_id'] = [
      '#type' => 'hidden',
      '#default_value' => $form_state->getValue('content_entity_revision_id') ?? $entity->get('content_entity_revision_id')->getString(),
    ];

    $form['uid']['#attributes']['class'][] = 'hidden';
    $form['created']['#attributes']['class'][] = 'hidden';
    $form['revision_information']['#attributes']['class'][] = 'hidden';
    $form['comments']['#attributes']['class'][] = 'hidden';
    $form['status']['#attributes']['class'][] = 'hidden';
    $form["description"]["widget"][0]["format"]['#attributes']['class'][] = 'hidden';

    // Only the assigned user can change the issue state.
    if (!$is_new && $current_user != $assigned_to) {
      $form["state"]["#attributes"]["class"][] = 'hidden';
    }

    // Make sure it's an AJAX form.
    $form['actions']['submit']['#ajax'] = [
      'callback' => '::ajaxSubmit',
      'event' => 'click',
      'progress' => [
        'type' => 'throbber',
        'message' => t('Saving...'),
      ],
    ];

    if (!$is_new) {
      // Remove redirect to prevent page reloading.
      $form['#submit'][] = '::formSubmit';

      $form_state->setRedirect(NULL);
    }

    return $form;
  }

  /**
   * Issue form submit callback.
   */
  public function formSubmit($form, FormStateInterface $form_state) {
    $form_state->disableRedirect();
  }

  /**
   * Submit callback for creating or updating a content issue.
   */
  public function ajaxSubmit($form, FormStateInterface $form_state) {
    // @phpstan-ignore-next-line
    $entity = $form_state->getformObject()->getEntity();

    $response = new AjaxResponse();

    if ($form_state->getFormObject()->getFormId() === 'content_issue_add_form') {
      $response->addCommand(new MessageCommand('Content issue created.'));
      $response->addCommand(new CloseDialogCommand('#drupal-off-canvas'));
    }
    else {
      // Render Issue row.
      $row_build = $this->contentIssueManager->renderRow($entity);
      $row_build = $this->renderer->render($row_build);

      // Render Issue details pane.
      $info_build = $this->contentIssueManager->renderIssue($entity->id());

      $response->addCommand(new ReplaceCommand('.content-issue-row[data-entity-id="' . $entity->id() . '"]', $row_build, []));
      $response->addCommand(new ReplaceCommand('#content-issue-dashboard-aside article', $info_build, []));
      $response->addCommand(new InvokeCommand('#content-issue-dashboard-aside', 'addClass', ['open']));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    if ($result < SAVED_NEW) {
      throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

}
