<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/1
 * Time: 13:42
 */

namespace Admin\Logic;


class RoomConfig extends BaseLogic
{


    public function get_room(array $sids = array()){
        $m = M('GameRoomItem','','DB_SERVER');

        $map['ServerID']=array('in',$sids);

        $log_data = $m->where($map)->field('ServerID,RoomName')->select();

        $log_data = $this->changeKeys($log_data, 'serverid');

        return $log_data;

    }

    public function room_today_value($key){
        $m = M('AA_Log_GameRoomScore','','DB_SERVER');
        $today = date('Y-m-d');
        $tomorrow = date("Y-m-d",strtotime("+1 day"));
        $map['LogDate'] = array('between',$today.','.$tomorrow);

        $data = $m->where($map)->sum($key);

        return $data;
    }

    public function room_sum_tax(){
        $m =  M('AA_Log_GameRoomRevenue','','DB_SERVER');
        $today = date('Y-m-d');
        $map['date'] = $today;
        $data = $m->where($map)->sum('revenue');
        return $data;

    }

    public function room_sum_blood(){
        $m = M('AA_Log_GameRoomBlood','','DB_SERVER');
        $today = date('Y-m-d');
        $tomorrow = date("Y-m-d",strtotime("+1 day"));
        $map['datetime'] = array('between',$today.','.$tomorrow);

        $data = $m->where($map)->sum('blood');

        return $data;
    }

    public function room_rate($sid=''){

        $m = M('AA_Log_GameRoomScore','','DB_SERVER');
        $map['ServerID'] = $sid;

        $count = $m->where($map)->count();
        $page = new \Think\Page($count,15);
        $data = $m->where($map)->limit($page->firstRow.','.$page->listRows)->order('LogDate desc')->select();

        foreach ($data as $v){
            $sids = $v['serverid'];
        }

        $roomname = $this->get_room($sids);

        foreach ($data as $k=>$v){
            $data[$k]['roomname'] = $roomname[$v['serverid']]['roomname'];
            $data[$k]['rate'] = number_format($v['outscore']/$v['eatscore'], 2 );
        }

        $result['data'] = $data;
        $result['show'] = $page->show();

        return $result;
    }

    //计算房间的当日输赢or指定日期输赢or总输赢（参数：多个房间ID、开始日期、结束日期）
    public function room_data($isOne=false){
        $m = M('GameRoomInfo','','DB_Plat');


        $data = $m->select();

        if($isOne){
            return $data;
        }else{
            $serverIds = array();
            foreach ($data as $v){
                $serverIds[] = $v['serverid'];
            }

            $today_score = $this->room_win($serverIds);
            $cheat_blood = $this->room_cheat_blood($serverIds);
            $today_tax = $this->room_today_tax($serverIds);
            $out_rate = $this->room_out_rate($serverIds);

            //总输赢
            $room_sum_win = $m->field('sum(OutCheatRate) - sum(EatCheatRate) as total')->select();
            //总血池
            $all_blood = $m->sum('MaxBloodScore');

            //当日总输赢
            $today_win = $this->room_today_value('TodayScore');

            //当日吃分
            $today_eat = $this->room_today_value('EatScore');

            //当日吐分
            $today_out = $this->room_today_value('OutScore');

            //吞吐率
            $today_rate = $today_eat > 0 ? number_format($today_out/$today_eat, 3) : 0; // 吞吐率

            //当日税收
            $today_sum_tax = $this->room_sum_tax();

            //当日总控制血池
            $today_sum_blood = $this->room_sum_blood();


            $other['room_sum_win'] = $room_sum_win[0]['total'];
            $other['all_blood'] = $all_blood;
            $other['today_win'] = $today_win;
            $other['today_eat'] = $today_eat;
            $other['today_out'] = $today_out;
            $other['today_rate'] = $today_rate;
            $other['today_sum_tax'] = $today_sum_tax;
            $other['today_sum_blood'] = $today_sum_blood;



            foreach ($data as $k=>$v){
                //当日输赢
                $data[$k]['today_score'] = $today_score[$v['serverid']]['total']?$today_score[$v['serverid']]['total']:0;

                //点控血池
                $data[$k]['cheat_blood'] = $cheat_blood[$v['serverid']]['total']?$cheat_blood[$v['serverid']]['total']:0;

                //当日税收
                $data[$k]['today_tax'] = $today_tax[$v['serverid']]['total']?$today_tax[$v['serverid']]['total']:0;

                //计算吐分率
                $eat = $out_rate[$v['serverid']]['eattotal'];
                $out = $out_rate[$v['serverid']]['outtotal'];


                if($out==0){
                    $outEat_Score = 1;
                }elseif($eat==0){
                    $outEat_Score = 0;
                }else{
                    $outEat_Score = $out/$eat;
                }
                $rate = sprintf("%.4f",$outEat_Score)*100;
                $data[$k]['out_rate'] =  $rate.'%';

            }

            $result['data'] = $data;
            $result['other'] = $other;

            return $result;
        }

    }

