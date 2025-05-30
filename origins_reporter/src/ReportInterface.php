<?php

declare(strict_types=1);

namespace Drupal\origins_reporter;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a report entity type.
 */
interface ReportInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
