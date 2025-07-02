<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
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


  public function editForm(int $comment_id) {

    $comment = $this->entityTypeManager()->getStorage('content_issue_comment')->load($comment_id);

    $build['ckeditor_section']['editor'] = [
      '#type' => 'text_format',
      '#format' => 'basic_html',
      '#default_value' => $comment->get('comment')->getString(),
    ];


    $build['submit'] = [
      '#type' => 'submit',
      '#value' => 'update'
    ];

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('.content-issue-comment[data-comment-id="' .$comment_id . '"]', $build, []));

    return $response;
  }

  public function deleteConfirm(int $comment_id) {

    $form['comment_id'] = [
      '#type' => 'hidden',
      '#value' => $comment_id,
    ];

    $form['confirm'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Are you sure you want to delete this comment?'),
    ];

    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#submit' => ['deleteComment'],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => [[$this, 'deleteComment']],
    ];

    return $form;
  }

  public function deleteComment($form, $form_state) {

  }

}
