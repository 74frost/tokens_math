<?php

namespace Drupal\tokens_math\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Webit\Util\EvalMath\EvalMath;

/**
 * Plugin implementation of the 'tokens_math' field type.
 *
 * @FieldType(
 *   id = "tokens_math",
 *   label = @Translation("Expression"),
 *   description = @Translation("Create a field value calculated by evaluating an expression that can include tokens."),
 *   default_widget = "tokens_math_default",
 *   default_formatter = "tokens_math_value"
 * )
 */
class ExpressionItem extends FieldItemBase {


  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'expression' => '',
      'default_zero' => TRUE,
      'suppress_errors' => TRUE,
      'debug_mode' => FALSE,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $entity_type_id = $this->getEntity()->getEntityTypeId();

    $element['expression'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Формула'),
      '#description' => $this->t('Введите математическое выражение для вычисления значения для этого поля. Выражения могут включать основные операторы <code>(+-*/^)</code> воспользуйтесь браузером токенов для подбора нужного вам значения для вычислений'),
      '#default_value' => $this->getSetting('expression'),
      '#element_validate' => ['token_element_validate'],
      '#token_types' => [$entity_type_id],
      '#required' => TRUE,
    ];

    $element['token_tree_link'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => [$entity_type_id],
    ];

    $element['default_zero'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Заменить пустые значения на 0?'),
      '#description' => $this->t('Если значение выбраного токена отсудствует - рекомендуется использовать эту функцию во избежание ошибок, но помните - делить на 0 нельзя'),
      '#default_value' => $this->getSetting('default_zero'),
    ];

    $element['suppress_errors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Supress Errors'),
      '#description' => $this->t('Если включено - очищает поле при возникновении ошибок'),
      '#default_value' => $this->getSetting('suppress_errors'),
    ];

    if (\Drupal::moduleHandler()->moduleExists('devel')) {
      $element['debug_mode'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Debug Mode'),
        '#description' => $this->t('Покажет дополнительную информацию об ошибке'),
        '#default_value' => $this->getSetting('debug_mode'),
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $this->value = $this->evaluateExpression($this->value);
  }

  /**
   * Evaluate the expression for the field value.
   */
  public function evaluateExpression($expression) {
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityTypeId();
    // Replace line breaks
    $expression = str_replace(["\r", "\n"], '', $expression);
    $original_expression = $expression;

    // Replace the tokens
    $token_service = \Drupal::token();
    $expression = $token_service->replace($expression,
      [$entity_type => $entity],
      ['clear' => FALSE]
    );

    // Add back the default values for any tokens still there
    $remaining_tokens = $token_service->scan($expression);
    foreach ($remaining_tokens as $type => $tokens) {
      foreach ($tokens as $name => $token) {
        $matches = [];

        // First process any items with default values
        if (preg_match_all('/' . preg_quote($token) . '\{(.*?)\}/', $expression, $matches)) {
          // Replace any matches with the default value
          foreach ($matches[0] as $index => $match) {
            $expression = preg_replace('/' . preg_quote($match) . '/', $matches[1][$index], $expression);
          }
        }

        // We may also have instances of this token without default values, so
        // we process those as well
        if (preg_match('/' . preg_quote($token) . '/', $expression)) {
          if ($this->getSetting('default_zero')) {
            // We're using the default_zero
            $expression = preg_replace('/' . preg_quote($token) . '/', 0, $expression);
          }
        }

        // Clean up any remaining default value wrappers
        $expression = preg_replace('/\{.*?\}/', '', $expression);
      }
    }

    // Evaluate the final expression
    $math = new EvalMath;
    $math->suppress_errors = $this->getSetting('suppress_errors');
    $value = $math->evaluate($expression);

    // Support debugging expressions with devel module
    if (\Drupal::moduleHandler()->moduleExists('devel') && $this->getSetting('debug_mode')) {
      $debug = [
        'Original Expression:' => $original_expression,
        'Token Replaced Expression:' => $expression,
        'Expression Result:' => $value
      ];
      dpm($debug, ' Debug Output');
    }

    return $value;
  }

}
