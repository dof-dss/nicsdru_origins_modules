<?php

/**
 * @file
 * Contains functions Layout Builder search indexing.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Handler function for hook_form_FORM_ID_alter().
 */
function layout_builder_search_node_type_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $node = $form_state->getFormObject()->getEntity();
  $node_type = $node->id();
  $layout_builder_displays = entity_layout_builder_displays($node_type);

  if ($layout_builder_displays) {

    $form['lb_search_settings'] = [
      '#type' => 'details',
      '#title' => t('Layout Builder search indexing'),
      '#group' => 'additional_settings',
    ];

    $form['lb_search_settings']['lb_search_enable'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable search indexing'),
      '#default_value' => $node->getThirdPartySetting('origins_common', 'lb_search_enable', NULL),
    ];

    $form['lb_search_settings']['lb_search_options'] = [
      '#type' => 'container',
      '#states' => [
        'invisible' => [
          ':input[name="lb_search_enable"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['lb_search_settings']['lb_search_options']['lb_search_index_display'] = [
      '#type' => 'select',
      '#title' => t('Select display mode'),
      '#description' => t('Layout Builder enabled display mode to render for search index.'),
      '#options' => $layout_builder_displays,
      '#default_value' => $node->getThirdPartySettings('origins_common', 'lb_search_index_display', NULL),

    ];

    $form['lb_search_settings']['lb_search_options']['lb_search_notes'] = [
      '#markup' => '<b>NOTE:</b> You will need to add the \'lb_search_content\' field to your Search API index fields.',
    ];

    $form['#entity_builders'][] = 'layout_builder_search_node_type_form_builder';
  }
}

/**
 * Entity builder for the layout_builder_search_node_type_form_alter().
 */
function layout_builder_search_node_type_form_builder($entity_type, $type, &$form, $form_state) {
  $type->setThirdPartySetting('origins_common', 'lb_search_enable', $form_state->getValue('lb_search_enable'));
  $type->setThirdPartySetting('origins_common', 'lb_search_index_display', $form_state->getValue('lb_search_index_display'));
}

/**
 * Handler function for hook_form_BASE_FORM_ID_alter().
 */
function layout_builder_search_node_edit_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $form['field_lb_search_content']['#disabled'] = TRUE;
  // Determine node type from node/add or node/edit forms.
  $node_type = \Drupal::routeMatch()->getParameter('node_type');
  if (!empty($node_type)) {
    $node_type = $node_type->id();
  }
  else {
    $node = \Drupal::routeMatch()->getParameter('node');
    $node_type = $node->getType();
  }

  // Get the search index content storage field.
  $entity_type = \Drupal::entityTypeManager()->getStorage('node_type')->load($node_type);
  $settings = $entity_type->getThirdPartySettings('origins_common');

  // Ensure we have settings configured from the entity type form.
  $search_display_present = !empty($settings['lb_search_index_display']);

  if (!empty($form['field_lb_search_enable']) && !empty($form['field_lb_search_content']) && $search_display_present) {
    $form['layout_builder_search_indexing'] = [
      '#type' => 'details',
      '#title' => t('Layout builder search indexing'),
      '#group' => 'advanced',
      '#open' => FALSE,
    ];

    // Alter the field titles for displaying on the node edit form.
    $form['field_lb_search_enable']['widget']['value']['#title'] = t('Enable search indexing for this node.');
    $form['layout_builder_search_indexing'][] = $form['field_lb_search_enable'];
    unset($form['field_lb_search_enable']);

    $form['field_lb_search_content']['widget'][0]['value']['#title'] = t('Search index contents.');
    $form['layout_builder_search_indexing'][] = $form['field_lb_search_content'];
    unset($form['field_lb_search_content']);
  }

}

/**
 * Creates the required fields for layout builder search indexing.
 *
 * @param \Drupal\node\Entity\NodeType $entity
 *   The entity type to process.
 */
