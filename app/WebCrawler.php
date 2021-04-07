<?php


namespace App;

use DOMDocument;
use App\WebCrawlerResult;


class WebCrawler
{
  private $url;
  private $depth;
  private $host;
  private $useHttpAuth = false;
  private $seen = array();
  private $_filter = array();
  private $uniqueImages;

  private $unique_internal_links;
  private $unique_external_links;

  private $counter;


  const BASE_URL = 'agencyanalytics.com';

  private $result;

  public function __construct($url, $depth = 5)
  {
    $this->url = $url;
    $this->depth = $depth;
    $parse = parse_url($url);
    $this->host = $parse['host'];
    $this->unique_internal_links = [];
    $this->unique_external_links = [];
    $this->uniqueImages = [];
    $this->result = [];
    $this->counter = 0;
  }

  private function isExternalLink (string $link): bool {
    return (strpos($link, self::BASE_URL) === false && strpos($link, 'http') !== false) ;
  }

  private function processImages($content) {
    $dom = new DOMDocument('1.0');
    @$dom->loadHTML($content);
    $imagesOnPage = $dom->getElementsByTagName('img');
    $srcCounter = 0;

    $images = [];
    $uniqueImages = [];
    foreach ($imagesOnPage as $image) {
      $images[] = $image->getAttribute('src');
      $srcCounter++;
    }
    $imageSummary = array_count_values($images);
    foreach ($imageSummary as $image=>$count) {
      if ($count === 1) {
        $uniqueImages[] = $image;
      }
    }
    return $uniqueImages;
  }

  private function countWordsInPage($url) {
    $pageText = @file_get_contents($url);
    $search = array('@<script[^>]*?>.*?</script>@si',
//      '@<head>.*?</head>@siU',            // Lose the head section
      '@<style[^>]*?>.*?</style>@siU',
      '@<![\s\S]*?--[ \t\n\r]*>@'
    );

    $contents = preg_replace($search, '', $pageText);
    return str_word_count(strip_tags($contents));
  }

  private function getTitle($content) {
    $dom = new DOMDocument('1.0');
    @$dom->loadHTML($content);
    $anchors = $dom->getElementsByTagName('title');
    return $anchors->item(0)->nodeValue;
  }

  private function processLink($content, $url, $depth, $httpcode, $time)
  {
    $dom = new DOMDocument('1.0');
    @$dom->loadHTML($content);
    $anchors = $dom->getElementsByTagName('a');
    $links = [];
    $uniqueImages = $this->processImages($content);
    $wordsInPage = $this->countWordsInPage($url);
    $title = $this->getTitle($content);
    foreach ($anchors as $a) {
      $links[] = $a->getAttribute('href');
    }
    $linkSummary = array_count_values($links);
    $externalLinks = [];
    $internalLinks = [];
    foreach ($linkSummary as $link => $count) {
      if ($count === 1) {
        if ($this->isExternalLink($link)) {
          $externalLinks[] = $link;
        }
        else {
          $internalLinks[] = $link;
        }
      }
    }

    $this->result[] = new WebCrawlerResult(
      $url,
      $uniqueImages,
      $externalLinks,
      $internalLinks,
      $time,
      $httpcode,
      $title,
      $wordsInPage
    );


    foreach ($anchors as $element) {
      $href = $element->getAttribute('href');
      if (0 !== strpos($href, 'http')) {
        $path = '/' . ltrim($href, '/');
        if (extension_loaded('http')) {
          $href = http_build_url($url, array('path' => $path));
        } else {
          $parts = parse_url($url);
          $href = $parts['scheme'] . '://';
          if (isset($parts['user']) && isset($parts['pass'])) {
            $href .= $parts['user'] . ':' . $parts['pass'] . '@';
          }
          $href .= $parts['host'];
          if (isset($parts['port'])) {
            $href .= ':' . $parts['port'];
          }
          $href .= $path;
        }
      }
      $this->crawl_page($href, $depth - 1);
    }
  }

  private function getContent($url)
  {
    $handle = curl_init($url);
    if ($this->useHttpAuth) {
      curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
      curl_setopt($handle, CURLOPT_USERPWD, $this->_user . ":" . $this->_pass);
    }
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($handle);
    $time = curl_getinfo($handle, CURLINFO_TOTAL_TIME);
    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);

    return array($response, $httpCode, $time);
  }

  private function isValid($url, $depth)
  {
    if (strpos($url, $this->host) === false
      || $depth === 0
      || isset($this->seen[$url])
    ) {
      return false;
    }
    foreach ($this->_filter as $excludePath) {
      if (strpos($url, $excludePath) !== false) {
        return false;
      }
    }
    return true;
  }

  public function crawl_page($url, $depth)
  {
    if (!$this->isValid($url, $depth)) {
      return;
    }
    $this->seen[$url] = true;
    list($content, $httpcode, $time) = $this->getContent($url);
    $this->processLink($content, $url, $depth, $httpcode, $time);
  }

  private function countUniqueItems($item) {
    $itemList = [];
    $itemRef = null;

    foreach ($this->result as $result) {

      switch ($item) {
        case 'internalLink':
          $itemRef = $result->getInternalLinks();
          break;
        case 'externalLink':
          $itemRef = $result->getExternalLinks();
          break;
        case 'image':
          $itemRef = $result->getImages();
          break;
      }

      if (!is_null($itemRef)) {
        array_push($itemList, array_values($itemRef));
      }
    }

    $flat = collect($itemList)->flatten()->all();
    $countItems = array_count_values($flat);

    $countItems = array_filter($countItems, function($value) {
      return $value === 1;
    });

    return count($countItems);
  }

  private function calculateItemAverage($item) {
    $total = 0;
    foreach ($this->result as $result) {
      switch ($item) {
        case 'pageLoad':
          $total += $result->getLoadTime();
          break;
        case 'wordCount':
          $total += $result->getWordCounts();
          break;
        case 'titleLength':
          $total += $result->getTitleLength();
          break;
      }
    }
    return $total/count($this->result);
  }

  public function getScrawledPageWithStatusCode() {
    $allResult = [];
    $counter = 0;
    foreach ($this->result as $result) {
      $data['id'] = ++$counter;
      $data['url'] = $result->getURL();
      $data['status_code'] = $result->getStatusCode();
      $data['avg_load_time'] = $result->getLoadTime();
      $allResult[] = $data;
    }

    return $allResult;
  }

  public function run()
  {
    $this->crawl_page($this->url, $this->depth);
    $data = [];
    $data['total_pages'] = count($this->result);
    $data['unique_internal_links'] = $this->countUniqueItems('internalLink');
    $data['unique_external_links'] = $this->countUniqueItems('externalLink');
    $data['unique_images'] = $this->countUniqueItems('image');
    $data['avg_page_load'] = $this->calculateItemAverage('pageLoad');
    $data['avg_word_count'] = $this->calculateItemAverage('wordCount');
    $data['avg_title_length'] = $this->calculateItemAverage('titleLength');

    return $data;
  }

}