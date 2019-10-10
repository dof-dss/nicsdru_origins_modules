<?php

/**
 * @file
 * Contains functions Layout Builder search indexing.
 */

use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

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
    $form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.layout_page.default');
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
          // Ideally we would use use a defined text format to filter
          // the content, this will suffice for the time being.
          $content = strip_tags(\Drupal::service('renderer')->render($build));
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
