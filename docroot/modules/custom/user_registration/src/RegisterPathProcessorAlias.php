<?php

namespace Drupal\user_registration;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RegisterPathProcessorAlias.
 */
class RegisterPathProcessorAlias implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * Processes the inbound path.
   *
   * @param string $path
   *   The path to process, with a leading slash.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   *
   * @return string
   *   The processed path.
   */
  public function processInbound($path, Request $request) {
    if (preg_match('~/user/(register|login)~is', $path, $match) === 1) {
      return "/user/" . $this->getPath($match[1]);
    }

    if (preg_match('~/user/(\d+/edit$)~is', $path, $match) === 1) {
      return "/user/" . $this->getEditPath($match[1]);
    }

    return $path;
  }

  /**
   * Processes the outbound path.
   *
   * @param string $path
   *   The path to process, with a leading slash.
   * @param array $options
   *   (optional) An associative array of additional options, with the following
   *   elements:
   *   - 'query': An array of query key/value-pairs (without any URL-encoding)
   *     to append to the URL.
   *   - 'fragment': A fragment identifier (named anchor) to append to the URL.
   *     Do not include the leading '#' character.
   *   - 'absolute': Defaults to FALSE. Whether to force the output to be an
   *     absolute link (beginning with http:). Useful for links that will be
   *     displayed outside the site, such as in an RSS feed.
   *   - 'language': An optional language object used to look up the alias
   *     for the URL. If $options['language'] is omitted, it defaults to the
   *     current language for the language type LanguageInterface::TYPE_URL.
   *   - 'https': Whether this URL should point to a secure location. If not
   *     defined, the current scheme is used, so the user stays on HTTP or HTTPS
   *     respectively. TRUE enforces HTTPS and FALSE enforces HTTP.
   *   - 'base_url': Only used internally by a path processor, for example, to
   *     modify the base URL when a language dependent URL requires so.
   *   - 'prefix': Only used internally, to modify the path when a language
   *     dependent URL requires so.
   *   - 'route': The route object for the given path. It will be set by
   *     \Drupal\Core\Routing\UrlGenerator::generateFromRoute().
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   (optional) Object to collect path processors' bubbleable metadata.
   *
   * @return string
   *   The processed path.
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if (preg_match('~/user/(register|login)~is', $path, $match) === 1) {
      return "/user/" . $this->getPath($match[1]);
    }

    if (preg_match('~/user/(\d+/edit$)~is', $path, $match) === 1) {
      return "/user/" . $this->getEditPath($match[1]);
    }

    return $path;
  }

  /**
   * @param string $match_path
   *
   * @return string
   */
  private function getPath(string $match_path) {
    return [
      'register' => 'sign-up',
      'login' => 'sign-in',
    ][$match_path];
  }

  /**
   * @param string $match_path
   *
   * @return string
   */
  private function getEditPath(string $match_path) {
    $user = \Drupal::currentUser();
    $user_path = $match_path;

    if (!$user->hasPermission('administer users') && $match_path == $user->id() . '/edit') {
      $user_path = 'my-settings';
    }

    return [
      $match_path => $user_path,
    ][$match_path];
  }

}
