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

  public function setUniqueImage($image) {
    $this->uniqueImages = $image;
  }

  public function getWordCounts() {
    return $this->wordCount;
  }

  public function setStatusCode($code) {
    $this->statusCode = $code;
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

  public function __toString(): string {
    $countExternalLinks = is_array($this->uniqueExternalLinks) ? count($this->uniqueExternalLinks) : 0;
    $countInternalLinks = is_array($this->uniqueInternalLinks) ? count($this->uniqueInternalLinks) : 0;
    $countImages = is_array($this->uniqueImages) ? count($this->uniqueImages) : 0;
    return "Page: $this->url<br>"
      ."Unique External Links: $countExternalLinks<br>"
      ."Unique Internal Links: $countInternalLinks<br>"
      ."Unique Images: $countImages<br>"
      ."Total Word Count: $this->wordCount<br>"
      ."Load Time: $this->loadTime<br>"
      ."HTTP Status: $this->statusCode<br>"
      ."Title: $this->title<br>"
      ."--------------------------------------------------<br>";
  }

}