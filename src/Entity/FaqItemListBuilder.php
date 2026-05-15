<?php

namespace Drupal\faq_by_url\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a list builder for FAQ Item entities.
 */
class FaqItemListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'faq_item_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['question'] = $this->t('Вопрос');
    $header['urls'] = $this->t('URL страниц');
    $header['status'] = $this->t('Статус');
    $header['weight'] = $this->t('Вес');
    $header['changed'] = $this->t('Обновлено');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\faq_by_url\Entity\FaqItemInterface $entity */

    $row['id'] = $entity->id();

    // Question - truncate if too long.
    $question = $entity->getQuestion();
    $row['question'] = mb_strlen($question) > 80
      ? mb_substr($question, 0, 80) . '...'
      : $question;

    // URLs - show as list.
    $urls = $entity->getUrls();
    if (!empty($urls)) {
      $url_list = [];
      foreach ($urls as $url) {
        $url_list[] = $url;
      }
      $url_display = implode(', ', array_slice($url_list, 0, 3));
      if (count($url_list) > 3) {
        $url_display .= ' ... (+' . (count($url_list) - 3) . ')';
      }
      $row['urls'] = $url_display;
    }
    else {
      $row['urls'] = $this->t('— Не привязан —');
    }

    // Status.
    $row['status'] = $entity->isPublished()
      ? $this->t('Опубликовано')
      : $this->t('Черновик');

    // Weight.
    $row['weight'] = $entity->getWeight();

    // Changed date.
    $row['changed'] = \Drupal::service('date.formatter')->format(
      $entity->getChangedTimeAcrossTranslations(),
      'short'
    );

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSort() {
    return ['field' => 'weight', 'direction' => 'ASC'];
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_query = $this->storage->getQuery();
    $entity_query->accessCheck(TRUE);
    $entity_query->sort('weight', 'ASC');
    $entity_query->sort('id', 'ASC');
    $entity_query->pager(50);
    $ids = $entity_query->execute();

    return $this->storage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery();
    $query->accessCheck(TRUE);
    $query->sort('weight', 'ASC');
    $query->sort('id', 'ASC');
    $query->pager($this->limit);

    // Only add the filter when the value is not empty.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('Вопросы-ответы не найдены. Добавьте первый вопрос-ответ, нажав на кнопку выше.');
    return $build;
  }

}
