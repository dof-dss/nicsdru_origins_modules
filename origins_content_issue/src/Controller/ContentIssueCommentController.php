<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Origins content issue routes.
 */
final class ContentIssueCommentController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(): array {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

  public function addForm(int $issue_id) {
    $issue_comment = $this->entityTypeManager()->getStorage('content_issue_comment')->create([]);

    $form_state['values']['issue_entity'] = $issue_id;
    $form = \Drupal::service('entity.form_builder')->getForm($issue_comment, 'add', $form_state);

    $form['assigned_to']['#attributes']['class'][] = 'hidden';
    $form["description"]["widget"][0]["format"]['#attributes']['class'][] = 'hidden';

    return $form;
  }

}
