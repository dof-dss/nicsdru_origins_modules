<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

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
    $cancel_url = Url::fromRoute('origins_content_issue.comment.close_modal');
    $action_url = Url::fromRoute('origins_content_issue.comment.delete', ['comment_id' => $comment_id]);

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['confirm-modal']],
      'message' => [
        '#markup' => '<p>Are you sure you want to proceed?</p>',
      ],
      'actions' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['modal-actions']],
        'confirm' => [
          '#type' => 'link',
          '#title' => $this->t('Confirm'),
          '#url' => $action_url,
          '#attributes' => [
            'class' => ['use-ajax', 'button', 'button--primary'],
            'data-dialog-close' => 'true',
          ],
        ],
        'cancel' => [
          '#type' => 'link',
          '#title' => $this->t('Cancel'),
          '#url' => $cancel_url,
          '#attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-close' => 'true',
          ],
        ],
      ],
    ];

    $build['comment_id'] = [
      '#type' => 'hidden',
      '#value' => $comment_id,
    ];

//    $response->addCommand(new OpenModalDialogCommand('Confirm Action', $build, ['width' => '400']));
//    return $response;
    return $build;
  }

  public function delete($comment_id) {
    $comment = $this->entityTypeManager()->getStorage('content_issue_comment')->load($comment_id);
    $comment->delete();

    $response = new AjaxResponse();
    $response->addCommand(new RemoveCommand('.content-issue-comment[data-comment-id="' .$comment_id . '"]'));
    $response->addCommand(new CloseDialogCommand());
    return $response;
  }

  public function closeModal() {
    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand());
    return $response;
  }

}
