<?php

declare(strict_types=1);

namespace Drupal\origins_forms\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItemInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Unique List Items constraint.
 */
final class UniqueListItemsConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $items, Constraint $constraint): void {
    if (!$items instanceof FieldItemListInterface) {
      throw new \InvalidArgumentException(
        sprintf('The validated value must be instance of \Drupal\Core\Field\FieldItemListInterface, %s was given.', get_debug_type($items))
      );
    }

    if (!$items->isEmpty()) {

      $item_values = [];

      foreach ($items as $delta => $item) {
        $value = $item->getValue();
        if ($item instanceof EntityReferenceItemInterface) {
          $item_values[] = $value['target_id'];
        }
        else {
          $item_values[] = $value;
        }
      }

      $item_values = array_unique($item_values);

      if (count($item_values) <  count($items)) {
        $this->context->addViolation($constraint->message);
      }
    }
  }

}
