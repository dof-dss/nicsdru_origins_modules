<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Controller;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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

  protected $closeModalRoute;

  protected $renderer;

  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
    $this->closeModalRoute = Url::fromRoute('origins_content_issue.comment.close_modal');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
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

  public function addForm(int $issue_id) {
    $issue_comment = $this->entityTypeManager()->getStorage('content_issue_comment')->create([]);

    $form_state['values']['issue_entity'] = $issue_id;
    $form = \Drupal::service('entity.form_builder')->getForm($issue_comment, 'add', $form_state);

    $form['assigned_to']['#attributes']['class'][] = 'hidden';
    $form["description"]["widget"][0]["format"]['#attributes']['class'][] = 'hidden';

    return $form;
  }


  public function editForm(int $comment_id) {
    $comment = \Drupal::entityTypeManager()->getStorage('content_issue_comment')->load($comment_id);

    $form_state['values']['issue_entity'] = $comment->get('issue_entity')->getString();
    $form_state['values']['comment_id'] = $comment_id;

    $form = \Drupal::service('entity.form_builder')->getForm($comment, 'add', $form_state);

    unset($form['actions']['delete']);

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $this->closeModalRoute,
      '#attributes' => [
        'class' => ['use-ajax', 'action-link'],
        'data-dialog-close' => 'true',
      ],
    ];

    return $form;
  }

  public function deleteConfirm(int $comment_id) {
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
          '#url' => Url::fromRoute('origins_content_issue.comment.delete', ['comment_id' => $comment_id]),
          '#attributes' => [
            'class' => ['use-ajax', 'button', 'button--primary'],
            'data-dialog-close' => 'true',
          ],
        ],
        'cancel' => [
          '#type' => 'link',
          '#title' => $this->t('Cancel'),
          '#url' => $this->closeModalRoute,
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
