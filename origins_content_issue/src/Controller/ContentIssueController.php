<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
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

    /** @var \Drupal\node\NodeInterface $node */
    $node_storage = $this->entityTypeManager->getStorage('node');
    if (!empty($revision_id) && $entity_id != $revision_id) {
      $node = $node_storage->loadRevision($revision_id);
    }
    else {
      $node = $node_storage->load($entity_id);
    }

    $form_state['values']['content_entity_id'] = $entity_id;
    $form_state['values']['content_entity_revision_id'] = $revision_id;
    $form_state['values']['assigned_to'] = $node->getOwnerId();
    $form_state['values']['label'] = $node->label();

    $form = \Drupal::service('entity.form_builder')->getForm($issue, 'add', $form_state);

    $form['assigned_to']['#attributes']['class'][] = 'hidden';
    $form["description"]["widget"][0]["format"]['#attributes']['class'][] = 'hidden';

//    $form['actions']['cancel'] = [
//      '#type' => 'submit',
//      '#value' => $this->t('Cancel'),
//      '#limit_validation_errors' => [],
//      '#ajax' => [
//        'callback' => '::ajaxCloseForm',
//      ],
//    ];

    $build['content'] = $form;

    return $build;
  }

//  /**
//   * AJAX callback to close the off-canvas dialog
//   */
//  public function ajaxCloseForm(array&$form, FormStateInterface $form_state) {
//    $response = new AjaxResponse();
//    $response->addCommand(new CloseDialogCommand('#drupal-off-canvas'));
//    return $response;
//  }

  public function display($entity_id) {

    if (!\Drupal::request()->isXmlHttpRequest()) {
      return $this->redirect('entity.content_issue.collection');
    }

    $issueManager = \Drupal::service('content_issue.manager');
    $build = $issueManager->renderIssue($entity_id);

    $response = new AjaxResponse();

    if ($build === NULL) {
      $response->addCommand(new MessageCommand('Requested issue no longer exists and has been removed from the dashboard.'));
      $response->addCommand(new RemoveCommand('div.content-issue-row[data-entity-id="' . $entity_id . '"]'));
      return $response;
    }

    $response->addCommand(new ReplaceCommand('#content-issue-dashboard-aside article', $build, []));
    $response->addCommand(new InvokeCommand('#content-issue-dashboard-aside', 'addClass', ['open']));
    return $response;
  }

}
