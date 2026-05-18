<?php

namespace Drupal\origins_tour\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;

/**
 * Controller for AJAX dialog example.
 */
class OriginsTourDialogController extends ControllerBase {

  public function modal()
  {

    $content = [
      '#markup' => '
        <p>This modal opened from the final Drupal Tour step.</p>
      ',
    ];

    $response = new AjaxResponse();

    $response->addCommand(
      new OpenModalDialogCommand(
        'Origins Tour Modal',
        $content,
        ['width' => '700']
      )
    );

    return $response;
  }

}
