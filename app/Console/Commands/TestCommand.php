<?php


namespace App\Console\Commands;


use App\Models\CategoryModel;
use Illuminate\Console\Command;

class TestCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'test';

    public function handle(){
//        $this->downCategory();
//        $this->saveToDatabase();
    }

    private function saveToDatabase(){
        $urlList = [
//            'my' => 'https://haiyingshuju.com/category/shopee/Malaysia/data/',
//            'tw' => 'https://haiyingshuju.com/category/shopee/Taiwan/data/',
//            'th' => 'https://haiyingshuju.com/category/shopee/Thailand/data/',
//            'sg' => 'https://haiyingshuju.com/category/shopee/Singapore/data/',
        ];

        foreach ($urlList as $k => $v){
            //获取第一级
            $res = $this->curlGet($v.'category.json', '');
            $resArr = json_decode($res, JSON_UNESCAPED_UNICODE);
            $inputData = [];
            foreach ($resArr as $rv){
                $inputData[] = [
                    'shop' => $k,
                    'cid' => $rv['cid'],
                    'name' => $rv['cname'],
                    'pid' => 0,
                    'path' => 0,
                ];
            }
            CategoryModel::insert($inputData);
            $this->saveChildToDatabase($resArr, $k, $v, 0);
        }
    }

    private function saveChildToDatabase($list, $shop, $url, $path){
        foreach ($list as $v){
            $newUrl = $url.$v['cid'].'/';
            $res = $this->curlGet($newUrl.'category.json', '');
            echo $newUrl.'category.json', PHP_EOL;
            $resArr = json_decode($res, JSON_UNESCAPED_UNICODE);
            $inputData = [];
            $newPath = $path.','.$v['cid'];
            if ($resArr){
                foreach ($resArr as $rv){
                    $inputData[] = [
                        'shop' => $shop,
                        'cid' => $rv['cid'],
                        'name' => $rv['cname'],
                        'pid' => $v['cid'],
                        'path' => $newPath,
                    ];
                }
                if ($inputData){
                    CategoryModel::insert($inputData);
                    $this->saveChildToDatabase($resArr, $shop, $newUrl, $newPath);
                }
            }
        }
    }

    private function downCategory()
    {
        $urlList = [
            'my' => 'https://haiyingshuju.com/category/shopee/Malaysia/data/',
//            'tw' => 'https://haiyingshuju.com/category/shopee/Taiwan/data/',
//            'th' => 'https://haiyingshuju.com/category/shopee/Thailand/data/',
//            'sg' => 'https://haiyingshuju.com/category/shopee/Singapore/data/',
        ];

        foreach ($urlList as $k => $v){
            $res = $this->curlGet($v.'category.json', '');
            $path = "./public/{$k}";
            $resArr = json_decode($res, JSON_UNESCAPED_UNICODE);
            if ($resArr){
                @mkdir($path);
                file_put_contents($path.'/category.json', $res);
                $this->downCategoryChild($resArr, $path, $v);
            }
        }
    }

    private function downCategoryChild($list, $pPath, $url){
        foreach ($list as $v){
            $newUrl = $url.$v['cid'].'/';
//            echo $newUrl.'category.json', PHP_EOL;
            $res = $this->curlGet($newUrl.'category.json', '');
            $path = "{$pPath}/{$v['cid']}";
            $resArr = json_decode($res, JSON_UNESCAPED_UNICODE);
            if ($resArr){
                echo $newUrl.'category.json', PHP_EOL;
                mkdir($path);
                file_put_contents($path.'/category.json', $res);
                $this->downCategoryChild($resArr, $path, $newUrl);
            }
        }
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
}
