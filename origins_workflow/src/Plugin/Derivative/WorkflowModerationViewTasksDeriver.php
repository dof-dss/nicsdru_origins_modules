<?php

namespace Drupal\origins_workflow\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\views\Views;

class WorkflowModerationViewTasksDeriver extends DeriverBase {

  /**
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    $view = Views::getView('workflow_moderation');
    $displays = $view->storage->get('display');
    unset($displays['default']);

    foreach ($displays as $display => $data) {
      if ($data["display_options"]['enabled'] ?? true) {
        $this->derivatives['origins_workflow.' . $display . '_tab'] = $base_plugin_definition;
        $this->derivatives['origins_workflow.' . $display . '_tab']['title'] = $data['display_title'];
        $this->derivatives['origins_workflow.' . $display . '_tab']['route_name'] = 'view.workflow_moderation.' . $display;
        $this->derivatives['origins_workflow.' . $display . '_tab']['parent_id'] = 'system.admin_content';
        $this->derivatives['origins_workflow.' . $display . '_tab']['weight'] = $data['position'];
      }
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
