<?php


namespace App\Http\Controllers\Admin\v1;


use App\Http\Controllers\Admin\BaseAdminController;
use Illuminate\Http\Request;


class SsppController extends BaseAdminController
{
    const URL_LIST = [
        'my' => 'https://shopee.com.my/',
        'tw' => 'https://xiapi.xiapibuy.com/',
        'th' => 'https://th.xiapibuy.com/',
        'br' => 'https://br.xiapibuy.com/',
        'sg' => 'https://sg.xiapibuy.com/',
    ];

    public function getData(Request $request)
    {
        $platform = $request->platform??'my';
        $keyword = $request->keyword??'bag';
        $type = $request->type??1; //1=keyword, 2=store
        $minPrice = $request->minPrice??'';
        $maxPrice = $request->maxPrice??'';
        $location = $request->location??''; //-1=local,-2=overseas

//        echo md5('55b03'.md5('by=sales&keyword=bag&limit=50&newest=0&order=desc&page_type=search&price_max=1000&price_min=0&skip_autocorrect=1&version=2').'55b03');die();
//        $param = 'by=sales&keyword=bag&limit=50&newest=0&order=desc&page_type=search&price_max=1000&price_min=0&skip_autocorrect=1&version=2';

        $data = [
            'by' => 'sales',
            'keyword' => $keyword,
            'limit' => '100',
            'order' => 'desc',
            'page_type' => ($type==1)?'search':'shop',
            'version' => '2',
            'price_min' => $minPrice,
            'price_max' => $maxPrice,
            'locations' => $location,
        ];
        $data = array_filter($data);
        $data['newest'] = $request->newest??0;

        if ($type == 1){
            $param = http_build_query($data);
            $url = self::URL_LIST[$platform]."/api/v2/search_items/?".$param;
        }else{
            $url = self::URL_LIST[$platform]."/api/v4/shop/get_shop_detail?username=".$keyword;
            $res = $this->curlGet($url, md5('55b03'.md5("username=".$keyword).'55b03'));
            $res = json_decode($res, true);

            sleep(1);

            unset($data['keyword']);
            $data['match_id'] = $res['data']['shopid'];
            $param = http_build_query($data);
            //https://shopee.com.my/api/v2/search_items/?by=sales&limit=30&match_id=298441267&newest=0&order=desc&page_type=shop&version=2
            $url = self::URL_LIST[$platform]."/api/v2/search_items/?".$param;
        }

        $k = md5('55b03'.md5($param).'55b03');
        return $this->curlGet($url, $k);
    }

    public function curlGet($url, $k)
    {
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

    public function getOrganizeData(Request $request)
    {
        $data = $this->getData($request);
        return $data;
    }
}
