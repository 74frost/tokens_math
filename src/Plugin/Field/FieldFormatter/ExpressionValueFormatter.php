<?php

namespace Drupal\tokens_math\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'tokens_math_value_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "tokens_math_value",
 *   label = @Translation("Evaluated Expression"),
 *   field_types = {
 *     "tokens_math"
 *   }
 * )
 */
class ExpressionValueFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'always_evaluate' => FALSE
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['always_evaluate'] = [
      '#type' => 'checkbox',
      '#title' => t('Пересчитывать каждый раз?'),
      '#description' => t('Если включено - результат пересчитывается при каждом отображении (если вы используете токены которые меняются'),
      '#default_value' => $this->getSetting('always_evaluate'),
      '#empty_option' => t('- Select wrapper -'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Always Evaluate : @always_evaluate', ['@always_evaluate' => $this->getSetting('always_evaluate') ? 'Yes' : 'No']);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $value = '';

    // We're forcing single cardinality for this field type in a form alter
    // so we just need to check the first item, and then work with that if it's
    // not empty.
    if ($this->getSetting('always_evaluate')) {
      // If there's no value yet, we append an item so that we can grab
      if (empty($items[0])) {
        $items->appendItem();
      }
      $value = $items[0]->evaluateExpression($items[0]->getFieldDefinition()->getSetting('expression'));
    }
    elseif (!empty($items[0])) {
      $value = $items[0]->value;
    }

    $element = [
      '#markup' => $value,
    ];

    return $element;
  }

}
