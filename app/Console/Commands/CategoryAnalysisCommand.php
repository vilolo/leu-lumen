<?php


namespace App\Console\Commands;


use App\Models\CategoryAnalysisModel;
use Illuminate\Console\Command;

class CategoryAnalysisCommand extends Command
{
    protected $signature = 'analysis';

    public function handle(){
//        $list = MarketModel::where('id', '<', 200)->get();

        $shop = 'my';
        $location = '-1';   //-1本地，-2oversea
        echo $shop,',',$location,'>';
        $list = CategoryAnalysisModel::where([
            ['shop', $shop],
            ['location', $location],
        ])
            ->whereRaw('total_goods is null')
            ->select('id', 'cid')
            ->orderBy('id', 'asc')
            ->get();

//        $list = CategoryAnalysisModel::where([
//            ['total_goods', 0],
//            ['shop', $shop],
//            ['location', $location],
//        ])
//            ->select('id', 'cid')
//            ->get();

        foreach ($list as $citem){
            $str = '{"show_disclaimer":false';
            $cid = $citem['cid'];
            $res = $this->getData($shop, $cid, $location);
            if (substr($res,0, strlen($str)) != $str){
                sleep(1);
                $res = $this->getData($shop, $cid, $location);
                if (substr($res,0, strlen($str)) == $str){
                    sleep(1);
                    $res = $this->getData($shop, $cid, $location);
                }
            }

            $arr = json_decode($res, JSON_UNESCAPED_UNICODE);
            $totalAvgLike = 0;
            $perViewProduct = 0;
            $perProductProfit = 0;
            $totalPerViewProduct = 0;
            if ($arr){
                foreach ($arr['items'] as $k => $v){
                    $item_basic = $v['item_basic'];
                    $price = bcdiv(bcadd($item_basic['price_min'], $item_basic['price_max'],3), 100000*2, 3);
                    $historicalSold = $item_basic['historical_sold'];

                    //上架天数，平均每日浏览数，30天平均销量，总平均销量，30天利润，总利润，30天平均利润，总平均利润，平均点赞数，平均每商品每天浏览量
                    $days = bcdiv(bcsub(time(), $item_basic['ctime']), 86400, 2);
                    $days = $days>0?$days:1;
                    $avgViewCount = bcdiv($item_basic['view_count'], ($days>30?30:$days), 2);
                    $perViewProduct += $avgViewCount;
                    $soldProfit = bcmul(bcmul($item_basic['sold'], $price, 2), 0.1, 2);
                    $soldHistoricalProfit = bcmul(bcmul($historicalSold, $price, 2), 0.1, 2);
                    $avgSoldHistoricalProfit = bcdiv($soldHistoricalProfit, $days, 2);
                    $perProductProfit += $avgSoldHistoricalProfit;
                    $avgLike = bcdiv($item_basic['liked_count'], $days, 2);
                    $totalAvgLike += $avgLike;
                    $profitPerView = bcdiv($soldProfit,($item_basic['view_count']>0?$item_basic['view_count']:1),3);
                    $totalPerViewProduct += $profitPerView;
                }

                $c = count($arr['items']);
                $c = $c<=0?1:$c;

//                $temp = [
//                    //热度：商品每日平均浏览量 = （累加（view_count/days））/总商品数
//                    'perViewProduct' => bcdiv($perViewProduct, $c, 2),
//
//                    //收益：商品每日平均收益 = （累加（（历史总销量*单价）*0.1）/days）/总商品数
//                    'perProductProfit' => bcdiv($perProductProfit, $c, 2),
//
//                    //转化：商品总平均浏览收益 = （累加（（（历史总销量*单价）*0.1）/总浏览量））/总商品数
//                    'avgProfitPerView' => bcdiv($totalPerViewProduct, $c, 2),
//
//                    //热度：平均商品收藏
//                    'avgAvgLike' => bcdiv($totalAvgLike, $c, 2),
//                ];

                CategoryAnalysisModel::where('id', $citem->id)->update([
                    'total_goods' => $arr['total_count']??0,
                    'avg_day_profit' => bcdiv($perProductProfit, $c, 2),
                    'avg_day_view' => bcdiv($perViewProduct, $c, 2),
                    'avg_day_like' => bcdiv($totalAvgLike, $c, 2),
                    'avg_view_profit' => bcdiv($totalPerViewProduct, $c, 2),
                ]);
            }

            echo 1;
        }
    }

    const URL_LIST = [
        'my' => 'https://my.xiapibuy.com/',   //https://shopee.com.my/
        'tw' => 'https://xiapi.xiapibuy.com/',
        'th' => 'https://th.xiapibuy.com/',
        'br' => 'https://br.xiapibuy.com/',
        'sg' => 'https://sg.xiapibuy.com/',
    ];

    public function getData($platform, $categoryids, $location)
    {
//        $platform = 'my';
//        $categoryids = '16';
        $data = [
            'by' => 'sales',
//            'keyword' => $keyword,
            'limit' => '100',
            'order' => 'desc',
//            'page_type' => ($type==1)?'search':'shop',
            'page_type' => 'search',
            'categoryids' => $categoryids,
            'version' => '2',
            'price_min' => $minPrice??'',
            'price_max' => $maxPrice??'',
        ];

        if ($location){
            $data['locations'] = $location;
        }

        $data = array_filter($data);
        $data['newest'] = $request->newest??0;

        $param = http_build_query($data);
        $url = self::URL_LIST[$platform]."api/v4/search/search_items/?".$param;
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
}
