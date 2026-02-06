<?php

declare(strict_types=1);

namespace Drupal\origins_qa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides endpoints for the QA API service.
 */
final class QaApiController extends ControllerBase {

  /**
   * The filepath to the invalid token list.
   *
   * @var string
   */
  protected $invalidTokensFilepath;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * The server token.
   *
   * @var string
   */
  protected readonly string $serverToken;

  /**
   * The request token.
   *
   * @var string
   */
  protected readonly string $requestToken;

  /**
   * Constructs a QaEndpointController object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   The logger channel factory service.
   */
  public function __construct(Request $request, LoggerChannelFactory $logger) {
    $this->request = $request;
    $this->logger = $logger->get('origins_qa');
    $this->invalidTokensFilepath = Settings::get('file_private_path') . '/origins_qa_invalid_tokens.txt';
    $this->serverToken = (string) getenv('ORIGINS_QA_API_TOKEN');
    $this->requestToken = \Drupal::request()->get('token');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('logger.factory'),
    );
  }

  /**
   * Verify API token.
   *
   * @return bool|\Symfony\Component\HttpFoundation\JsonResponse
   *   True if valid, JSON response on error.
   */
  private function isValidToken(): bool|JsonResponse {

    if (empty($this->requestToken)) {
      return new JsonResponse('Request token not set', 400);
    }

    if (empty($this->serverToken)) {
      return new JsonResponse('Server token not set', 400);
    }

    // If we're on the production environment reject the request.
    if (getenv('PLATFORM_BRANCH') === 'main') {
      $this->logger->warning("Origins QA module is enabled and should NOT be for production environments.");
      return new JsonResponse('Invalid environment', 405);
    }

    // Check if the token is in the invalid list.
    if (file_exists($this->invalidTokensFilepath)) {
      $invalid_tokens = str_getcsv(file_get_contents($this->invalidTokensFilepath));

      if (in_array($this->requestToken, $invalid_tokens)) {
        return new JsonResponse('Invalid token', 403);
      }
    }

    // Check we have an HTTPS connection.
    if (!$this->request->isSecure()) {
      // Add the token to the invalid list if it was passed via
      // an unencrypted HTTP connection.
      if (file_exists($this->invalidTokensFilepath)) {
        $invalid_tokens = str_getcsv(file_get_contents($this->invalidTokensFilepath));
        $invalid_tokens[] = $this->requestToken;
      }
      else {
        $invalid_tokens = [$this->requestToken];
      }

      $file_data = implode(',', $invalid_tokens);
      if (file_put_contents($this->invalidTokensFilepath, $file_data) === FALSE) {
        $this->logger->warning("Unable to write QA API invalid tokens file. Check filesystem permissions.");
      }

      return new JsonResponse('Token invalid - insecure request', 400);
    }

    // Reject if the token is incorrect.
    if ($this->requestToken != $this->serverToken) {
      return new JsonResponse('Invalid token', 401);
    }

    return TRUE;
  }

  /**
   * Enable QA accounts.
   */
  public function setQaUsersStatus($status) {
    if (($response = $this->isValidToken()) !== TRUE) {
      return $response;
    }

    $response = new JsonResponse();
    $qac = new QaAccountsManager();

    if ($status === 'enable') {
      $qac->toggleAll('enable');
      return $response->setStatusCode(200);
    }
    else {
      $qac->toggleAll('disable');
      return $response->setStatusCode(200);
    }
  }

  /**
   * Clear flood control.
   */
  public function unFlood() {
    if (($response = $this->isValidToken()) !== TRUE) {
      return $response;
    }

    $response = new JsonResponse();

    if (\Drupal::service('module_handler')->moduleExists('flood_control')) {
      /** @var \Drupal\flood_control\FloodUnblockManagerInterface $flood_unblock_manager */
      $flood_unblock_manager = \Drupal::service('flood_control.flood_unblock_manager');

      $events = $flood_unblock_manager->getEvents();
      foreach ($events as $key => $event) {
        $fids = $flood_unblock_manager->getEventIds($key);
        foreach ($fids as $fid) {
          $flood_unblock_manager->floodUnblockClearEvent($key . ':' . $fid);
        }
      }

      return $response->setStatusCode(200);

    }
    else {
      return new JsonResponse('Flood control module not enabled', 501);
    }
  }

}
