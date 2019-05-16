<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/1
 * Time: 13:42
 */

namespace Admin\Logic;


class Notice extends BaseLogic
{

	public function  data(){
        $m = M('AA_bulletin');

        $data = $m->select();

        return $data;

	}

	public function  add($title,$content,$index){
        $m = M('AA_bulletin');

        $data['title'] = $title;
        $data['content'] = $content;
        $data['index'] = $index;


        $result = $m->add($data);

        if($result){
            return 'ok';
        }else{
            return null;
        }
    }

    public function  update($id,$title,$content,$index){
        $m = M('AA_bulletin');

        $map['id'] = $id;
        $data['title'] = $title;
        $data['content'] = $content;
        $data['index'] = $index;


        $result = $m->where($map)->save($data);

        if($result){
            return 'ok';
        }else{
            return null;
        }
    }

    public  function  delete($id){
        $m = M('AA_bulletin');

        $map['id'] = $id;

        $result = $m->where($map)->delete();

        if($result){
            return 'ok';
        }else{
            return null;
        }

    }
}