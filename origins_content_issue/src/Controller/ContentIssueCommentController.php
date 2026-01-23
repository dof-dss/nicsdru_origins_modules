<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;

/**
 * Returns responses for Origins content issue routes.
 */
final class ContentIssueCommentController extends ControllerBase {

  /**
   * Drupal renderer service instance.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

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

  /**
   * Displays a create new comment form.
   *
   * @param int|string $issue_id
   *   The id of the issue this comment is for.
   */
  public function addForm(int|string $issue_id): array {
    $issue_comment = $this->entityTypeManager()->getStorage('content_issue_comment')->create([]);

    $form_state['values']['issue_entity'] = $issue_id;
    // @phpstan-ignore-next-line.
    $form = \Drupal::service('entity.form_builder')->getForm($issue_comment, 'add', $form_state);

    $form['assigned_to']['#attributes']['class'][] = 'hidden';
    $form["description"]["widget"][0]["format"]['#attributes']['class'][] = 'hidden';

    return $form;
  }

  /**
   * Displays an edit form for comments.
   *
   * @param int|string $comment_id
   *   The id of the comment to edit.
   */
  public function editForm(int|string $comment_id): array {
    // @phpstan-ignore-next-line.
    $comment = \Drupal::entityTypeManager()->getStorage('content_issue_comment')->load($comment_id);

    $form_state['values']['issue_entity'] = $comment->get('issue_entity')->getString();
    $form_state['values']['comment_id'] = $comment_id;

    // @phpstan-ignore-next-line.
    $form = \Drupal::service('entity.form_builder')->getForm($comment, 'add', $form_state);

    unset($form['actions']['delete']);

    return $form;
  }

  /**
   * Displays a delete confirmation form for comments.
   *
   * @param int|string $comment_id
   *   The id of the comment to delete.
   */
  public function deleteConfirm(int|string $comment_id): array {
    $form = [
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
          '#url' => Url::fromRoute('origins_content_issue.comment.delete', ['comment_id' => $comment_id]),
          '#attributes' => [
            'class' => ['use-ajax', 'button', 'button--primary'],
            'data-dialog-close' => 'true',
          ],
        ],
        'cancel' => [
          '#type' => 'link',
          '#title' => $this->t('Cancel'),
          '#url' => Url::fromRoute('origins_content_issue.comment.close_modal'),
          '#attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-close' => 'true',
          ],
        ],
      ],
    ];

    $form['comment_id'] = [
      '#type' => 'hidden',
      '#value' => $comment_id,
    ];

    return $form;
  }

  /**
   * Deletes a comment and closes the modal.
   *
   * @param int|string $comment_id
   *   The id of the comment to delete.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response to the delete request.
   */
  public function delete($comment_id): AjaxResponse {
    $comment = $this->entityTypeManager()->getStorage('content_issue_comment')->load($comment_id);
    $comment->delete();

    $response = new AjaxResponse();
    $response->addCommand(new RemoveCommand('.content-issue-comment[data-comment-id="' . $comment_id . '"]'));
    $response->addCommand(new CloseDialogCommand());
    return $response;
  }

  /**
   * Closes an ajax modal.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response to close modal.
   */
  public function closeModal(): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand());
    return $response;
  }

}
