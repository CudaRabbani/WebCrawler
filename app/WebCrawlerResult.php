<?php


namespace App;


class WebCrawlerResult
{
  private $url;
  private $uniqueExternalLinks;
  private $uniqueInternalLinks;
  private $uniqueImages;
  private $wordCount;
  private $statusCode;
  private $title;
  private $loadTime;

  public function __construct($pageUrl, $images, $externalLinks, $internalLinks, $loadTime, $statusCode, $title, $words) {
    $this->url = $pageUrl;
    $this->uniqueExternalLinks = $externalLinks;
    $this->uniqueInternalLinks = $internalLinks;
    $this->uniqueImages = $images;
    $this->wordCount = $words;
    $this->statusCode = $statusCode;
    $this->loadTime = $loadTime;
    $this->title = $title;
  }

  public function getWordCounts() {
    return $this->wordCount;
  }

  public function getStatusCode() {
    return $this->statusCode;
  }

  public function getLoadTime() {
    return $this->loadTime;
  }

  public function getExternalLinks() {
    return $this->uniqueExternalLinks;
  }

  public function getInternalLinks() {
    return $this->uniqueInternalLinks;
  }

  public function getImages() {
    return $this->uniqueImages;
  }

  public function getTitleLength() {
    return strlen($this->title);
  }

  public function getURL() {
    return $this->url;
  }
}