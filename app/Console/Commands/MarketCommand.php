<?php


namespace App\Console\Commands;


use App\Models\CategoryModel;
use App\Models\MarketModel;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class MarketCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'market';

    public function handle(){
//        $this->market();
//        $this->fixData();
        echo 'ok';
    }

    private function market()
    {
        $shop = 'tw';
//        $file = './public/'.$platform.'/category.json';
//        $categoryData = file_get_contents($file);
//        $arr = json_decode($categoryData, JSON_UNESCAPED_UNICODE);

//        $arr = [
//            ['cid' => 100],
//            ['cid' => 1611],
//            ['cid' => 70],
//            ['cid' => 73],
//            ['cid' => 75],
//        ];

//        $arr = CategoryModel::where([
//            ['shop', $platform],
//            ['pid', '>', 0],
//        ])->select('cid')->get()->toArray();

        $arr = CategoryModel::where([
            ['category.shop', $shop],
            ['category.pid', '>', 0],
        ])->whereRaw(' m.id is null ')
            ->leftJoin('market as m', 'm.cid', 'category.cid')
            ->select('category.cid')->get()->toArray();

        $this->saveData($arr, $shop);
    }

    private function fixData(){
        $arr = MarketModel::whereRaw(' left(data, 24) <> \'{"show_disclaimer":false\' ')
            ->select('id', 'cid', 'shop')->get()->toArray();

        foreach ($arr as $v){
            $cid = $v['cid'];
            $str = '{"show_disclaimer":null';
            $res = $this->getData($v['shop'], $cid);
            if (substr($res,0, strlen($str)) == $str){
                sleep(1);
                $res = $this->getData($v['shop'], $cid);
                if (substr($res,0, strlen($str)) == $str){
                    sleep(1);
                    $res = $this->getData($v['shop'], $cid);
                }
            }
            MarketModel::where('id', $v['id'])->update(['data' => $res]);
            echo $v['id'], '==';
//            sleep(1);
        }
    }

    private function saveData($arr, $shop){
        $data = [];
        foreach ($arr as $v){
            $cid = $v['cid'];
            $str = '{"show_disclaimer":false';
            $res = $this->getData($shop, $cid);
            if (substr($res,0, strlen($str)) != $str){
                sleep(1);
                $res = $this->getData($shop, $cid);
                if (substr($res,0, strlen($str)) == $str){
                    sleep(1);
                    $res = $this->getData($shop, $cid);
                }
            }
            $data[] = [
                'shop' => $shop,
                'cid' => $cid,
                'data' => $res,
            ];
            if (count($data) >= 20){
                MarketModel::insert($data);
                $data = [];
            }
            echo 1;
//            sleep(1);
        }
        if ($data){
            MarketModel::insert($data);
        }
    }

    const URL_LIST = [
        'my' => 'https://shopee.com.my/',
        'tw' => 'https://xiapi.xiapibuy.com/',
        'th' => 'https://th.xiapibuy.com/',
        'br' => 'https://br.xiapibuy.com/',
        'sg' => 'https://sg.xiapibuy.com/',
    ];

    public function getData($platform, $categoryids)
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
            'locations' => $location??-2,
        ];
        $data = array_filter($data);
        $data['newest'] = $request->newest??0;

        $param = http_build_query($data);
        $url = self::URL_LIST[$platform]."api/v2/search_items/?".$param;
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
