<?php


namespace App\Http\Controllers\Admin;


use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator as IValidator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


class BaseAdminController extends Controller
{
    /**验证传递参数api
     * @param Request $request
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @throws ApiException
     */
//    protected function validated(Request $request, array $rules, array $messages = []){
//        $validator = IValidator::make($request->all(), $rules, $messages);
//        if ($validator->fails()) {
//            $this->errorBadRequest($validator);
//        }
//    }

    /**
     * 返回错误的请求
     * @param Validator $validator
     * @throws ApiException
     */
    protected function errorBadRequest(Validator $validator)
    {
        // github like error messages
        // if you don't like this you can use code bellow
        //
        //throw new ValidationHttpException($validator->errors());
        $messages = $validator->errors()->toArray();
        if ($messages) {
            $msg = array_shift($messages);
        }
        throw new ApiException($msg[0],422);
    }

    //数据导出
    public function exportData(array $data,$title='数据列表',array $filed=[]){
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('shop');
        $row = 1;
        for ($i=1;$i<=count($filed);$i++){
            $sheet->setCellValueByColumnAndRow($i,$row, $filed[$i-1]);
        }
        $row = 2;
        foreach ($data as $item){
            $i = 1;
            foreach ($item as $v){
                $sheet->setCellValueByColumnAndRow($i,$row, (string)$v);
                $i++;
            }
            $row++;
        }

        $file_name = $title;
        $file_name = $file_name . ".xlsx";
        ob_end_clean();
        ob_start();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$file_name.'"');
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        die();
    }
}
