<?php


namespace App;

use DOMDocument;
use App\WebCrawlerResult;


class WebCrawler
{
  protected $_url;
  protected $_depth;
  protected $_host;
  protected $_useHttpAuth = false;
  protected $_seen = array();
  protected $_filter = array();
  private $uniqueImages;

  private $unique_internal_links;
  private $unique_external_links;

  private $counter;


  const BASE_URL = 'agencyanalytics.com';

  private $result;

  public function __construct($url, $depth = 5)
  {
    $this->_url = $url;
    $this->_depth = $depth;
    $parse = parse_url($url);
    $this->_host = $parse['host'];
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
    $search = array('@<script[^>]*?>.*?</script>@si',  // Strip out javascript
//      '@<head>.*?</head>@siU',            // Lose the head section
      '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
      '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA
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

  protected function processLink($content, $url, $depth, $httpcode, $time)
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

  protected function _getContent($url)
  {
    $handle = curl_init($url);
    if ($this->_useHttpAuth) {
      curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
      curl_setopt($handle, CURLOPT_USERPWD, $this->_user . ":" . $this->_pass);
    }
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);

    /* Get the HTML or whatever is linked in $url. */
    $response = curl_exec($handle);
    // response total time
    $time = curl_getinfo($handle, CURLINFO_TOTAL_TIME);
    /* Check for 404 (file not found). */
    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

    curl_close($handle);
    return array($response, $httpCode, $time);
  }

  protected function _printResult($url, $depth, $httpcode, $time)
  {
    ob_end_flush();
    $currentDepth = $this->_depth - $depth;
    $count = count($this->_seen);
    $this->counter++;
    //echo "N::$count,CODE::$httpcode,TIME::$time,DEPTH::$currentDepth URL::$url <br>";
    ob_start();
    flush();
  }

  protected function isValid($url, $depth)
  {
    if (strpos($url, $this->_host) === false
      || $depth === 0
      || isset($this->_seen[$url])
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
    $this->_seen[$url] = true;
    list($content, $httpcode, $time) = $this->_getContent($url);
    $this->_printResult($url, $depth, $httpcode, $time);
    $this->processLink($content, $url, $depth, $httpcode, $time);
  }

  private function countUniqueInternalLinks() {
    $uniqueInternalLinks = [];
    foreach ($this->result as $result) {
      $internalLinks = $result->getInternalLinks();
      if (!is_null($internalLinks)) {
        array_push($uniqueInternalLinks, array_values($internalLinks));
      }

    }
    $flat = collect($uniqueInternalLinks)->flatten()->all();
    $count = array_count_values($flat);
    $internalLinkCounter = 0;

    foreach ($count as $key => $value) {
      if ($value === 1) {
        $internalLinkCounter++;
      }
    }
    return $internalLinkCounter;
  }

  private function countUniqueImages() {
    $uniqueImages = [];
    foreach ($this->result as $result) {
      $images = $result->getImages();
      if (!is_null($images)) {
        array_push($uniqueImages, array_values($images));
      }
    }

    $flat = collect($uniqueImages)->flatten()->all();
    $count = array_count_values($flat);
    $imageCounter = 0;

    foreach ($count as $key => $value) {
      if ($value === 1) {
        $imageCounter++;
      }
    }
    return $imageCounter;
  }

  private function countUniqueExternalLinks () {
    $uniqueExternalLinks = [];
    foreach ($this->result as $result) {
      $t = $result->getExternalLinks();
      if (!is_null($t)) {
        array_push($uniqueExternalLinks, array_values($t));
      }

    }
    $flat = collect($uniqueExternalLinks)->flatten()->all();
    $count = array_count_values($flat);
    $externalLinkCounter = 0;
    foreach ($count as $key => $value) {
      if ($value === 1) {
        $externalLinkCounter++;
      }
    }
    return $externalLinkCounter;
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
    $counter = 0;
    foreach ($countItems as $key => $value) {
      if ($value === 1) {
        $counter++;
      }
    }

    return $counter;
  }

  private function countAvgPageLoad() {
    $totalTime = 0;
    foreach ($this->result as $result) {
      $totalTime += $result->getLoadTime();
    }

    return $totalTime/count($this->result);
  }

  private function countAvgWord() {
    $totalWord = 0;
    foreach ($this->result as $result) {
      $totalWord += $result->getWordCounts();
    }

    return $totalWord/count($this->result);
  }

  private function countAvgTitleLength() {
    $totalTitle = 0;
    foreach ($this->result as $result) {
      $totalTitle += $result->getTitleLength();
    }

    return $totalTitle/count($this->result);
  }

  public function run()
  {
    $this->crawl_page($this->_url, $this->_depth);
    $data = [];
    $data['total_pages'] = count($this->result);
/*    $data['unique_internal_links'] = $this->countUniqueInternalLinks();
    $data['unique_external_links'] = $this->countUniqueExternalLinks();
    $data['unique_images'] = $this->countUniqueImages();*/
    $data['unique_internal_links'] = $this->countUniqueItems('internalLink');
    $data['unique_external_links'] = $this->countUniqueItems('externalLink');
    $data['unique_images'] = $this->countUniqueItems('image');
    $data['avg_page_load'] = $this->countAvgPageLoad();
    $data['avg_word_count'] = $this->countAvgWord();
    $data['avg_title_length'] = $this->countAvgTitleLength();
    echo "<br>The End<br>";
    print_r($data);

    return $this->result;
  }

}