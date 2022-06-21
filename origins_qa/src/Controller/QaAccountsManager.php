<?php

namespace Drupal\origins_qa\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for Origins QA.
 */
class QaAccountsManager extends ControllerBase {

  /**
   * Returns a list of QA accounts.
   */
  public function list() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
