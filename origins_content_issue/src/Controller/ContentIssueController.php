<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Origins reporter routes.
 */
final class ContentIssueController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(): array {
    $entity = $this->entityTypeManager()->getStorage('content_issue')->create([]);
    $form = \Drupal::service('entity.form_builder')->getForm($entity, 'add');

    $form['advanced']['#attributes']['class'][] = 'hidden';
    $form['created']['#attributes']['class'][] = 'hidden';
    $form['status']['#attributes']['class'][] = 'hidden';
    $form['uid']['#attributes']['class'][] = 'hidden';
    $form['revision_log']['#attributes']['class'][] = 'hidden';
    $form['revision_information']['#attributes']['class'][] = 'hidden';
    $form['revision']['#attributes']['class'][] = 'hidden';
    $form["description"]["widget"][0]["format"]['#attributes']['class'][] = 'hidden';

    unset($form['advanced']);

    $build['content'] = $form;

    return $build;
  }

}
