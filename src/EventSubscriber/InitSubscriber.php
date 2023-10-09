<?php

namespace Drupal\minify_assets\EventSubscriber;

use Drupal\minify_assets\Asset\DeferCss;
use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Perform initialization tasks for advagg_mod.
 */
class InitSubscriber implements EventSubscriberInterface {

  /**
   * A config object for the advagg_mod configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * A config object for the advagg configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $advaggConfig;

  /**
   * The CSS translator service.
   *
   * @var \Drupal\advagg_mod\Asset\TranslateCss
   */
  protected $translator;

  /**
   * The CSS defer service.
   *
   * @var \Drupal\advagg_mod\Asset\DeferCss
   */
  protected $cssDeferer;

  /**
   * The Console.log remover.
   *
   * @var \Drupal\advagg_mod\Asset\RemoveConsoleLog
   */
  protected $consoleLogRemover;

  /**
   * Constructs the Subscriber object.
   *
   * @param \Drupal\advagg_mod\Asset\DeferCss $deferer
   *   The CSS deferer.
   */
  public function __construct(DeferCss $deferer) {
    $this->cssDeferer = $deferer;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onEvent', 0],
      KernelEvents::RESPONSE => [
        ['deferCss', 0],
      ],
    ];
  }

  /**
   * Synchronize global_counter variable between sites.
   *
   * Only if using unified_multisite_dir.
   */
  public function onEvent() {
  }

  /**
   * Apply CSS defer actions.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $response
   *   The response event object.
   */
  public function deferCss(ResponseEvent $response) {

    $response = $response->getResponse();

    //only process if status code is 200 / so no defer processing for 403 & 404 or 503
    if($response->getStatusCode() != 200) return;
    
    // Only process Html Responses.
    if (!$response instanceof HtmlResponse) return;

    if(method_exists($response, 'getRequestType') ){
      // Only process the master request. This is important when using BigPipe.
      if ($response->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) return;
      //only process if status code is 200 / so no defer processing for 403 & 404
      if($response->getStatusCode() != 200) return;
    }

    $content = $this->cssDeferer->defer($response->getContent());

    $response->setContent($content);

  }

}
