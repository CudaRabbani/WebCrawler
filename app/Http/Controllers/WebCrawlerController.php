<?php

namespace App\Http\Controllers;

use App\WebCrawler;

class WebCrawlerController extends Controller
{
    public function get() {
      $url = 'https://agencyanalytics.com/';
      $webCrawler = new WebCrawler($url, 5);
      $crawlingData = $webCrawler->run();
      return view('crawling',
        [
          'summaryData'=>$crawlingData,
          'crawlingData'=>$webCrawler->getScrawledPageWithStatusCode()
        ]
      );
    }
}
