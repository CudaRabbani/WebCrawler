<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Web Crawler</title>

        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        <script src="{{ asset('js/app.js') }}"></script>

        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Nunito', sans-serif;
                font-weight: 200;
                height: 100vh;
                margin: 25px;
            }

            .content {
                text-align: center;
            }

        </style>
    </head>
    <body>
    <div class="content">

        <div id="root">
            <h4>Overall Summary</h4>

            <table class="table table-striped">
                <thead>
                <tr>
                    <th scope="col">Number of pages Crawled</th>
                    <th scope="col">Unique Images</th>
                    <th scope="col">Unique Internal Links</th>
                    <th scope="col">Unique External Links</th>
                    <th scope="col">Average Page Load</th>
                    <th scope="col">Average Word Count</th>
                    <th scope="col">Average Title Length</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>{!! $summaryData['total_pages'] !!}</td>
                    <td>{!! $summaryData['unique_images'] !!}</td>
                    <td>{!! $summaryData['unique_internal_links'] !!}</td>
                    <td>{!! $summaryData['unique_external_links'] !!}</td>
                    <td>{!! $summaryData['avg_page_load'] !!}</td>
                    <td>{!! $summaryData['avg_word_count'] !!}</td>
                    <td>{!! $summaryData['avg_title_length'] !!}</td>
                </tr>
                </tbody>
            </table>
            <hr>
            <h4>Pages Scrawled</h4>

            <table class="table table-striped">
                <thead>
                <tr>
                    <th scope="col">Page No.</th>
                    <th scope="col" class="text-left">Page URL</th>
                    <th scope="col">Status Code</th>
                    <th scope="col">Load Time</th>
                </tr>
                </thead>
                <tbody>
                @foreach($crawlingData as $data)
                    <tr>
                        <td>{{$data['id']}}</td>
                        <td class="text-left">{{$data['url']}}</td>
                        <td>{{$data['status_code']}}</td>
                        <td>{{$data['avg_load_time']}}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    </body>
</html>
