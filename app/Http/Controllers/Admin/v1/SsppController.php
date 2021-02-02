<?php


namespace App\Http\Controllers\Admin\v1;


use App\Http\Controllers\Admin\BaseAdminController;
use Laravel\Lumen\Http\Request;


class SsppController extends BaseAdminController
{
    const URL_LIST = [
        'my' => 'https://shopee.com.my'
    ];
    public function test(Request $request)
    {
        $platform = $request->platform??'my';
        $keyword = $request->keyword??'bag';
        $type = $request->type; //1=keyword, 2=store
        $minPrice = $request->minPrice??0;
        $maxPrice = $request->maxPrice??1000;
        $location = $request->location; //-1=local,-2=overseas
        $by = 'sales';

//        echo md5('55b03'.md5('by=sales&keyword=bag&limit=50&newest=0&order=desc&page_type=search&price_max=1000&price_min=0&skip_autocorrect=1&version=2').'55b03');die();

//        $param = 'by=sales&keyword=bag&limit=50&newest=0&order=desc&page_type=search&price_max=1000&price_min=0&skip_autocorrect=1&version=2';
        $param = "by={$by}&keyword={$keyword}&limit=100&newest=0&order=desc&page_type=search&version=2";
        $url = self::URL_LIST[$platform]."/api/v2/search_items/?".$param;
        $k = md5('55b03'.md5($param).'55b03');
        return $this->curlGet($url, $k);
    }

    public function curlGet($url, $k){

        $header  = array(
            'if-none-match-: 55b03-'.$k,
            'user-agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.111 Safari/537.36'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}
