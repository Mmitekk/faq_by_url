<?php

namespace Drupal\faq_by_url\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'FAQ by URL' block.
 *
 * @Block(
 *   id = "faq_by_url_block",
 *   admin_label = @Translation("FAQ by URL — Вопросы-ответы"),
 *   category = @Translation("Custom")
 * )
 */
class FaqBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current path stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The path matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs a FaqBlock object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    CurrentPathStack $current_path,
    AliasManagerInterface $alias_manager,
    RequestStack $request_stack,
    PathMatcherInterface $path_matcher
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->aliasManager = $alias_manager;
    $this->requestStack = $request_stack;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('path.current'),
      $container->get('path_alias.manager'),
      $container->get('request_stack'),
      $container->get('path.matcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('faq_by_url.settings');
    $current_urls = $this->getCurrentUrls();

    if (empty($current_urls)) {
      return $this->buildEmpty($config);
    }

    $faq_items = $this->loadFaqItemsForUrls($current_urls);

    if (empty($faq_items)) {
      return $this->buildEmpty($config);
    }

    $items = [];
    $counter = 0;
    foreach ($faq_items as $faq_item) {
      $counter++;
      $answer_render = [
        '#type' => 'processed_text',
        '#text' => $faq_item->getAnswer(),
        '#format' => $faq_item->getAnswerFormat() ?: 'basic_html',
        '#langcode' => $faq_item->language()->getId(),
      ];

      $items[] = [
        'id' => $faq_item->id(),
        'question' => $faq_item->getQuestion(),
        'answer' => \Drupal::service('renderer')->renderPlain($answer_render),
        'counter' => $config->get('show_counter') ? $counter : NULL,
        'is_first' => $counter === 1 && $config->get('open_first'),
      ];
    }

    $build = [
      '#theme' => 'faq_by_url_block',
      '#title' => $config->get('block_title') ?: '',
      '#items' => $items,
      '#empty_message' => '',
      '#css_variables' => $this->buildCssVariables($config),
      '#attached' => [
        'library' => [
          'faq_by_url/faq_block',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.path', 'url.query_args'],
        'tags' => ['faq_item_list'],
        'max-age' => 3600,
      ],
    ];

    return $build;
  }

  /**
   * Builds the empty state render array.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The module configuration.
   *
   * @return array
   *   A render array.
   */
  protected function buildEmpty($config) {
    $empty_message = $config->get('empty_message');
    if (empty($empty_message)) {
      return [
        '#cache' => [
          'contexts' => ['url.path'],
          'tags' => ['faq_item_list'],
          'max-age' => 3600,
        ],
      ];
    }

    return [
      '#theme' => 'faq_by_url_block',
      '#title' => $config->get('block_title') ?: '',
      '#items' => [],
      '#empty_message' => $empty_message,
      '#css_variables' => $this->buildCssVariables($config),
      '#attached' => [
        'library' => [
          'faq_by_url/faq_block',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => ['faq_item_list'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Gets all URL variants for the current page.
   *
   * Returns both the system path and the alias (if different),
   * the request URI, and the special <front> token if this is
   * the front page.
   *
   * @return string[]
   *   Array of URL paths to match against.
   */
  protected function getCurrentUrls() {
    $urls = [];

    // 1. Get current path from the path stack (system path).
    $system_path = $this->currentPath->getPath();
    if (!str_starts_with($system_path, '/')) {
      $system_path = '/' . $system_path;
    }
    $urls[] = $system_path;

    // 2. Get the alias for the system path.
    try {
      $alias = $this->aliasManager->getAliasByPath($system_path);
      if ($alias !== $system_path) {
        $urls[] = $alias;
      }
    } catch (\InvalidArgumentException $e) {
      // Path not found in alias storage, skip.
    }

    // 3. Get the request URI (what user sees in browser).
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      $request_uri = $request->getRequestUri();
      // Strip query string.
      $request_path = '/' . ltrim(strtok($request_uri, '?'), '/');
      if (!in_array($request_path, $urls)) {
        $urls[] = $request_path;
      }

      // Also get system path from the request URI alias.
      try {
        $system_from_alias = $this->aliasManager->getPathByAlias($request_path);
        if (!str_starts_with($system_from_alias, '/')) {
          $system_from_alias = '/' . $system_from_alias;
        }
        if (!in_array($system_from_alias, $urls)) {
          $urls[] = $system_from_alias;
        }
      } catch (\InvalidArgumentException $e) {
        // Alias not found, skip.
      }
    }

    // 4. If this is the front page, add the <front> token and the
    //    configured front page path from Drupal settings.
    //    This allows FAQ items to be assigned to the front page
    //    regardless of whether it's a node, view, or custom path.
    if ($this->pathMatcher->isFrontPage()) {
      $urls[] = '<front>';

      // Also add the front page path as configured in
      // admin/config/system/site-information.
      $front_path = \Drupal::config('system.site')->get('page.front');
      if (!empty($front_path)) {
        if (!str_starts_with($front_path, '/')) {
          $front_path = '/' . $front_path;
        }
        if (!in_array($front_path, $urls)) {
          $urls[] = $front_path;
        }

        // And the alias for the front page system path.
        try {
          $front_alias = $this->aliasManager->getAliasByPath($front_path);
          if ($front_alias !== $front_path && !in_array($front_alias, $urls)) {
            $urls[] = $front_alias;
          }
        } catch (\InvalidArgumentException $e) {
          // Skip.
        }
      }
    }

    // Remove trailing slashes for consistency (except root).
    $urls = array_map(function ($url) {
      if ($url !== '/' && $url !== '<front>' && str_ends_with($url, '/')) {
        return rtrim($url, '/');
      }
      return $url;
    }, $urls);

    return array_unique(array_filter($urls));
  }

  /**
   * Resolves the <front> token to the actual front page path.
   *
   * @return string
   *   The front page path with leading slash, e.g. '/node' or '/node/1'.
   */
  protected function resolveFrontPath() {
    $front_path = \Drupal::config('system.site')->get('page.front');
    if (!empty($front_path)) {
      if (!str_starts_with($front_path, '/')) {
        $front_path = '/' . $front_path;
      }
      return $front_path;
    }
    return '/node';
  }

  /**
   * Loads FAQ items that are assigned to any of the given URLs.
   *
   * @param string[] $urls
   *   Array of URL paths to match (including <front> if front page).
   *
   * @return \Drupal\faq_by_url\Entity\FaqItemInterface[]
   *   Array of matching FAQ items, sorted by weight and ID.
   */
  protected function loadFaqItemsForUrls(array $urls) {
    if (empty($urls)) {
      return [];
    }

    // First, load all published FAQ items.
    $query = $this->entityTypeManager->getStorage('faq_item')->getQuery();
    $query->condition('status', 1)
      ->sort('weight', 'ASC')
      ->sort('id', 'ASC')
      ->accessCheck(TRUE);

    $ids = $query->execute();

    if (empty($ids)) {
      return [];
    }

    $faq_items = $this->entityTypeManager->getStorage('faq_item')->loadMultiple($ids);
    $matching_items = [];

    // Resolve <front> to real paths for alias lookups.
    $front_path = $this->resolveFrontPath();

    foreach ($faq_items as $faq_item) {
      $item_urls = $faq_item->getUrls();
      if (empty($item_urls)) {
        continue;
      }

      // Normalize item URLs for comparison.
      $normalized_item_urls = array_map(function ($url) {
        $url = trim($url);
        // Don't touch <front> — it's a special token.
        if ($url === '<front>') {
          return $url;
        }
        if (!empty($url) && !str_starts_with($url, '/')) {
          $url = '/' . $url;
        }
        if ($url !== '/' && str_ends_with($url, '/')) {
          $url = rtrim($url, '/');
        }
        return $url;
      }, $item_urls);

      // Check for intersection.
      foreach ($normalized_item_urls as $item_url) {

        // --- Special token: <front> ---
        // If the FAQ item URL is <front>, resolve it to the real front
        // page path and check if the current page matches.
        if ($item_url === '<front>') {
          if (in_array('<front>', $urls) || in_array($front_path, $urls)) {
            $matching_items[$faq_item->id()] = $faq_item;
            break;
          }
          // Also check alias of the front page path.
          try {
            $front_alias = $this->aliasManager->getAliasByPath($front_path);
            if (in_array($front_alias, $urls)) {
              $matching_items[$faq_item->id()] = $faq_item;
              break;
            }
          } catch (\InvalidArgumentException $e) {
            // Skip.
          }
          continue;
        }

        // Direct match.
        if (in_array($item_url, $urls)) {
          $matching_items[$faq_item->id()] = $faq_item;
          break;
        }

        // Also check if the item URL is an alias and we have the system path.
        try {
          $system_path_from_alias = $this->aliasManager->getPathByAlias($item_url);
          if (!str_starts_with($system_path_from_alias, '/')) {
            $system_path_from_alias = '/' . $system_path_from_alias;
          }
          if (in_array($system_path_from_alias, $urls)) {
            $matching_items[$faq_item->id()] = $faq_item;
            break;
          }
        } catch (\InvalidArgumentException $e) {
          // Not a valid alias, skip.
        }

        // Also check if the item URL is a system path and we have the alias.
        try {
          $alias_from_system = $this->aliasManager->getAliasByPath($item_url);
          if ($alias_from_system !== $item_url && in_array($alias_from_system, $urls)) {
            $matching_items[$faq_item->id()] = $faq_item;
            break;
          }
        } catch (\InvalidArgumentException $e) {
          // Not a valid system path, skip.
        }

        // Wildcard matching: if item URL ends with /*, match any subpath.
        if (str_ends_with($item_url, '/*')) {
          $base_path = rtrim(substr($item_url, 0, -2), '/');
          foreach ($urls as $current_url) {
            if ($current_url === $base_path || str_starts_with($current_url, $base_path . '/')) {
              $matching_items[$faq_item->id()] = $faq_item;
              break 2;
            }
          }
        }
      }
    }

    return $matching_items;
  }

  /**
   * Builds CSS custom properties string from module configuration.
   *
   * Returns an inline style string like:
   *   --faq-color-accent: #e95b18; --faq-color-border: #e0e0e0; ...
   *
   * Only includes variables that differ from CSS defaults, to keep
   * the output minimal. However, we always output all variables so
   * that config changes take effect immediately without cache issues.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The module configuration.
   *
   * @return string
   *   Inline style string for CSS custom properties.
   */
  protected function buildCssVariables($config) {
    $vars = [
      '--faq-color-accent' => $config->get('color_accent') ?: '#e95b18',
      '--faq-color-question-text' => $config->get('color_question_text') ?: '#333333',
      '--faq-color-answer-text' => $config->get('color_answer_text') ?: '#555555',
      '--faq-color-border' => $config->get('color_border') ?: '#e0e0e0',
      '--faq-color-hover-bg' => $config->get('color_hover_bg') ?: '#e95b18',
      '--faq-color-item-hover-bg' => $config->get('color_item_hover_bg') ?: '#fafafa',
      '--faq-color-strong' => $config->get('color_strong') ?: '#333333',
    ];

    $parts = [];
    foreach ($vars as $name => $value) {
      $parts[] = $name . ': ' . $value;
    }

    return implode('; ', $parts);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url.path', 'url.query_args'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['faq_item_list'];
  }

}
