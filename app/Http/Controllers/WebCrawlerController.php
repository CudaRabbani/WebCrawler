<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\WebCrawler;
use App\WebCrawlerResult;

class WebCrawlerController extends Controller
{
    public function get() {
      $name = 'Reza';
      $url = 'https://agencyanalytics.com/';
      $webCrawler = new WebCrawler($url, 5);
      $result = $webCrawler->run();
      //var_dump($result);
      //return view('welcome', ['name' => $name, 'data' => json_encode($result)]);
      return view('welcome', compact('name', 'result'));
    }
}
