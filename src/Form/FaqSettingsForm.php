<?php

namespace Drupal\faq_by_url\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure FAQ by URL settings.
 */
class FaqSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['faq_by_url.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'faq_by_url_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('faq_by_url.settings');

    // ===== General settings =====
    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Общие настройки'),
    ];

    $form['general']['block_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Заголовок блока FAQ'),
      '#description' => $this->t('Заголовок, отображаемый над блоком вопросов-ответов. Можно оставить пустым, чтобы не отображать заголовок.'),
      '#default_value' => $config->get('block_title') ?? 'Часто задаваемые вопросы',
      '#maxlength' => 255,
    ];

    $form['general']['empty_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Сообщение при отсутствии вопросов'),
      '#description' => $this->t('Текст, отображаемый если для текущей страницы нет вопросов-ответов. Оставьте пустым, чтобы скрыть блок полностью.'),
      '#default_value' => $config->get('empty_message') ?? '',
      '#maxlength' => 255,
    ];

    $form['general']['show_counter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Показывать нумерацию вопросов'),
      '#description' => $this->t('Если включено, перед каждым вопросом будет отображаться порядковый номер.'),
      '#default_value' => $config->get('show_counter') ?? TRUE,
    ];

    $form['general']['open_first'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Раскрывать первый вопрос по умолчанию'),
      '#description' => $this->t('Если включено, первый вопрос-ответ на странице будет раскрыт при загрузке.'),
      '#default_value' => $config->get('open_first') ?? FALSE,
    ];

    // ===== Colors =====
    $form['colors'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Цвета и оформление'),
      '#description' => $this->t('Настройте цвета блока FAQ под дизайн вашего сайта. Оставьте поле пустым, чтобы использовать значение по умолчанию. Формат: HEX (например, #e95b18) или RGB/RGBA (например, rgba(233,91,24,0.1)).'),
    ];

    $form['colors']['color_accent'] = [
      '#type' => 'color',
      '#title' => $this->t('Акцентный цвет'),
      '#description' => $this->t('Основной цвет модуля: нумерация, иконки +/−, текст вопроса при наведении/раскрытии, ссылки в ответе, рамка фокуса.'),
      '#default_value' => $config->get('color_accent') ?? '#e95b18',
      '#attributes' => ['placeholder' => '#e95b18'],
    ];

    $form['colors']['color_question_text'] = [
      '#type' => 'color',
      '#title' => $this->t('Цвет текста вопроса'),
      '#description' => $this->t('Цвет текста вопроса в обычном (неактивном) состоянии.'),
      '#default_value' => $config->get('color_question_text') ?? '#333333',
      '#attributes' => ['placeholder' => '#333333'],
    ];

    $form['colors']['color_answer_text'] = [
      '#type' => 'color',
      '#title' => $this->t('Цвет текста ответа'),
      '#description' => $this->t('Основной цвет текста в блоке ответа.'),
      '#default_value' => $config->get('color_answer_text') ?? '#555555',
      '#attributes' => ['placeholder' => '#555555'],
    ];

    $form['colors']['color_border'] = [
      '#type' => 'color',
      '#title' => $this->t('Цвет разделительных линий'),
      '#description' => $this->t('Цвет горизонтальных линий-разделителей между вопросами.'),
      '#default_value' => $config->get('color_border') ?? '#e0e0e0',
      '#attributes' => ['placeholder' => '#e0e0e0'],
    ];

    $form['colors']['color_hover_bg'] = [
      '#type' => 'color',
      '#title' => $this->t('Фон вопроса при наведении (hover)'),
      '#description' => $this->t('Цвет фона строки вопроса при наведении курсора. Используется с прозрачностью 10%.'),
      '#default_value' => $config->get('color_hover_bg') ?? '#e95b18',
      '#attributes' => ['placeholder' => '#e95b18'],
    ];

    $form['colors']['color_item_hover_bg'] = [
      '#type' => 'color',
      '#title' => $this->t('Фон элемента при наведении (весь блок)'),
      '#description' => $this->t('Цвет фона всего элемента FAQ при наведении, когда он закрыт.'),
      '#default_value' => $config->get('color_item_hover_bg') ?? '#fafafa',
      '#attributes' => ['placeholder' => '#fafafa'],
    ];

    $form['colors']['color_strong'] = [
      '#type' => 'color',
      '#title' => $this->t('Цвет жирного текста в ответе (strong)'),
      '#description' => $this->t('Цвет тега &lt;strong&gt; внутри блока ответа.'),
      '#default_value' => $config->get('color_strong') ?? '#333333',
      '#attributes' => ['placeholder' => '#333333'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('faq_by_url.settings')
      ->set('block_title', $form_state->getValue('block_title'))
      ->set('empty_message', $form_state->getValue('empty_message'))
      ->set('show_counter', $form_state->getValue('show_counter'))
      ->set('open_first', $form_state->getValue('open_first'))
      ->set('color_accent', $form_state->getValue('color_accent'))
      ->set('color_question_text', $form_state->getValue('color_question_text'))
      ->set('color_answer_text', $form_state->getValue('color_answer_text'))
      ->set('color_border', $form_state->getValue('color_border'))
      ->set('color_hover_bg', $form_state->getValue('color_hover_bg'))
      ->set('color_item_hover_bg', $form_state->getValue('color_item_hover_bg'))
      ->set('color_strong', $form_state->getValue('color_strong'))
      ->save();

    // Invalidate cache so blocks pick up new colors immediately.
    \Drupal::cache()->invalidateTags(['faq_item_list']);

    parent::submitForm($form, $form_state);
  }

}
