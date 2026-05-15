/**
 * @file
 * JavaScript behaviors for the FAQ by URL accordion block.
 *
 * Provides smooth expand/collapse animation with accessible
 * keyboard navigation and Schema.org SEO support.
 */

(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * Toggle FAQ item open/closed state.
   *
   * When opening an item, all other items in the same wrapper are closed
   * so that only one FAQ answer is visible at a time (exclusive accordion).
   *
   * @param {HTMLElement} item - The .faq-by-url-item element.
   * @param {HTMLElement} wrapper - The .faq-by-url-wrapper parent element.
   * @param {boolean} forceState - Optional: true to open, false to close.
   */
  function toggleFaqItem(item, wrapper, forceState) {
    var isOpen = item.classList.contains('faq-by-url-item--open');
    var shouldOpen = typeof forceState === 'boolean' ? forceState : !isOpen;

    var answer = item.querySelector('.faq-by-url-answer');
    var question = item.querySelector('.faq-by-url-question');

    if (!answer || !question) {
      return;
    }

    // When opening, close all other items in the same wrapper first.
    if (shouldOpen && !isOpen && wrapper) {
      var openItems = wrapper.querySelectorAll('.faq-by-url-item--open');
      openItems.forEach(function (openItem) {
        if (openItem !== item) {
          toggleFaqItem(openItem, null, false);
        }
      });
    }

    if (shouldOpen && !isOpen) {
      // Open the item.
      item.classList.add('faq-by-url-item--open');
      question.setAttribute('aria-expanded', 'true');

      // Calculate the natural height for animation.
      answer.style.display = 'block';
      var height = answer.scrollHeight;
      answer.style.maxHeight = '0px';
      answer.style.overflow = 'hidden';

      // Force reflow.
      answer.offsetHeight; // eslint-disable-line no-unused-expressions

      answer.style.transition = 'max-height 0.35s ease-in-out';
      answer.style.maxHeight = height + 'px';

      // After animation, remove max-height to allow content changes.
      var onTransitionEnd = function () {
        answer.style.maxHeight = 'none';
        answer.style.overflow = 'visible';
        answer.removeEventListener('transitionend', onTransitionEnd);
      };
      answer.addEventListener('transitionend', onTransitionEnd);

    } else if (!shouldOpen && isOpen) {
      // Close the item.
      item.classList.remove('faq-by-url-item--open');
      question.setAttribute('aria-expanded', 'false');

      // Set current height explicitly for animation.
      answer.style.maxHeight = answer.scrollHeight + 'px';
      answer.style.overflow = 'hidden';

      // Force reflow.
      answer.offsetHeight; // eslint-disable-line no-unused-expressions

      answer.style.transition = 'max-height 0.3s ease-in-out';
      answer.style.maxHeight = '0px';

      var onClosed = function () {
        if (!item.classList.contains('faq-by-url-item--open')) {
          answer.style.display = 'none';
          answer.style.maxHeight = '';
          answer.style.overflow = '';
        }
        answer.removeEventListener('transitionend', onClosed);
      };
      answer.addEventListener('transitionend', onClosed);
    }
  }

  /**
   * Initialize FAQ accordion behavior on the page.
   *
   * @param {HTMLElement} context - The DOM context to search within.
   */
  function initFaqAccordion(context) {
    var items = context.querySelectorAll('.faq-by-url-item');

    items.forEach(function (item) {
      // Skip if already initialized.
      if (item.dataset.faqInitialized) {
        return;
      }
      item.dataset.faqInitialized = 'true';

      var question = item.querySelector('.faq-by-url-question');

      if (!question) {
        return;
      }

      // Click handler.
      question.addEventListener('click', function (e) {
        e.preventDefault();
        var wrapper = item.closest('.faq-by-url-wrapper');
        toggleFaqItem(item, wrapper);
      });

      // Keyboard handler (Enter and Space).
      question.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ' || e.keyCode === 13 || e.keyCode === 32) {
          e.preventDefault();
          var wrapper = item.closest('.faq-by-url-wrapper');
          toggleFaqItem(item, wrapper);
        }
      });
    });

    // Add Schema.org structured data for SEO if items exist.
    addSchemaMarkup(context);
  }

  /**
   * Adds Schema.org FAQPage structured data as JSON-LD.
   *
   * @param {HTMLElement} context - The DOM context.
   */
  function addSchemaMarkup(context) {
    var wrapper = context.querySelector('.faq-by-url-wrapper');
    if (!wrapper) {
      return;
    }

    var items = wrapper.querySelectorAll('.faq-by-url-item');
    if (items.length === 0) {
      return;
    }

    // Check if we already added schema markup.
    if (document.getElementById('faq-by-url-schema')) {
      return;
    }

    var faqData = {
      '@context': 'https://schema.org',
      '@type': 'FAQPage',
      'mainEntity': []
    };

    items.forEach(function (item) {
      var questionText = item.querySelector('.faq-by-url-question__text');
      var answerInner = item.querySelector('.faq-by-url-answer__inner');

      if (questionText && answerInner) {
        faqData.mainEntity.push({
          '@type': 'Question',
          'name': questionText.textContent.trim(),
          'acceptedAnswer': {
            '@type': 'Answer',
            'text': answerInner.textContent.trim()
          }
        });
      }
    });

    if (faqData.mainEntity.length > 0) {
      var script = document.createElement('script');
      script.type = 'application/ld+json';
      script.id = 'faq-by-url-schema';
      script.textContent = JSON.stringify(faqData);
      document.head.appendChild(script);
    }
  }

  /**
   * Attach behavior via Drupal's behavior system.
   */
  Drupal.behaviors.faqByUrlBlock = {
    attach: function (context, settings) {
      // Use once() for Drupal 9.5+ / 10+ compatibility.
      var wrappers = context.querySelectorAll('.faq-by-url-wrapper');
      if (wrappers.length === 0) {
        return;
      }

      wrappers.forEach(function (wrapper) {
        if (wrapper.dataset.faqAttached) {
          return;
        }
        wrapper.dataset.faqAttached = 'true';
        initFaqAccordion(wrapper);
      });
    }
  };

})(Drupal, drupalSettings);
