<?php

namespace Drupal\course_enrollment\Plugin\Field\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'difficulty_badge' formatter.
 *
 * @FieldFormatter(
 *   id = "difficulty_badge",
 *   label = @Translation("Difficulty badge"),
 *   field_types = {
 *     "list_string"
 *   }
 * )
 */
class DifficultyBadgeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(
    FieldItemListInterface $items,
    $langcode,
  ) {
    $elements = [];

    /*
     * Get allowed values.
     *
     * Drupal will provide the translated labels when the
     * field configuration has been translated.
     */
    $allowed_values = $this->fieldDefinition
      ->getFieldStorageDefinition()
      ->getSetting('allowed_values');

    foreach ($items as $delta => $item) {

      /*
       * Actual stored database value.
       */
      $value = (string) $item->value;

      $label = $allowed_values[$value] ?? $value;

      $normalized_value = strtolower($value);

      if (str_contains($normalized_value, 'beginner')) {
        $difficulty = 'beginner';
      }
      elseif (str_contains($normalized_value, 'intermediate')) {
        $difficulty = 'intermediate';
      }
      elseif (str_contains($normalized_value, 'advanced')) {
        $difficulty = 'advanced';
      }
      else {
        $difficulty = 'unknown';
      }

      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $label,
        '#attributes' => [
          'class' => [
            'difficulty-badge',
            'difficulty-badge--' . $difficulty,
          ],
        ],
      ];
    }

    return $elements;
  }

}