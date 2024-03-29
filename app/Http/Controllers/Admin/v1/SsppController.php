<?php


namespace App\Http\Controllers\Admin\v1;


use App\Http\Controllers\Admin\BaseAdminController;
use App\Models\CategoryModel;
use App\Models\GoodsCollectModel;
use App\Models\MarketModel;
use App\Models\SearchLogModel;
use App\Models\TemplateModel;
use App\Tools\Utils;
use Illuminate\Http\Request;

class SsppController extends BaseAdminController
{
    const URL_LIST = [
        'my' => 'https://my.xiapibuy.com/',
        'tw' => 'https://xiapi.xiapibuy.com/',
        'th' => 'https://th.xiapibuy.com/',
        'br' => 'https://br.xiapibuy.com/',
        'sg' => 'https://sg.xiapibuy.com/',
    ];

    const IMG_BASE = 'https://s-cf-my.shopeesz.com/file/';

    private $platform = '';

    public function getData(Request $request)
    {
        $this->platform = $request->shop??'my';

        if ($request->dataFrom == 'offline'){
            //数据库获取数据
            $res = MarketModel::where([
                ['cid', $request->cids],
                ['shop', $request->shop],
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

        switch ($request->shop_type){
            case 1:
                $data['official_mall'] = 1;
                break;
            case 2:
                $data['shopee_verified'] = 1;
                $data['skip_autocorrect'] = 1;
                break;
            case 3:
                $data['skip_autocorrect'] = 1;
                break;
        }

        if ($type == 1){
            $param = http_build_query($data);
            $url = self::URL_LIST[$this->platform]."api/v4/search/search_items/?".$param;
        }else{
            if ($type == 2){
                $url = self::URL_LIST[$this->platform]."api/v4/shop/get_shop_detail?username=".$keyword;
                $res = $this->curlGet($url, md5('55b03'.md5("username=".$keyword).'55b03'));
                $res = json_decode($res, true);
                sleep(1);
                $data['match_id'] = $res['data']['shopid'];
            }
            unset($data['keyword']);
            $param = http_build_query($data);
            $url = self::URL_LIST[$this->platform]."api/v4/search/search_items/?".$param;
        }
        $k = md5('55b03'.md5($param).'55b03');
        return $this->curlGet($url, $k);
    }

    public function getItemDetail(Request $request){

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
        //1=keyword, 2=store, 3=category, 4=collect
        if ($request->type == 4){
            $goodsList = $this->collectList($request->shop);
            $list = [];
            foreach ($goodsList as $v){
                $temp = json_decode($v['goods_info'], JSON_UNESCAPED_UNICODE);
                $temp['did'] = $v['id'];
                $temp['shop'] = $v['shop'];
                $temp['name'] = "【{$v['shop']}】".$temp['name'];
                $list[] = $temp;
            }
            $data = [
                'goodsList' => $list,
                'info' => []
            ];
        }else{
            $res = $this->getData($request);
            $arr = json_decode($res, true);
            $data = $this->assignData($arr, $request->shop, $request->getDetail==1);
        }
        return Utils::res_ok('ok',$data);
    }

    private function assignData($arr, $shop, $getDetail = false)
    {
        $data = [
            'total_count' => number_format($arr['total_count']),
            'total_ads_count' => $arr['total_ads_count'],
        ];
        if (!$arr['items']){
            Utils::error_throw('获取失败');
//            return Utils::res_error('数据未获取到:'.print_r($arr, true));
        }
        $goodsList = [];
        $perViewProduct = 0;
        $totalPerViewProduct = 0;
        $perProductProfit = 0;
        $totalAvgLike = 0;
        foreach ($arr['items'] as $k => $v){
            $item_basic = $v['item_basic'];
            //标题，链接，图片，最低价，最高价，30天销量，总销量，上架时间，评分，广告词，地方，review
            $name = $item_basic['name'];
            $url = self::URL_LIST[$this->platform].preg_replace("/[\\s|\\[|\\]]+/", '-', str_replace('#','', str_replace('%', '', $item_basic['name']))).'-i.'.$v['shopid'].'.'.$v['itemid'];
            //$imgUrl = 'https://cf.shopee.com.my/file/';
            $imgUrl = 'https://s-cf-my.shopeesz.com/file/';
            $imgList = [
                $imgUrl.$item_basic['images'][0].'_tn'
            ];
            if (count($item_basic['images']) > 1){
                $imgList[] = $imgUrl.$item_basic['images'][1].'_tn';
            }
            //取最低最高平均数
            $price = bcdiv(bcadd($item_basic['price_min'], $item_basic['price_max'],3), 100000*2, 3);
            $sold = $item_basic['sold'];
            $historicalSold = $item_basic['historical_sold'];
            $ctime = date('Y-m-d', $item_basic['ctime']);
            $itemRating = $item_basic['item_rating']['rating_star'];
            $ads_keyword = $v['ads_keyword'];
            $shop_location = $item_basic['shop_location'];
            $cmt_count = $item_basic['cmt_count'];

            //上架天数，平均每日浏览数，30天平均销量，总平均销量，30天利润，总利润，30天平均利润，总平均利润，平均点赞数，平均每商品每天浏览量，review率
            $days = bcdiv(bcsub(time(), $item_basic['ctime']), 86400, 2);
            $days = $days>0?$days:1;
            $avgViewCount = bcdiv($item_basic['view_count'], ($days>30?30:$days), 2);
            $perViewProduct += $avgViewCount;
            $avgSold = bcdiv($sold, ($days>30?30:$days), 2);
            $avgHistoricalSold = bcdiv($historicalSold, $days, 2);
            $soldProfit = bcmul(bcmul($sold, $price, 2), 0.1, 2);
            $soldHistoricalProfit = bcmul(bcmul($historicalSold, $price, 2), 0.1, 2);
            $avgSoldProfit = bcdiv($soldProfit, ($days>30?30:$days), 2);
            $avgSoldHistoricalProfit = bcdiv($soldHistoricalProfit, $days, 2);
            $perProductProfit += $avgSoldHistoricalProfit;
            $avgLike = bcdiv($item_basic['liked_count'], $days, 2);
            $totalAvgLike += $avgLike;
            $profitPerView = bcdiv($soldProfit,($item_basic['view_count']>0?$item_basic['view_count']:1),3);  //由于浏览量是近期的
            $totalPerViewProduct += $profitPerView;

            //店铺信息
//            $param = 'shopid='.$v['shopid'];
//            $k = md5('55b03'.md5($param).'55b03');
//            $shopInfo = $this->curlGet(self::URL_LIST[$shop].'api/v4/product/get_shop_info?'.$param, md5('55b03'.md5($k).'55b03'));
//            $shopInfo = json_decode($shopInfo, true);

            //商品详情  ?itemid=9337328351&shopid=110514716
            if ($getDetail){
                try {
                    $param = "itemid={$v['itemid']}&shopid={$v['shopid']}";
                    $k = md5('55b03'.md5($param).'55b03');
                    $itemDetail = $this->curlGet(self::URL_LIST[$shop].'api/v2/item/get?'.$param, $k);
                    $itemDetail = json_decode($itemDetail, JSON_UNESCAPED_UNICODE);
                }catch (\Exception $e){
                }
            }

            //店铺类型
            $shopType = '';
            if ($item_basic['is_official_shop']){
                $shopType = 'Mall';
            }elseif($item_basic['is_preferred_plus_seller']){
                $shopType = 'Preferred +';
            }elseif ($item_basic['shopee_verified']){
                $shopType = 'Preferred';
            }

            //分类
//            $category = CategoryModel::where('cid', $v['catid'])->first();
//            if (filled($category)){
//                $ids = $category['path'].','.$v['catid'];
//                $cpath = CategoryModel::whereRaw(" cid in ({$ids}) ")
//                    ->orderByRaw(" FIELD(cid,{$ids}) ")
//                    ->select(DB::raw(' group_concat(name," || ") as name'))
//                    ->first();
//            }

            $cpath = '';
            if (isset($itemDetail['item']['categories'])){
                foreach ($itemDetail['item']['categories'] as $cv){
                    $cpath .= $cv['display_name'] . ' > ';
                }
            }

            $goodsList[] = [
                'name' => $name,
                'shopType' => $shopType,
                'cname' => $cpath??'-',
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
                'profitPerView' => $profitPerView,
//                'shopInfo' => isset($shopInfo['data']['account']['username'])?
//                    $shopInfo['data']['account']['username'].'*'.$shopInfo['data']['name'].'*'.date('Y-m-d', $shopInfo['data']['ctime']):'--',
                'shopid' => $v['shopid'],
                'itemid' => $v['itemid'],
                'review' => $cmt_count,
                'reviewRate' => $historicalSold>0?bcdiv($cmt_count,$historicalSold,2):0,
            ];
        }
        $c = count($arr['items']);
        return [
            'goodsList' => $goodsList,
            'info' => array_merge($data, [
                //热度：商品每日平均浏览量 = （累加（view_count/days））/总商品数
                'perViewProduct' => bcdiv($perViewProduct, $c, 2),

                //收益：商品每日平均收益 = （累加（（历史总销量*单价）*0.1）/days）/总商品数
                'perProductProfit' => bcdiv($perProductProfit, $c, 2),

                //转化：近期商品平均浏览收益 = （累加（（（近期销量*单价）*0.1）/浏览量））/总商品数
                'avgProfitPerView' => bcdiv($totalPerViewProduct, $c, 2),

                //热度：平均商品收藏
                'avgAvgLike' => bcdiv($totalAvgLike, $c, 2),
            ])
        ];
    }

    public function getDetail(Request $request)
    {
        $itemid = $request->itemid;
        $shopid = $request->shopid;
        $shop = $request->shop;
        $param = "itemid={$itemid}&shopid={$shopid}";
        $k = md5('55b03'.md5($param).'55b03');
        $itemDetail = $this->curlGet(self::URL_LIST[$shop].'api/v2/item/get?'.$param, $k);
        $itemDetail = json_decode($itemDetail, JSON_UNESCAPED_UNICODE);

        $cpath = '';
        if (isset($itemDetail['item']['categories'])){
            foreach ($itemDetail['item']['categories'] as $cv){
                $cpath .= $cv['display_name'] . ' > ';
            }
        }

        $skuList = [];
        if (isset($itemDetail['item']['models']) && count($itemDetail['item']['models']) > 0){
            foreach ($itemDetail['item']['models'] as $k => $v){
                $temp = [
                    'sold' => $v['sold'],
                    'name' => $v['name'],
                    'price' => bcdiv($v['price'],100000,3),
                    'stock' => $v['stock'],
                    'total' => bcmul($v['sold'], bcdiv($v['price'],100000,3), 2),
                    'image' => isset($itemDetail['item']['tier_variations'][0]['images'][$k]) ? self::IMG_BASE.$itemDetail['item']['tier_variations'][0]['images'][$k].'_tn' : '',
                ];
                foreach ($itemDetail['item']['tier_variations'] as $k2 => $v2){
                    if (isset($v2['images']) && !empty($v2['images'])){
                        foreach ($v2['images'] as $k3 => $v3){
                            if (in_array($v2['options'][$k3], explode(',', $v['name']))){
                                $temp['image'] = self::IMG_BASE.$itemDetail['item']['tier_variations'][$k2]['images'][$k3].'_tn';
                            }
                        }
                    }
                }
                $skuList[] = $temp;
            }
        }

        $temp = [];
        foreach ($skuList as $k => $v){
            $temp[$k] = $v['sold'];
        }

        array_multisort($temp, SORT_DESC, $skuList);

        return Utils::res_ok('ok',[
            'cpath' => $cpath,
            'skuList' => $skuList,
        ]);
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

    public function getCategory_old(Request $request)
    {
        Utils::validator($request, [
            'shop' => 'required'
        ]);

        $data = file_get_contents('./'.$request->shop.'/category.json');
        $data = json_decode($data, JSON_UNESCAPED_UNICODE);
        return Utils::res_ok('ok', $data);
    }

    public function getCategory(Request $request)
    {
        Utils::validator($request, [
            'shop' => 'required'
        ]);
        $cid = $request->cid??0;
        $data = CategoryModel::where([
            ['shop', $request->shop],
            ['pid', $cid],
        ])->get()->toArray();
        return Utils::res_ok('ok', $data);
    }

    public function addCollect(Request $request)
    {
        Utils::validator($request, [
            'row' => 'required'
        ]);
        GoodsCollectModel::insert([
            'goods_info' => $request->row,
            'shop' => $request->shop??''
        ]);
        return Utils::res_ok('ok');
    }

    public function delCollect(Request $request)
    {
        Utils::validator($request, [
            'id' => 'required'
        ]);
        GoodsCollectModel::where('id', $request->id)->update(['status' => 0]);
        return Utils::res_ok('ok');
    }

    private function collectList($shop)
    {
        return GoodsCollectModel::where([
            'status' => 1,
            'shop' => $shop
        ])
            ->select('shop', 'goods_info', 'id')
            ->orderBy('id', 'desc')->get();
    }

    public function saveSearchLog(Request $request)
    {
        $type = $request->type??0;
        $shop = $request->shop??'';
        $keyword = $request->keyword??'';
        $cname = $request->cname??'';
        $params = json_encode($request->toArray(), JSON_UNESCAPED_UNICODE);
        SearchLogModel::insert([
            'type' => $type,
            'shop' => $shop,
            'keyword' => $keyword.','.$cname,
            'params' => $params,
        ]);
        return Utils::res_ok('ok');
    }

    public function showSearchLog(Request $request)
    {
        $where[] = ['status', 1];
        if ($request->type){
            $where[] = ['type', $request->type];
        }
        $list = SearchLogModel::where($where)->orderBy('id', 'desc')->get();
        foreach ($list as $k => $v){
            $list[$k]['params'] = http_build_query(json_decode($v['params'], JSON_UNESCAPED_UNICODE));
        }
        return Utils::res_ok('ok', $list);
    }

    public function delSearchLog(Request $request)
    {
        Utils::validator($request, [
            'id' => 'required'
        ]);
        SearchLogModel::where('id', $request->id)->update(['status' => 0]);
        return Utils::res_ok('ok');
    }

    public function allCategory()
    {
        $res = CategoryModel::select('cid', 'shop', 'name', 'zh_name', 'pid')
//            ->whereIn('shop', ['my','tw','th','sg','br'])
                ->orderBy('id')
                ->get()->toArray();
        $list = [];
        foreach ($res as $k => $v){
            if ($v['pid'] == 0){
                //一级
                $list[$v['shop']]['>'.$v['cid']] = $v;
                unset($res[$k]);
                foreach ($res as $k2 => $v2){
                    if ($v['cid'] == $v2['pid'] && $v['shop'] == $v2['shop']){
                        $list[$v['shop']]['>'.$v['cid']]['child'][$k2] = $v2;
                        unset($res[$k2]);
                        foreach ($res as $k3 => $v3){
                            if ($v2['cid'] == $v3['pid'] && $v2['shop'] == $v3['shop']){
                                $list[$v['shop']]['>'.$v['cid']]['child'][$k2]['child'][$k3] = $v3;
                                unset($res[$k3]);
                                foreach ($res as $k4 => $v4){
                                    if ($v3['cid'] == $v4['pid'] && $v3['shop'] == $v4['shop']){
                                        $list[$v['shop']]['>'.$v['cid']]['child'][$k2]['child'][$k3]['child'][$k4] = $v4;
                                        unset($res[$k4]);
                                        foreach ($res as $k5 => $v5){
                                            if ($v4['cid'] == $v5['pid'] && $v4['shop'] == $v5['shop']){
                                                $list[$v['shop']]['>'.$v['cid']]['child'][$k2]['child'][$k3]['child'][$k4]['child'][$k5] = $v5;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return Utils::res_ok('ok', $list);
    }
}
