<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Form;

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

    $form['content_entity_id'] = [
      '#type' => 'hidden',
      '#default_value' => $form_state->getValue('content_entity_id'),
    ];

    $form['content_entity_revision_id'] = [
      '#type' => 'hidden',
      '#default_value' => $form_state->getValue('content_entity_revision_id'),
    ];

    return $form;
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
