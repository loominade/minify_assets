<?php

namespace Drupal\minify_assets\Asset;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Extension\ExtensionList;

/**
 * Modify stylesheet links to defer them. May lead to Flash of unstyled content.
 */
class DeferCss {

  /**
   * The defer method to use from advagg_mod configuration.
   *
   * @var int
   */
  protected $deferMethod;

  /**
   * The global counter to use for calculating paths.
   *
   * @var int
   */
  protected $counter;

  /**
   * Whether or not to alter external stylesheets.
   *
   * @var bool
   */
  protected $external;

  /**
   * DeferCss constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
  }

  public function accessProtected($obj,$name) {
    $array = (array)$obj;
    $prefix = chr(0).'*'.chr(0);
    return $array[$prefix.$name];
  }

  public function getDeferableCss() {
    $library_discovery = \Drupal::service('library.discovery');
    $extensions = array_merge(
      \Drupal::config('core.extension')->get('module'),
      \Drupal::config('core.extension')->get('theme')
    );

    $all_libraries = [];
    $deferable_css = [];
    foreach ($extensions as $name => $weight) {
      $libraries = $library_discovery->getLibrariesByExtension($name);
      foreach ($libraries as $library => $definition) {
        if ($definition['css']) {
          foreach ($definition['css'] as $key => $asset) {
            if (isset($asset['weight']) && $asset['weight'] === CSS_STATE) {
              $deferable_css[] = $asset['data'];
              $library_key = $name . '/' . $library;
              if (!in_array($library_key, $all_libraries)) {
                $all_libraries[] = $library_key;
              }
            }
          }
        }
      }
    }

    $resolver = \Drupal::service('asset.resolver');

    $assets = new AttachedAssets();
    $assets->setLibraries($all_libraries);

    $all_css = $resolver->getCssAssets($assets, false);

    $output = [];
    foreach ($all_css as $key => $entry) {
      if (in_array($key, $deferable_css)) {
        $path = $entry["data"];
        if (str_starts_with($path, 'public://')) {
          $url = \Drupal::service('file_url_generator')->generate($path);
          $path = $url->toString();
        }
        $output[] = $path;
      }
    }
    
    return $output;
  }

  /**
   * Replace stylesheet links with preload & noscript links.
   *
   * @param string $content
   *   The response content.
   *
   * @return string
   *   Updated content.
   */
  public function defer($content) {

    $deferables = $this->getDeferableCss();

    foreach ($deferables as $filepath) {
      $regexFilepath = str_replace(['?', '/', '.'], ['\?', '\/', '\.'], $filepath);
      $pattern = '/<link rel=["\']stylesheet["\'](.*)(href="[^"]*' . $regexFilepath . '[^"]*")(.*)\/\>/';
      $content = preg_replace_callback($pattern, [$this, 'callback'], $content);
    }

    return $content;
  }

  /**
   * Callback to replace individual stylesheet links.
   *
   * @param array $matches
   *   Array from matches from preg_replace_callback.
   *
   * @return string
   *   Updated html string.
   */
  protected function callback(array $matches) {
    $module_path = \Drupal::service('extension.list.module')->getPath('minify_assets');
    $css_loader = "{$module_path}/src/async-css.js";
    return "<link rel='preload' {$matches[1]} {$matches[2]} as='style' {$matches[3]}/>
      <script src='{$css_loader}' ></script>
      <noscript>{$matches[0]}</noscript>";
  }
}
