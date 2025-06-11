<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Returns responses for Origins reporter routes.
 */
final class ContentIssueController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(int $entity_id, int|null $revision_id = NULL): array {
    $issue = $this->entityTypeManager()->getStorage('content_issue')->create([]);
    $form = \Drupal::service('entity.form_builder')->getForm($issue, 'add');

    /** @var \Drupal\node\NodeInterface $node */
    $node_storage = $this->entityTypeManager->getStorage('node');
    if (!empty($revision_id)) {
      $node = $node_storage->loadRevision($revision_id);
    }
    else {
      $node = $node_storage->load($entity_id);
    }

    $form["label"]["widget"][0]["value"]['#value'] = $node->label();

    $form['label']['#default_value'] = $node->label();
    $form['label']['#value'] = $node->label();

    $form['assigned_to']['#attributes']['class'][] = 'hidden';
    $form["description"]["widget"][0]["format"]['#attributes']['class'][] = 'hidden';

    $form["content_entity_id"]["#value"] = $entity_id;
    $form["content_entity_revision_id"]["#value"] = $revision_id;

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::ajaxCloseForm',
      ],
    ];

    $build['content'] = $form;

    return $build;
  }

  /**
   * AJAX callback to close the off-canvas dialog
   */
  public function ajaxCloseForm(array&$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand('#drupal-off-canvas'));
    return $response;
  }

  public function display($entity_id) {

    $storage = \Drupal::entityTypeManager()->getStorage('content_issue');
    $issue = $storage->load($entity_id);
    $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder('content_issue');
    $build = $viewBuilder->view($issue, 'default');

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#content-issue-dashboard-aside article', $build, []));
    $response->addCommand(new InvokeCommand('#content-issue-dashboard-aside', 'addClass', ['open']));
    return $response;
  }

}