function layout_builder_search_node_type_presave(NodeType $entity) {
  $lb_search_enabled = $entity->getThirdPartySetting('origins_common', 'lb_search_enable', FALSE);
  if ($lb_search_enabled) {
    // If the 'enable search index' doesn't exist for this node, create it.
    $field = FieldConfig::loadByName('node', $entity->id(), 'field_lb_search_enable');
    if (!$field) {
      // Create the enabled field for this entity.
      $field_storage = FieldStorageConfig::loadByName('node', 'field_lb_search_enable');
      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $entity->id(),
        'label' => t('LB Search enabled'),
      ]);
      $field->setTranslatable(FALSE);
      $field->save();
    }

    $field = FieldConfig::loadByName('node', $entity->id(), 'field_lb_search_content');
    if (!$field) {
      // Create the content field for this entity.
      $field_storage = FieldStorageConfig::loadByName('node', 'field_lb_search_content');
      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $entity->id(),
        'label' => t('LB Search content'),
        'description' => t('Populated on node save.'),
      ]);
      $field->setTranslatable(FALSE);
      $field->save();
    }

    // Enable the fields in the form display.
    $form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.' . $entity->id() . '.default');
    $form_display->setComponent('field_lb_search_enable', [
      'type' => 'boolean_checkbox',
      'label' => 'above',
      'settings' => ['link_to_entity' => 'false'],
    ]);

    $form_display->setComponent('field_lb_search_content', [
      'type' => 'string_textarea',
      'label' => 'above',
      'settings' => ['link_to_entity' => 'false'],
    ])->save();

  }
  else {
    // Delete the 'enable search index' field from this entity if it exists.
    $field = FieldConfig::loadByName('node', $entity->id(), 'field_lb_search_enable');
    if ($field) {
      $field->delete();
    }

    $field = FieldConfig::loadByName('node', $entity->id(), 'field_lb_search_content');
    if ($field) {
      $field->delete();
    }
  }
}

/**
 * Renders and stores the layout builder display to the search index field.
 *
 * @param \Drupal\node\Entity\Node $entity
 *   The entity to process.
 */
function layout_builder_search_node_presave(Node $entity) {
  if ($entity->hasField('field_lb_search_enable')) {
    $search_enable = (bool) $entity->get('field_lb_search_enable')->getValue();
    if ($search_enable) {
      $entity_storage = \Drupal::entityTypeManager()->getStorage('node_type');
      $entity_type = $entity_storage->load($entity->getType());
      $settings = $entity_type->getThirdPartySettings('origins_common');
      $search_index_display = $settings['lb_search_index_display'] ?? NULL;

      if ($search_index_display) {
        // Check the requested search index storage field exists.
        if ($entity->hasField('field_lb_search_content')) {
          $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
          $build = $view_builder->view($entity, $search_index_display);
          // Rendering a new entity outside of preview will likely trip a fatal error
          // as the entity will not have fully saved. However, swapping to use preview
          // mode allows us to gather more or less the equivalent then remove the markup
          // to store the values against the field_lb_search_content field.
          $build['#' . $entity->getEntityTypeId()]->in_preview = $entity->isNew();
          // Ideally we would use use a defined text format to filter
          // the content, this will suffice for the time being.
          $content = strip_tags(\Drupal::service('renderer')->render($build));
          // Remove excess whitespace.
          $content = preg_replace('/\s\s+/', ' ', $content);
          $entity->set('field_lb_search_content', $content);
        }
      }
    }
  }
}

/**
 * Layout Builder enabled displays.
 *
 * @param string $node_type
 *   Content type to return display modes for.
 *
 * @return array
 *   Array of Layout Builder enabled display modes.
 */
function entity_layout_builder_displays($node_type) {
  $entity_displays = \Drupal::service('entity_display.repository');
  $entity_view_displays = \Drupal::entityTypeManager()->getStorage('entity_view_display');
  $layout_builder_displays = [];
  $display_modes = $entity_displays->getViewModeOptionsByBundle('node', $node_type);

  foreach ($display_modes as $id => $mode) {
    $display_id = 'node.' . $node_type . '.' . $id;
    $display = $entity_view_displays->load($display_id);
    if ($display instanceof LayoutBuilderEntityViewDisplay) {
      $settings = $display->getThirdPartySettings('layout_builder');
      if (array_key_exists('enabled', $settings) && $settings['enabled'] == TRUE) {
        $layout_builder_displays[$display->getMode()] = $display->getMode();
      }
    }
  }
  return $layout_builder_displays;
}
