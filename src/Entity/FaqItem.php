<?php

namespace Drupal\faq_by_url\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;

/**
 * Defines the FAQ Item entity.
 *
 * @ContentEntityType(
 *   id = "faq_item",
 *   label = @Translation("FAQ Item"),
 *   label_collection = @Translation("FAQ Items"),
 *   label_singular = @Translation("FAQ item"),
 *   label_plural = @Translation("FAQ items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count FAQ item",
 *     plural = "@count FAQ items"
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\faq_by_url\Entity\FaqItemListBuilder",
 *     "form" = {
 *       "add" = "Drupal\faq_by_url\Form\FaqItemForm",
 *       "edit" = "Drupal\faq_by_url\Form\FaqItemForm",
 *       "delete" = "Drupal\faq_by_url\Form\FaqItemDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "faq_item",
 *   admin_permission = "administer faq by url",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "question",
 *     "uuid" = "uuid",
 *     "published" = "status",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "collection" = "/admin/config/content/faq-by-url",
 *     "add-form" = "/admin/config/content/faq-by-url/add",
 *     "edit-form" = "/admin/config/content/faq-by-url/{faq_item}/edit",
 *     "delete-form" = "/admin/config/content/faq-by-url/{faq_item}/delete"
 *   },
 *   field_ui_base_route = "entity.faq_item.collection"
 * )
 */
class FaqItem extends ContentEntityBase implements FaqItemInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->get('question')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setQuestion($question) {
    $this->set('question', $question);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnswer() {
    return $this->get('answer')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnswerFormat() {
    return $this->get('answer')->format;
  }

  /**
   * {@inheritdoc}
   */
  public function setAnswer($answer, $format = NULL) {
    $this->set('answer', [
      'value' => $answer,
      'format' => $format ?? 'basic_html',
    ]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrls() {
    $urls = [];
    foreach ($this->get('urls') as $item) {
      if (!empty($item->value)) {
        $urls[] = $item->value;
      }
    }
    return $urls;
  }

  /**
   * {@inheritdoc}
   */
  public function setUrls(array $urls) {
    $this->set('urls', $urls);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return (int) $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Note: Most base fields are defined in faq_by_url_entity_base_field_info()
    // to allow easier modification. Only entity keys are defined here.

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setReadOnly(TRUE);

    return $fields;
  }

}
