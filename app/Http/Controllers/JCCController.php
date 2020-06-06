<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;

class JCCController extends Controller
{
    const MAX_PROFILES = 4;

    public function grabData()
    {
        $client = new Client(HttpClient::create(['timeout' => 60]));
        $alphas = range('a', 'b');
        $inmates = [];

        foreach ($alphas as $alpha) {
            $params = [
                'edtLastName' => $alpha,
                'btnSearch' => 'Search',
                '__VIEWSTATE' => '/wEPDwUIODU5MjU2ODIPZBYCAgMPZBYIAgkPEA8WBh4NRGF0YVRleHRGaWVsZAULQ29kZURpc3BsYXkeDkRhdGFWYWx1ZUZpZWxkBQdDb2RlRGVmHgtfIURhdGFCb3VuZGdkEBUDAApGRU1BTEUgICAgCk1BTEUgICAgICAVAwEwAUYBTRQrAwNnZ2dkZAILDxAPFgYfAAULQ29kZURpc3BsYXkfAQUHQ29kZURlZh8CZ2QQFQwAHkFMQVNLQSBOQVRJVkUgICAgICAgICAgICAgICAgIB5BTUVSSUNBTiBJTkRJQU4gICAgICAgICAgICAgICAeQVNJQU4gICAgICAgICAgICAgICAgICAgICAgICAgHkJMQUNLICAgICAgICAgICAgICAgICAgICAgICAgIB5ISVNQQU5JQyAgICAgICAgICAgICAgICAgICAgICAeTkFUSVZFIEhBV0FJSUFOICAgICAgICAgICAgICAgHk5PTi1XSElURSAgICAgICAgICAgICAgICAgICAgIB5PVEhFUiAgICAgICAgICAgICAgICAgICAgICAgICAeUEFDSUZJQyBJU0xBTkRFUiAgICAgICAgICAgICAgHlVOS05PV04gICAgICAgICAgICAgICAgICAgICAgIB5XSElURSAgICAgICAgICAgICAgICAgICAgICAgICAVDAEwAUEBSQFDAUIBSAFTAU4BTwFQASMBVxQrAwxnZ2dnZ2dnZ2dnZ2dkZAIRDw9kDxAWBGYCAQICAgMWBBYCHg5QYXJhbWV0ZXJWYWx1ZWQWAh8DZBYCHwNkFgIfA2QWBAIDAgMCAwIDZGQCEw88KwANAQAPFgQfAmceC18hSXRlbUNvdW50ZmRkGAEFCUdyaWRWaWV3MQ88KwAKAQhmZOlJFWcoCMHzFDvU+gBwz43RB8vf',
                '__VIEWSTATEGENERATOR' => '208A8EE7',
                '__EVENTVALIDATION' => '/wEWEwLv/+HqCgKN5LOwCwKp17uwCALXk6S2DwKdk6S2DwKkk6S2DwLSyKprAp3IqmsCpciqawKfyKprApzIqmsCqsiqawKvyKprAqDIqmsCo8iqawKyyKprAr/JqmsCq8iqawKln/PuCnDsXOhpuslgnf16NZi5/YqfTbIi'
            ];

            $crawler = $client->request('POST', 'http://jccweb.jacksongov.org/InmateSearch/Default.aspx', $params);

            $inmate = [];
            $inmateDataKeys = ['ID', 'Last Name', 'First Name', 'Middle', 'DOB', 'Race', 'Sex'];

            $inmatesArr = $crawler->filter('#GridView1 > tr td')->each(function ($node) {
                return $node->text();
            });

            $inmatesArrFiltered = array_slice($inmatesArr, 0, 70);

            for ($i = 0; $i < count($inmatesArrFiltered); $i = $i + 7) {
                $length = 7;
                $oneInmate = array_slice($inmatesArrFiltered, $i, $length);
                for ($j = 0; $j < count($oneInmate); $j++) {
                    $inmate[$inmateDataKeys[$j]] = $oneInmate[$j];
                }

                if(count($inmates) >= self::MAX_PROFILES) {break 2;}

                $inmates[] = $inmate;
            }
        }

        return $inmates;
    }
}
