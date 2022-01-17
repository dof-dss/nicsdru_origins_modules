<?php

namespace Drupal\origins_media\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;
use Drupal\embed\EmbedButtonInterface;
use Drupal\entity_embed\Form\EntityEmbedDialog;

/**
 * Extend the base entity embed dialog class.
 *
 * Allows us to ensure we can pre-set a step for location entity embedding.
 */
class OriginsEntityEmbedDialog extends EntityEmbedDialog {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_embed_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EditorInterface $editor = NULL, EmbedButtonInterface $embed_button = NULL) {
    // Pass in our pre-set form_state into the origin entity embed form builder
    // so we can get the correct form output in the state we need it to be in
    // for location embeds (either new map or replace existing map).
    if ($embed_button->id() == 'location') {
      $form_state->set('step', 'select');
    }

    return parent::buildForm($form, $form_state, $editor, $embed_button);
  }

}
