<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/1
 * Time: 13:42
 */

namespace Admin\Logic;


class BaseLogic
{
    //计算房间的当日输赢or指定日期输赢or总输赢（参数：多个房间ID、开始日期、结束日期）

    /**
     * 二维数组修改key
     * @param array $array
     * @param $newKey
     * @return array
     */
    protected function changeKeys(array $array, $newKey) {
        $newArr = array();
        if(!empty($array)){
            foreach ($array as $item) {
                is_object($item) ? $item = json_decode(json_encode($item), true) : '';
                $newArr[$item[$newKey]] = $item;
            }
        }

        return $newArr;
    }


}