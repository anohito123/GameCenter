<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/1
 * Time: 13:42
 */

namespace Admin\Logic;


class PlayerDealList extends BaseLogic
{

	public function  data($uid = '',$stime='',$etime='',$type = 'R'){
		$m = M('AccountsInfo');
		$m1 =  M('ScoreChangeDetail','','DB_RECOED');
	
		$today = date('Y-m-d');
        $tomorrow = date("Y-m-d",strtotime("+1 day"));
		
		if($stime=='' && $etime==''){
			$stime = $today	;
			$etime = $tomorrow;
		}
		
		$vip = $m->where('usertype=4')->field('UserID')->select();
		foreach ($users as $k=>$v){
            $vips[] = $v['userid'];
        }
		
		$map['UserID'] = array('not in',$vips);
		$map['UserId2'] = array('not in',$vips);
		$map['ChangeDate'] = array('between',$stime.','.$etime);
		
		$count = $m->where($map)->where('UserID!=UserId2')->count();
        $page = new \Think\Page($count,10);
			
        $data = $m->where($map)->where('UserID!=UserId2')
        ->order('ChangeDate desc')->limit($page->firstRow.','.$page->listRows)
		->select();
		
		$result['show'] = $page->show();
		$result['data'] = $data;
		
		return $result;
		
	}

}