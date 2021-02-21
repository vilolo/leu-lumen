<?php


namespace App\Http\Controllers\Admin\v1;


use App\Http\Controllers\Admin\BaseAdminController;
use App\Models\MarketModel;
use App\Models\TemplateModel;
use App\Tools\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class SsppController extends BaseAdminController
{
    const URL_LIST = [
        'my' => 'https://shopee.com.my/',
        'tw' => 'https://xiapi.xiapibuy.com/',
        'th' => 'https://th.xiapibuy.com/',
        'br' => 'https://br.xiapibuy.com/',
        'sg' => 'https://sg.xiapibuy.com/',
    ];

    private $platform = '';

    public function getData(Request $request)
    {
        $this->platform = $request->store??'my';

        if ($request->dataFrom == 'offline'){
            //数据库获取数据
            $res = MarketModel::where([
                ['cid', $request->cids],
                ['shop', $request->store],
            ])->orderBy('id', 'desc')->first();
            return $res->data ?? '数据不存在';
        }

        $keyword = $request->keyword;
        $type = $request->type??1; //1=keyword, 2=store, 3=category
        $minPrice = $request->minPrice??'';
        $maxPrice = $request->maxPrice??'';
        $location = $request->oversea??''; //-1=local,-2=overseas
        $cids = $request->cids??'';

//        echo md5('55b03'.md5('by=sales&keyword=bag&limit=50&newest=0&order=desc&page_type=search&price_max=1000&price_min=0&skip_autocorrect=1&version=2').'55b03');die();
//        $param = 'by=sales&keyword=bag&limit=50&newest=0&order=desc&page_type=search&price_max=1000&price_min=0&skip_autocorrect=1&version=2';

        if ($type != 3){
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
        }else{
            $data = [
                'by' => 'sales',
//            'keyword' => $keyword,
                'limit' => '100',
                'order' => 'desc',
//            'page_type' => ($type==1)?'search':'shop',
                'page_type' => 'search',
                'categoryids' => $cids,
                'version' => '2',
                'price_min' => $minPrice,
                'price_max' => $maxPrice,
                'locations' => $location,
            ];
        }
        $data = array_filter($data);
        $data['newest'] = $request->newest??0;

        if ($type == 1){
            $param = http_build_query($data);
            $url = self::URL_LIST[$this->platform]."api/v2/search_items/?".$param;
        }else{
            $url = self::URL_LIST[$this->platform]."api/v4/shop/get_shop_detail?username=".$keyword;
            $res = $this->curlGet($url, md5('55b03'.md5("username=".$keyword).'55b03'));
            $res = json_decode($res, true);
            sleep(1);
            unset($data['keyword']);
            $data['match_id'] = $res['data']['shopid'];
            $param = http_build_query($data);
            $url = self::URL_LIST[$this->platform]."api/v2/search_items/?".$param;
        }

        $k = md5('55b03'.md5($param).'55b03');
        return $this->curlGet($url, $k);
    }

    public function curlGet($url, $k)
    {
        //https://shopee.com.my/api/v4/shop/get_shop_detail?username=watchgod.my
        //https://shopee.com.my/api/v2/search_items/?by=sales&limit=30&match_id=88257056&newest=0&order=desc&page_type=shop&version=2
        //https://shopee.com.my/api/v2/search_items/?by=sales&keyword=bag&limit=100&order=desc&page_type=search&version=2&newest=0

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

    public function newOrganizeData(Request $request)
    {
        $res = $this->getData($request);
        $arr = json_decode($res, true);
        $data = $this->assignData($arr);
        return Utils::res_ok('ok',$data);
    }

    private function assignData($arr)
    {
        $data = [
            'total_count' => number_format($arr['total_count']),
            'total_ads_count' => $arr['total_ads_count'],
        ];
        if (!$arr['items']){
            return Utils::res_error('数据未获取到:'.print_r($arr, true));
        }
        $goodsList = [];
        foreach ($arr['items'] as $k => $v){
            //标题，链接，图片，最低价，最高价，30天销量，总销量，上架时间，评分，广告词，地方
            $name = $v['name'];
            $url = self::URL_LIST[$this->platform].preg_replace("/[\\s|\\[|\\]]+/", '-', str_replace('#','', str_replace('%', '', $v['name']))).'-i.'.$v['shopid'].'.'.$v['itemid'];
            //$imgUrl = 'https://cf.shopee.com.my/file/';
            $imgUrl = 'https://s-cf-my.shopeesz.com/file/';
            $imgList = [
                $imgUrl.$v['images'][0].'_tn'
            ];
            if (count($v['images']) > 1){
                $imgList[] = $imgUrl.$v['images'][1].'_tn';
            }
            //取最低最高平均数
            $price = bcdiv(bcadd($v['price_min'], $v['price_max'],3), 100000*2, 3);
            $sold = $v['sold'];
            $historicalSold = $v['historical_sold'];
            $ctime = date('Y-m-d', $v['ctime']);
            $itemRating = $v['item_rating']['rating_star'];
            $ads_keyword = $v['ads_keyword'];
            $shop_location = $v['shop_location'];

            //上架天数，平均每日浏览数，30天平均销量，总平均销量，30天利润，总利润，30天平均利润，总平均利润，平均点赞数
            $days = ceil(bcdiv(bcsub(time(), $v['ctime']), 86400, 2));
            $avgViewCount = bcdiv($v['view_count'], $days, 2);
            $avgSold = bcdiv($sold, 30, 2);
            $avgHistoricalSold = bcdiv($historicalSold, $days, 2);
            $soldProfit = bcmul(bcmul($sold, $price, 2), 0.1, 2);
            $soldHistoricalProfit = bcmul(bcmul($historicalSold, $price, 2), 0.1, 2);
            $avgSoldProfit = bcdiv($soldProfit, 30, 2);
            $avgSoldHistoricalProfit = bcdiv($soldHistoricalProfit, $days, 2);
            $avgLike = bcdiv($v['liked_count'], $days, 2);

            $goodsList[] = [
                'name' => $name,
                'images' => $imgList,
                'url' => $url,
                'price' => $price,
                'ctime' => $ctime,
                'days' => $days,
                'sold' => $sold,
                'avgSold' => $avgSold,
                'soldProfit' => $soldProfit,
                'avgSoldProfit' => $avgSoldProfit,
                'historicalSold' => $historicalSold,
                'avgHistoricalSold' => $avgHistoricalSold,
                'soldHistoricalProfit' => $soldHistoricalProfit,
                'avgSoldHistoricalProfit' => $avgSoldHistoricalProfit,
                'avgViewCount' => $avgViewCount,
                'itemRating' => $itemRating,
                'avgLike' => $avgLike,
                'adsKeyword' => $ads_keyword,
                'shopLocation' => $shop_location,
            ];

        }
        return [
            'goodsList' => $goodsList,
            'info' => $data
        ];
    }

    public function getOrganizeData(Request $request)
    {
        $res = $this->getData($request);
        $arr = json_decode($res, true);

//        echo '<pre>';
//        print_r($arr);
//        echo '</pre>';die();

        //商品总数，计算查询商品总销量，计算出平均价格，广告个数
        $data = [
            'total_count' => $arr['total_count'],
            'total_ads_count' => $arr['total_ads_count'],
        ];

        $totalSold = 0;
        $totalPrice = 0;
        $realTotalPrice = 0;
        $goodsList = [];
        if (!$arr['items']){
            return Utils::res_error('数据未获取到:'.print_r($arr, true));
        }
        foreach ($arr['items'] as $k => $v){
            $totalSold += (int)$v['sold'];
            $totalPrice += (int)$v['price'];
            $realTotalPrice += (int)$v['price']*(int)$v['sold'];
            //url，标题，价格，上架时间，天数，点赞数（平均），观看数（平均），历史销量（平均），最近销量，图片
            $days = (int)(time() - (int)$v['ctime'])/86400;
            $days = $days <=0 ? 1 : $days;
//            $imgUrl = 'https://cf.shopee.com.my/file/';
            $imgUrl = 'https://s-cf-my.shopeesz.com/file/';
            $imgList = [
                $imgUrl.$v['images'][0].'_tn'
            ];
            if (count($v['images']) > 1){
                $imgList[] = $imgUrl.$v['images'][1].'_tn';
            }
            $temp = [
                'url' => self::URL_LIST[$this->platform].preg_replace("/[\\s|\\[|\\]]+/", '-', str_replace('#','', str_replace('%', '', $v['name']))).'-i.'.$v['shopid'].'.'.$v['itemid'],
                'name' => $v['name'],
                'price' => ((int)$v['price'])/100000,
                'ctime' => date('Y-m-d', $v['ctime']),
                'days' => sprintf("%.2f",$days),
                'liked_count' => $v['liked_count'],
                'liked_count_avg' => sprintf("%.2f",(int)$v['liked_count']/$days),
                'view_count' => $v['view_count'],
                'view_count_avg' => sprintf("%.2f",(int)$v['view_count']/$days),
                'historical_sold' => $v['historical_sold'],
                'historical_sold_avg' => sprintf("%.2f",(int)$v['historical_sold']/$days),
                'sold' => $v['sold'],
                'ads_keyword' => $v['ads_keyword'],
                'images' => $imgList,
                'shop_location' => $v['shop_location'],
            ];

            $goodsList[] = $temp;
        }

        $data['avgPrice'] = count($arr['items']) > 0 ? sprintf('%.2f', ($totalPrice/100000)/count($arr['items'])) : 0;
        $data['avgSold'] = $data['total_count'] > 0 ? sprintf('%.2f', $totalSold/$data['total_count']) : 0;
        $data['count_sold'] = $totalSold;
        $data['realAvgPrice'] = $totalSold > 0 ? sprintf('%.2f', ($realTotalPrice/100000)/$totalSold) : 0;

//        echo '<pre>';
//        print_r($data);
//        print_r($goodsList);
//        echo '</pre>';die();

        return Utils::res_ok('ok',[
            'goodsList' => $goodsList,
            'info' => $data
        ]);
    }

    public function showTemplate()
    {
        $res = TemplateModel::get();
        return Utils::res_ok('', $res);
    }

    public function saveTemplate(Request $request)
    {
        Utils::validator($request, [
            'shop' => 'required',
            'description' => 'required',
        ]);
        $template = TemplateModel::where('shop', $request->shop)->first();
        if (!$template){
            $template = new TemplateModel();
            $template->shop = $request->shop;
        }
        $template->description = $request->description;
        $template->save();
        return Utils::res_ok();
    }

    public function getCategory(Request $request)
    {
        Utils::validator($request, [
            'shop' => 'required'
        ]);

        $data = file_get_contents('./'.$request->shop.'/category.json');
        $data = json_decode($data, JSON_UNESCAPED_UNICODE);
        return Utils::res_ok('ok', $data);
    }
}
