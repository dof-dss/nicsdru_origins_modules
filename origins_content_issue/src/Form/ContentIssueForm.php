<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use phpDocumentor\Reflection\Types\Parent_;

/**
 * Form controller for the content issue entity edit forms.
 */
final class ContentIssueForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    /** @var \Drupal\origins_content_issue\Entity\ContentIssue $entity */
    $entity = $form_state->getFormObject()->getEntity();

    if ($entity->isNew()) {
      $form["state"]["#type"] = 'hidden';
    }

    $form['advanced']['#type'] = 'hidden';

    $form['content_entity_id'] = [
      '#type' => 'hidden',
      '#default_value' => $form_state->getValue('content_entity_id') ?? $entity->get('content_entity_id')->getString(),
    ];

    $form['content_entity_revision_id'] = [
      '#type' => 'hidden',
      '#default_value' => $form_state->getValue('content_entity_revision_id') ?? $entity->get('content_entity_revision_id')->getString(),
    ];

    $form['uid']['#attributes']['class'][] = 'hidden';
    $form['created']['#attributes']['class'][] = 'hidden';
    $form['revision_information']['#attributes']['class'][] = 'hidden';
    $form['comments']['#attributes']['class'][] = 'hidden';
    $form['status']['#attributes']['class'][] = 'hidden';
    $form["description"]["widget"][0]["format"]['#attributes']['class'][] = 'hidden';

    // Make sure it's an AJAX form.
    $form['actions']['submit']['#ajax'] = [
      'callback' => '::ajaxSubmit',
      'event' => 'click',
      'progress' => [
        'type' => 'throbber',
        'message' => t('Saving...'),
      ],
    ];

    // Remove redirect to prevent page reloading.
    $form['#submit'][] = '::formSubmit';

    $form_state->setRedirect(NULL);

    return $form;
  }

  public function formSubmit($form, FormStateInterface $form_state) {
    $form_state->disableRedirect(); // Ensure no redirect happens
  }

  public function ajaxSubmit($form, FormStateInterface $form_state) {
    $issue_id = $form_state->getformObject()->getEntity()->id();

    $issueManager = \Drupal::service('content_issue.manager');
    $build = $issueManager->render($issue_id);

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#content-issue-dashboard-aside article', $build, []));
    $response->addCommand(new InvokeCommand('#content-issue-dashboard-aside', 'addClass', ['open']));
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->toLink()->toString()];
    $logger_args = [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New content issue %label has been created.', $message_args));
        $this->logger('origins_content_issue')->notice('New content issue %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The content issue %label has been updated.', $message_args));
        $this->logger('origins_content_issue')->notice('The content issue %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

}
