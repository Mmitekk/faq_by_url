<?php

namespace Drupal\faq_by_url\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining FAQ Item entities.
 */
interface FaqItemInterface extends ContentEntityInterface, EntityPublishedInterface {

  /**
   * Gets the question text.
   *
   * @return string
   *   The question text.
   */
  public function getQuestion();

  /**
   * Sets the question text.
   *
   * @param string $question
   *   The question text.
   *
   * @return \Drupal\faq_by_url\Entity\FaqItemInterface
   *   The called entity.
   */
  public function setQuestion($question);

  /**
   * Gets the answer text.
   *
   * @return string
   *   The answer text.
   */
  public function getAnswer();

  /**
   * Gets the answer text format.
   *
   * @return string
   *   The text format ID.
   */
  public function getAnswerFormat();

  /**
   * Sets the answer text.
   *
   * @param string $answer
   *   The answer text.
   * @param string|null $format
   *   The text format ID.
   *
   * @return \Drupal\faq_by_url\Entity\FaqItemInterface
   *   The called entity.
   */
  public function setAnswer($answer, $format = NULL);

  /**
   * Gets the list of URLs this FAQ item is assigned to.
   *
   * @return string[]
   *   Array of URL paths.
   */
  public function getUrls();

  /**
   * Sets the URLs for this FAQ item.
   *
   * @param string[] $urls
   *   Array of URL paths.
   *
   * @return \Drupal\faq_by_url\Entity\FaqItemInterface
   *   The called entity.
   */
  public function setUrls(array $urls);

  /**
   * Gets the weight value.
   *
   * @return int
   *   The weight.
   */
  public function getWeight();

  /**
   * Sets the weight value.
   *
   * @param int $weight
   *   The weight.
   *
   * @return \Drupal\faq_by_url\Entity\FaqItemInterface
   *   The called entity.
   */
  public function setWeight($weight);

  /**
   * Gets the creation timestamp.
   *
   * @return int
   *   Creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the creation timestamp.
   *
   * @param int $timestamp
   *   Creation timestamp.
   *
   * @return \Drupal\faq_by_url\Entity\FaqItemInterface
   *   The called entity.
   */
  public function setCreatedTime($timestamp);

}
