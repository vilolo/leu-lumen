<?php


namespace App\Console\Commands;


use App\Models\MarketModel;
use Illuminate\Console\Command;

class CategoryAnalysisCommand extends Command
{
    protected $signature = 'analysis';

    public function handle(){
        $list = MarketModel::get();
        $data = [];
        foreach ($list as $v){
            $temp = [];

            //cid
            //cname

            $data[] = $temp;
        }

    }
}
