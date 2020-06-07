<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;

class JCCController extends Controller
{
    const MAX_PROFILES = 250;
    const TIME_OUT = 300;

    public function grabData()
    {
        $client = new Client(HttpClient::create(['timeout' => self::TIME_OUT]));
        $alphas = range('a', 'z');
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
            $viewState = $crawler->filter('#__VIEWSTATE')->attr('value');
            $eventValidation = $crawler->filter('#__EVENTVALIDATION')->attr('value');

            $inmate = [];
            $inmateDataKeys = ['ID', 'Last Name', 'First Name', 'Middle', 'DOB', 'Race', 'Sex'];

            $inmatesArr = $crawler->filter('#GridView1 > tr td')->each(function ($node) {
                if($node->text() == 1) {
                    return 'error';
                }
                return $node->text();
            });

            $numberOfPages = $inmatesArr[count($inmatesArr) - 1];
            $inmatesArrFiltered = array_slice($inmatesArr, 0, array_search('error', $inmatesArr) - 1);

            for ($i = 0; $i < count($inmatesArrFiltered); $i = $i + 7) {
                $length = 7;
                $oneInmate = array_slice($inmatesArrFiltered, $i, $length);
                for ($j = 0; $j < count($oneInmate); $j++) {
                    $inmate[$inmateDataKeys[$j]] = $oneInmate[$j];
                }

                if (count($inmates) >= self::MAX_PROFILES) {
                    break 2;
                }

                $inmates[] = $inmate;
            }

            for ($p = 2; $p <= $numberOfPages; $p++) {
                $pageParams = [
                    '__EVENTTARGET' => 'GridView1',
                    '__EVENTARGUMENT' => "Page$${p}",
                    '__VIEWSTATE' => $viewState,
                    '__VIEWSTATEGENERATOR' => '208A8EE7',
                    '__EVENTVALIDATION' => $eventValidation,
                    'edtLastName' => $alpha,
                ];

                $crawler2 = $client->request('POST', 'http://jccweb.jacksongov.org/InmateSearch/Default.aspx', $pageParams);

                $inmatesArr = $crawler2->filter('#GridView1 > tr td')->each(function ($node) {
                    if($node->text() == 1) {
                        return 'error';
                    }
                    return $node->text();
                });

                $inmatesArrFiltered2 = array_slice($inmatesArr, 0, array_search('error', $inmatesArr) - 1);

                for ($i = 0; $i < count($inmatesArrFiltered2); $i = $i + 7) {
                    $length = 7;
                    $oneInmate1 = array_slice($inmatesArrFiltered2, $i, $length);
                    for ($j = 0; $j < count($oneInmate1); $j++) {
                        $inmate[$inmateDataKeys[$j]] = $oneInmate1[$j];
                    }

                    if (count($inmates) >= self::MAX_PROFILES) {
                        break 2;
                    }

                    $inmates[] = $inmate;
                }
            }
        }

        $this->writeToFile($inmates);

        return $inmates;
    }

    public function writeToFile($inmates)
    {
        $newArr = [];

        $fileContents = file_get_contents('jcc.txt');

        $decoded = json_decode($fileContents, true);

        foreach ($inmates as $inmate) {
            if(!in_array($inmate['ID'], $decoded)) {
                $newArr[] = $inmate['ID'];
            }
        }

        file_put_contents('jcc.txt', json_encode(array_merge($decoded, $newArr)));
    }
}
