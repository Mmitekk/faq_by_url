<?php

namespace Drupal\faq_by_url\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting FAQ Item entities.
 */
class FaqItemDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t(
      'Вы уверены, что хотите удалить вопрос-ответ "%label"?',
      ['%label' => $this->entity->label()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.faq_item.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Удалить');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $label = $this->entity->label();
      $this->entity->delete();

      $this->messenger()->addStatus($this->t(
        'Вопрос-ответ "%label" удалён.',
        ['%label' => $label]
      ));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t(
        'Ошибка при удалении вопрос-ответа: @message',
        ['@message' => $e->getMessage()]
      ));
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
