<?php

declare(strict_types=1);

namespace Drupal\origins_qa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
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
   * Enable QA accounts.
   */
  public function setQaUsersStatus($status, $token) {
    // If we're on the production environment reject the request.
    if (getenv('PLATFORM_BRANCH') === 'main') {
      $this->logger->warning("Origins QA module is enabled and should NOT be for production environments.");
      return new JsonResponse(NULL, 405);
    }

    // Check if the token is in the invalid list.
    if (file_exists($this->invalidTokensFilepath)) {
      $invalid_tokens = str_getcsv(file_get_contents($this->invalidTokensFilepath));

      if (in_array($token, $invalid_tokens)) {
        return new JsonResponse(NULL, 403);
      }
    }

    // Check we have an HTTPS connection.
    if (!$this->request->isSecure()) {
      // Add the token to the invalid list if it was passed via
      // an unencrypted HTTP connection.
      if (file_exists($this->invalidTokensFilepath)) {
        $invalid_tokens = str_getcsv(file_get_contents($this->invalidTokensFilepath));
        $invalid_tokens[] = $token;
      }
      else {
        $invalid_tokens = [$token];
      }

      $file_data = implode(',', $invalid_tokens);
      if (file_put_contents($this->invalidTokensFilepath, $file_data) === FALSE) {
        $this->logger->warning("Unable to write QA API invalid tokens file. Check filesystem permissions.");
      }

      return new JsonResponse(NULL, 400);
    }

    // Reject if the token is incorrect.
    if ($token != getenv('ORIGINS_QA_API_TOKEN')) {
      return new JsonResponse(NULL, 401);
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

}
