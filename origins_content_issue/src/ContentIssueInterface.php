<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a content issue entity type.
 */
interface ContentIssueInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
