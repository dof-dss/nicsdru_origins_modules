<?php

declare(strict_types=1);

namespace Drupal\origins_forms\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a constraint that prevents duplicate entries in fields that
 * implement the FieldItemList interface.
 *
 * @Constraint(
 *   id = "unique_list_items",
 *   label = @Translation("Unique List Items", context = "Validation"),
 * )
 */
final class UniqueListItemsConstraint extends Constraint {

  /**
   * Constraint validation message.
   *
   * @var string
   */
  public string $message = 'Duplicate items detected. The list must contain only unique entries.';

}
