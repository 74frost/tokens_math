<?php

namespace Drupal\tokens_math\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'tokens_math_widget' widget.
 *
 * @FieldWidget(
 *   id = "tokens_math_default",
 *   label = @Translation("Field Token Math"),
 *   field_types = {
 *     "tokens_math"
 *   }
 * )
 */
class ExpressionDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // Need to set a value so that the preSave triggers on the field item
    // definition.
    $element['value'] = [
      '#type' => 'value',
      '#value' => $this->getFieldSetting('expression'),
    ];

    return $element;
  }

}
