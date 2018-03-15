<?php

namespace Drupal\single_language_url_prefix;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language via URL prefix when there is single language.
 *
 * @see \Drupal\Core\PathProcessor\PathProcessorAlias
 * @see \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl
 */
class SingleLanguageNegotiationUrl implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * An alias manager for looking up the system path.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs a SingleLanguageNegotiationUrl object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   An alias manager for looking up the system path.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory.
   */
  public function __construct(LanguageManagerInterface $language_manager,
                              ConfigFactoryInterface $config) {
    $this->languageManager = $language_manager;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $languages = $this->languageManager->getLanguages();

    // We don't do anything if more then one language enabled.
    // That works by default.
    if (count ($languages) > 1) {
      return $path;
    }

    $config = $this->config->get('language.negotiation')->get('url');

    if ($config['source'] == LanguageNegotiationUrl::CONFIG_PATH_PREFIX) {
      $parts = explode('/', trim($path, '/'));
      $prefix = array_shift($parts);

      $language = reset($languages);
      if (isset($config['prefixes'][$language->getId()]) && $config['prefixes'][$language->getId()] == $prefix) {
        // Rebuild $path with the language removed.
        $path = '/' . implode('/', $parts);
      }
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [],
                                  Request $request = NULL,
                                  BubbleableMetadata $bubbleable_metadata = NULL) {
    $languages = $this->languageManager->getLanguages();

    // We don't do anything if more then one language enabled.
    // That works by default.
    if (count ($languages) > 1) {
      return $path;
    }

    $config = $this->config->get('language.negotiation')->get('url');

    if ($config['source'] == LanguageNegotiationUrl::CONFIG_PATH_PREFIX) {
      $language = reset($languages);

      if (isset($config['prefixes'][$language->getId()])) {
        $options['prefix'] = $config['prefixes'][$language->getId()] . '/';
      }
    }

    return $path;
  }

}