    /**
     * 计算房间的当日输赢or指定日期输赢or总输赢
     * （参数：多个房间ID、开始日期、结束日期）
     * 默认为查询当日输赢、第二个参数传‘all’为查询总输赢
     */
    public function room_win(array $serverIds = array(), $startTime='',$endTime=''){


        $today = date('Y-m-d');
        $tomorrow = date("Y-m-d",strtotime("+1 day"));

        $m =  M('AA_Log_GameRoomScore','','DB_SERVER');

        $map['ServerID']=array('in',$serverIds);

        if($startTime!='' && $endTime!=''){
            $map['LogDate'] = array('between',$startTime.','.$endTime);
        }elseif ($startTime =='all'){}
        else{
            $map['LogDate'] = array('between',$today.','.$tomorrow);
        }

        $log_data = $m->where($map)->field('serverid,sum(todayscore) as total')->group('serverid')->select();

        $log_data = $this->changeKeys($log_data, 'serverid');


        return $log_data;

    }
    /**
     * 获取点控血池（传数组房间ID）
     */
    public function room_cheat_blood(array $serverIds = array()){
        $m =  M('AA_Log_GameRoomCheatBlood','','DB_SERVER');

        $map['room_id']=array('in',$serverIds);
        $log_data = $m->where($map)->field('room_id,sum(cheat_blood) as total')->group('room_id')->select();

        $log_data = $this->changeKeys($log_data, 'room_id');

        return $log_data;
    }

    /**
     * 获取吐分率（传数组房间ID）
     */
    public function room_out_rate(array $serverIds = array()){
        $m =  M('AA_Log_GameRoomScore','','DB_SERVER');

        $map['room_id']=array('in',$serverIds);
        $log_data = $m->where($map)->field('ServerID,sum(EatScore) as EatTotal,sum(OutScore) as OutTotal')->group('ServerID')->select();

        $log_data = $this->changeKeys($log_data, 'serverid');

        return $log_data;
    }


    /**
     * 获取当日税收（数组房间ID,起始时间，默认查询当天）
     */
    public function room_today_tax(array $serverIds = array(),$startTime='',$endTime=''){
        $today = date('Y-m-d');
        $tomorrow = date("Y-m-d",strtotime("+1 day"));

        $m =  M('AA_Log_GameRoomRevenue','','DB_SERVER');

        $map['room_id']=array('in',$serverIds);

        if($startTime!='' && $endTime!=''){
            $map['date'] = array('between',$startTime.','.$endTime);
        }else{
            $map['date'] = array('between',$today.','.$tomorrow);
        }

        $log_data = $m->where($map)->field('room_id,sum(revenue) as total')->group('room_id')->select();

        $log_data = $this->changeKeys($log_data, 'room_id');

        return $log_data;
    }


    //修改房间血池 logType=7
    public function do_room_cheat($roomid,$cheat){
        $m = M('GameRoomItem','','DB_SERVER');

        $map['ServerID'] = $roomid;

        $old_cheat = $m->where($map)->getField('BloodScore');

        $roomname = $m->where($map)->getField('RoomName');
				
		
        $save['BloodScore'] = $old_cheat+$cheat;
		
	//	dump($roomid.':'.$cheat);
	//	dump($old_cheat.':'.$save['BloodScore']);exit;
		
		
        $result = $m->where($map)->save($save);

        if($result){
            $m2 =  M('Web_DoLog');
            $data['logName'] = $_SESSION['admin'];
            $data['doIP'] = 0;
            $data['res'] = '成功';
            $data['logType'] = 7;
            $data['remark'] = '为房间【'.$roomname.'】增加：【'.$cheat.'】血池值';

            return $m2->add($data);
        }else{
            return null;
        }
    }

    //修改房间平衡值 logType=8
    public function do_room_balance($roomid,$cheat){
        $m = M('GameRoomItem','','DB_SERVER');

        $map['ServerID'] = $roomid;

        $old_cheat = $m->where($map)->getField('MaxEatScore');

        $roomname = $m->where($map)->getField('RoomName');

        $save['MaxEatScore'] = $old_cheat+$cheat;

        $result = $m->where($map)->save($save);

        if($result){
            $m2 =  M('Web_DoLog');
            $data['logName'] = $_SESSION['admin'];
            $data['doIP'] = 0;
            $data['res'] = '成功';
            $data['logType'] = 8;
            $data['remark'] = '为房间【'.$roomname.'】增加：【'.$cheat.'】平衡值';

            return $m2->add($data);
        }else{
            return null;
        }
    }

}