<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;


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

    $form['advanced']['#attributes']['class'][] = 'hidden';
    $form['created']['#attributes']['class'][] = 'hidden';
    $form['status']['#attributes']['class'][] = 'hidden';
    $form['comments']['#attributes']['class'][] = 'hidden';
    $form['uid']['#attributes']['class'][] = 'hidden';
    $form['revision_log']['#attributes']['class'][] = 'hidden';
    $form['revision_information']['#attributes']['class'][] = 'hidden';
    $form['revision']['#attributes']['class'][] = 'hidden';
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

}
