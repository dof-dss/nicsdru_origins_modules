<?php

declare(strict_types=1);

namespace Drupal\origins_help\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for Origins Help AJAX dialog actions.
 */
final class OriginsHelpDialogController extends ControllerBase {

  /**
   * Opens the Origins Help modal dialog.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response that opens the dialog.
   */
  public function modal(): AjaxResponse {
    $content = [
      '#markup' => '<p>This modal opened from the final Drupal Tour step.</p>',
    ];

    $response = new AjaxResponse();
    $response->addCommand(
      new OpenModalDialogCommand(
        'Origins Help Modal',
        $content,
        ['width' => '700'],
      )
    );

    return $response;
  }

  /**
   * Closes the currently open modal dialog.
   *
   * Used as the AJAX target for the "Close" link shown after feedback is sent.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response that closes the dialog.
   */
  public function closeModal(): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

}
