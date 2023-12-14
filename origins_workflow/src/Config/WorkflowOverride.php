<?php

namespace Drupal\origins_workflow\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\origins_workflow\Form\ModerationSettingsForm;

class WorkflowOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (in_array('views.view.workflow_moderation', $names)) {

      $config = \Drupal::configFactory()->get(ModerationSettingsForm::SETTINGS)->getRawData();

      foreach ($config['view_overrides'] as $display => $data) {

        // Override the Content Types filter if we have local configuration.
        $filtered_content_types = array_filter($data['filtered_node_types'] ?? [], 'is_string');

        if (!empty($filtered_content_types)) {
          $overrides['views.view.workflow_moderation']['display'][$display]['display_options']['filters']['type']['value'] = $filtered_content_types;
          $overrides['views.view.workflow_moderation']['display'][$display]['display_options']['filters']['type']['expose']['reduce'] = TRUE;
        }

        if ($data['disable']) {
          $overrides['views.view.workflow_moderation']['display'][$display]['display_options']['enabled'] = FALSE;
        }

      }
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'OriginsWorkflowConfigOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
