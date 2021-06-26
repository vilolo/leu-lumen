<?php


namespace App\Console\Commands;


use App\Models\CategoryModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
//        $this->saveCategory();
        $this->saveCategoryV3();
    }

    public function saveCategoryV3()
    {
        $shop = 'sg';
        $file = base_path().'/public/data/category_'.$shop.'.json';
        $res = file_get_contents($file);
        $list = json_decode($res, true)['data']['list'];
        $data = [];
        foreach ($list as $k => $v){
            $data[$v['id']] = [
                'shop' => $shop,
                'cid' => $v['id'],
                'name' => $v['name'],
                'source_name' => $v['display_name'],
                'pid' => $v['parent_id'],
                'path' => 0,
                'has_children' => $v['has_children']?1:0,
            ];
            if ($v['children']){
                foreach ($v['children'] as $v2){
                    $data[$v2['id']] = [
                        'shop' => $shop,
                        'cid' => $v2['id'],
                        'name' => $v2['name'],
                        'source_name' => $v2['display_name'],
                        'pid' => $v2['parent_id'],
                        'path' => '0,'.$v['id'],
                        'has_children' => $v2['has_children']?1:0,
                    ];
                    if ($v2['children']){
                        foreach ($v2['children'] as $v3){
                            $data[$v3['id']] = [
                                'shop' => $shop,
                                'cid' => $v3['id'],
                                'name' => $v3['name'],
                                'source_name' => $v3['display_name'],
                                'pid' => $v3['parent_id'],
                                'path' => '0,'.$v['id'].','.$v2['id'],
                                'has_children' => $v3['has_children']?1:0,
                            ];
                            if ($v3['children']){
                                foreach ($v3['children'] as $v4){
                                    $data[$v4['id']] = [
                                        'shop' => $shop,
                                        'cid' => $v4['id'],
                                        'name' => $v4['name'],
                                        'source_name' => $v4['display_name'],
                                        'pid' => $v4['parent_id'],
                                        'path' => '0,'.$v['id'].','.$v2['id'].','.$v3['id'],
                                        'has_children' => $v4['has_children']?1:0,
                                    ];
                                    if ($v4['children']){
                                        foreach ($v4['children'] as $v5){
                                            $data[$v5['id']] = [
                                                'shop' => $shop,
                                                'cid' => $v5['id'],
                                                'name' => $v5['name'],
                                                'source_name' => $v5['display_name'],
                                                'pid' => $v5['parent_id'],
                                                'path' => '0,'.$v['id'].','.$v2['id'].','.$v3['id'].','.$v4['id'],
                                                'has_children' => $v5['has_children']?1:0,
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        DB::table('category_new')->insert($data);
        echo 'ok';
    }

    public function saveCategory(){
        $shop = 'br';
        $file = base_path().'/public/data/category_'.$shop.'.json';
        $res = file_get_contents($file);
        $list = json_decode($res, true)['data']['list'];
        $data = [];
        foreach ($list as $k => $v){
            $path = 0;
            if ($v['parent_id'] == 0){
                $data[$v['id']] = [
                    'shop' => $shop,
                    'cid' => $v['id'],
                    'name' => $v['name'],
                    'source_name' => $v['display_name'],
                    'pid' => 0,
                    'path' => $path,
                ];
                unset($list[$k]);
                $path .= ','.$v['id'];
                foreach ($list as $k2 => $v2){
                    if ($v2['parent_id'] == $v['id']){
                        $data[$v2['id']] = [
                            'shop' => $shop,
                            'cid' => $v2['id'],
                            'name' => $v2['name'],
                            'source_name' => $v2['display_name'],
                            'pid' => $v2['parent_id'],
                            'path' => $path,
                        ];
                        unset($list[$k2]);
                        foreach ($list as $k3 => $v3){
                            if ($v3['parent_id'] == $v2['id']){
                                $data[$v3['id']] = [
                                    'shop' => $shop,
                                    'cid' => $v3['id'],
                                    'name' => $v3['name'],
                                    'source_name' => $v3['display_name'],
                                    'pid' => $v3['parent_id'],
                                    'path' => $path.','.$v2['id'],
                                ];
                                unset($list[$k3]);
                                foreach ($list as $k4 => $v4){
                                    if ($v4['parent_id'] == $v3['id']){
                                        $data[$v4['id']] = [
                                            'shop' => $shop,
                                            'cid' => $v4['id'],
                                            'name' => $v4['name'],
                                            'source_name' => $v4['display_name'],
                                            'pid' => $v4['parent_id'],
                                            'path' => $path.','.$v2['id'].','.$v3['id'],
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        DB::table('category_new')->insert($data);
        echo 'ok';
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
