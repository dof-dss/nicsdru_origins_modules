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
use Drupal\Core\Url;
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
    $issueManager = \Drupal::service('content_issue.manager');

    $node_storage = $this->entityTypeManager->getStorage('node');
    if (!empty($revision_id) && $entity_id != $revision_id) {
      /** @var \Drupal\node\NodeInterface $node */
      $node = $node_storage->loadRevision($revision_id);
      $current_issues = $issueManager->getIssuesByContentId($entity_id, $revision_id);
    }
    else {
      /** @var \Drupal\node\NodeInterface $node */
      $node = $node_storage->load($entity_id);
      $current_issues = $issueManager->getIssuesByContentId($entity_id);
    }

    $form_state['values']['content_entity_id'] = $entity_id;
    $form_state['values']['content_entity_revision_id'] = $revision_id;
    $form_state['values']['assigned_to'] = $node->getOwnerId();
    $form_state['values']['label'] = $node->label();

    $form = \Drupal::service('entity.form_builder')->getForm($issue, 'add', $form_state);

    $form['assigned_to']['#attributes']['class'][] = 'hidden';
    $form["description"]["widget"][0]["format"]['#attributes']['class'][] = 'hidden';

    if ($current_issues) {
      $form["current_issues"] = [
        '#type' => 'link',
        '#title' => $this->t('Click here to view %count existing issues for this content.', ['%count' => count($current_issues)]),
        '#url' => Url::fromRoute('entity.content_issue.collection', [
          'entity_id' => $entity_id,
          'revision_id' => $revision_id,
        ]),
      ];
    }

    $build['content'] = $form;

    return $build;
  }

  /**
   * Displays a Content Issue in the information panel.
   *
   * @param int|string $entity_id
   *   The Content Issue ID to display.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The Ajax response to update the information panel or warning.
   */
  public function display(int|string $entity_id) {

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
