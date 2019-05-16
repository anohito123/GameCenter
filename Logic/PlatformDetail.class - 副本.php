<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/1
 * Time: 13:42
 */

namespace Admin\Logic;


class PlatformDetail extends BaseLogic
{
    public function data($stime='',$etime=''){
        if($stime!=''&& $etime!=''){}else{

            $stime = date("Y-m-d",strtotime("-1 month"));
            $etime = date('Y-m-d',strtotime("+1 day"));

        }
        $reg_sum = $this->get_reg_sum($stime,$etime);
        $received_sum = $this->get_received_presents($stime,$etime,'R');
        $present_sum = $this->get_received_presents($stime,$etime,'P');

        $received_score = $this->get_received_presents($stime,$etime,'R',true);
        $present_score = $this->get_received_presents($stime,$etime,'P',true);
        //$max_online = $this->get_day_maxOnline($stime,$etime);

        $p_times = $this->get_received_presents($stime,$etime,'P',false,true);
        $r_times = $this->get_received_presents($stime,$etime,'R',false,true);

        $today_active = $this->get_today_active($stime,$etime);

        $today_vip_tax = $this->get_today_vip_tax($stime,$etime);

        //房间税收
        $today_room_tax = $this->get_today_room_tax($stime,$etime);

        //dump($received_sum);exit;

        $begintime = strtotime($stime);$endtime = strtotime($etime);
        for ($start = $begintime; $start <= $endtime; $start += 24 * 3600) {

            $time[] = date("Y-m-d", $start);
        }

        array_pop($time);

        foreach ($time as $k=>$v){
            //统计时间
            $data[$k]['date'] = $time[$k];
        }



        foreach ($data as $k=>$v){
            //编号
            $data[$k]['id'] = (count($data)-$k);
            //注册总数
            $data[$k]['reg_sum'] = $reg_sum[$v['date']]['total']?$reg_sum[$v['date']]['total']:0;
            //总接收数
            $data[$k]['received_sum'] = $received_sum[$v['date']]['total']?$received_sum[$v['date']]['total']:0;
            //赠送总数
            $data[$k]['present_sum'] = $present_sum[$v['date']]['total']?$present_sum[$v['date']]['total']:0;
            //总接收分数
            $data[$k]['received_score'] = $received_score[$v['date']]['total']?$received_score[$v['date']]['total']:0;
            //总赠送分数
            $data[$k]['present_score'] = $present_score[$v['date']]['total']?$present_score[$v['date']]['total']:0;

            //总赠送笔数
            $data[$k]['p_times'] = $p_times[$v['date']]['total']?$p_times[$v['date']]['total']:0;

            //总接收笔数
            $data[$k]['r_times'] = $r_times[$v['date']]['total']?$r_times[$v['date']]['total']:0;

            //最高在线
            //$data[$k]['max_online'] = $max_online[$v['date']]['total']?$max_online[$v['date']]['total']:0;
            //当日活跃
            $data[$k]['active'] = $today_active[$v['date']]['total']?$today_active[$v['date']]['total']:0;

            //顺差
            $data[$k]['ab'] =$data[$k]['present_score'] - $data[$k]['received_score'];

            //当日vip税收
            $data[$k]['today_vip_tax'] =$today_vip_tax[$v['date']]['total']?abs($today_vip_tax[$v['date']]['total']):0;
            //当日房间税收
            $data[$k]['today_room_tax'] =$today_room_tax[$v['date']]['total']?$today_room_tax[$v['date']]['total']:0;

        }

        return $data;

    }

    public function get_today_vip_tax($stime='',$etime=''){
        $m = M('AccountsInfo');
        $m1 =  M('ScoreChangeDetail','','DB_RECOED');
        $vip = $m->where('usertype=4')->select();

        $vips = array();

        foreach ($vip as $v){
            $vips[] = $v['userid'];
        }

        $map['ChangeDate'] = array('between',$stime.','.$etime);
        $map['UserId2'] = array('not in',$vips);

        $data = $m1->where($map)->where('UserID!=UserId2')
            ->field('CONVERT(varchar(10), ChangeDate,21) as daytime,sum(Score)*0.02 as total')
            ->group('CONVERT(varchar(10), ChangeDate,21)')
            ->select();

        $data = $this->changeKeys($data, 'daytime');
        return $data;
    }

    public function get_today_room_tax($stime='',$etime=''){
        $m = M('AA_Log_GameRoomRevenue','','DB_SERVER');
        $map['ChangeDate'] = array('between',$stime.','.$etime);
        $data = $m->where($map)->field('date,sum(revenue) as total')->group('date')->select();
        $data = $this->changeKeys($data, 'date');
        return $data;
    }


    public function get_today_active($stime='',$etime=''){
        $m = M('RecordUserLeave','','DB_RECOED');

        $map['LeaveTime'] = array('between',$stime.','.$etime);
        //$data = $m->where($map)->Distinct(true)->field('count(UserID) as total,CONVERT(varchar(10),LeaveTime,21) as dayDate')->group('CONVERT(varchar(10),LeaveTime,21)')->select();
       // $data = $this->changeKeys($data, 'daydate');

        $data = $m->query("SELECT count(distinct(UserID)) as total,CONVERT(varchar(10),LeaveTime,21) as daydate 
        FROM RecordUserLeave 
        where LeaveTime >'$stime' and LeaveTime <'$etime' 
        GROUP BY CONVERT(varchar(10),LeaveTime,21)");

        $data = $this->changeKeys($data, 'daydate');
        return $data;
    }


    /**
     * 获取指定时间注册人数
     * （参数：开始日期、结束日期,都不传查询所有）
     */
    public function get_reg_sum($stime='',$etime=''){
        $m =  M('AA_ZZ_Log_Register');

        if($stime!=''&& $etime!=''){

            $map['LogTime'] = array('between',$stime.','.$etime);
            $log_data = $m->where($map)->field('CONVERT(varchar(10), LogTime,21) as daytime,count(User_Id) as total')->group('CONVERT(varchar(10), LogTime,21)')->select();

        }else{

            $log_data = $m->field('CONVERT(varchar(10), LogTime,21) as daytime,count(User_Id) as total')->group('CONVERT(varchar(10), LogTime,21)')->select();
        }

        $log_data = $this->changeKeys($log_data, 'daytime');

        return $log_data;

    }
    /**
     * 获取指定时间接收或赠送人数/分数（只统计普通玩家对VIP的接收或赠送）
     * （参数：开始日期、结束日期,交易类型r接收p赠送,是否为计算分数若为true则计算分数，否则计算人数,都不传查询所有,默认查询接收人数）
     */
    public function get_received_presents($stime='',$etime='',$type='R',$isCountScore = false,$isCountTimes= false){
        $m1 =  M('ScoreChangeDetail','','DB_RECOED');
        $m2 = M('AccountsInfo');

        $vip = $m2->where('usertype=4')->select();

        $vips = array();

        foreach ($vip as $v){
            $vips[] = $v['userid'];
        }

        //dump($vips);

        if($type == 'R'){
            $map['UserID']=array('in',$vips);
            $map['UserId2']=array('not in',$vips);
        }elseif($type == 'P'){
            $map['UserID']=array('not in',$vips);
            $map['UserId2']=array('in',$vips);
        }

        if($stime!=''&& $etime!=''){
            $map['ChangeDate'] = array('between',$stime.','.$etime);

            if($isCountScore){
                $log_data = $m1->where($map)
                    ->field('CONVERT(varchar(10), ChangeDate,21) as daytime,sum(Score) as total')
                    ->group('CONVERT(varchar(10), ChangeDate,21)')
                    ->select();
            }elseif ($isCountTimes){
                if($type == 'R'){
                    $log_data = $m1->where($map)
                        ->field('CONVERT(varchar(10), ChangeDate,21) as daytime,count(UserId2) as total')
                        ->group('CONVERT(varchar(10), ChangeDate,21)')
                        ->select();
                }elseif ($type == 'P'){
                    $log_data = $m1->where($map)
                        ->field('CONVERT(varchar(10), ChangeDate,21) as daytime,count(UserID) as total')
                        ->group('CONVERT(varchar(10), ChangeDate,21)')
                        ->select();
                }


            } else{
                $log_data = $m1->where($map)
                    ->field('CONVERT(varchar(10), ChangeDate,21) as daytime,count(DISTINCT(UserId2)) as total')
                    ->group('CONVERT(varchar(10), ChangeDate,21)')
                    ->select();
            }



        }else{

            if($isCountScore){
                $log_data = $m1->where($map)
                    ->field('CONVERT(varchar(10), ChangeDate,21) as daytime,sum(Score) as total')
                    ->group('CONVERT(varchar(10), ChangeDate,21)')
                    ->select();
            }elseif ($isCountTimes){
                if($type == 'R'){
                    $log_data = $m1->where($map)
                        ->field('CONVERT(varchar(10), ChangeDate,21) as daytime,count(UserId2) as total')
                        ->group('CONVERT(varchar(10), ChangeDate,21)')
                        ->select();
                }elseif ($type == 'P'){
                    $log_data = $m1->where($map)
                        ->field('CONVERT(varchar(10), ChangeDate,21) as daytime,count(UserID) as total')
                        ->group('CONVERT(varchar(10), ChangeDate,21)')
                        ->select();
                }


            } else{
                $log_data = $m1->where($map)
                    ->field('CONVERT(varchar(10), ChangeDate,21) as daytime,count(DISTINCT(UserId2)) as total')
                    ->group('CONVERT(varchar(10), ChangeDate,21)')
                    ->select();
            }

        }

        $log_data = $this->changeKeys($log_data, 'daytime');

        return $log_data;

    }

    /**
     * 获取指定日期最高在线人数
     * （参数：开始日期、结束日期、不传查询历史最高）
     */
    public function get_day_maxOnline($stime='',$etime=''){
        $m =  M('onlineTotal');

        if($stime!=''&& $etime!=''){
            $map['date'] = array('between',$stime.','.$etime);
            $log_data = $m->where($map)->field('dayDate,max(total) as total')->group('dayDate')->select();

        }else{
            $log_data = $m->field('dayDate,max(total) as total')->group('dayDate')->select();
        }

        $log_data = $this->changeKeys($log_data, 'daydate');

        return $log_data;

    }

    public function platform_all(){
        $m = M('AccountsInfo');
        $m1 = M('RecordUserEnter','','DB_RECOED');
        $m2= M('RecordUserLeave','','DB_RECOED');
        $m3= M('onlineTotal');
        $m4 = M('GameScoreLocker','','DB_TREASURE');


        $stime = date("Y-m-d");
        $etime = date('Y-m-d',strtotime("+1 day"));
        $mapdate['RegisterDate'] = array('between',$stime.','.$etime);
        $mapdate1['LeaveTime'] = array('between',$stime.','.$etime);
        $map['UserID'] = array('gt',10000);

        //用户注册
        $data['all_reg'] = $m->where($map)->count();
        $data['all_ios'] = $m->where($map)->where('AppPlatform=1')->count();
        $data['all_adr'] = $m->where($map)->where('AppPlatform=2')->count();
        $data['all_pc'] = $m->where($map)->where('AppPlatform=3')->count();
        $data['today_reg'] = $m->where($map)->where($mapdate)->count();

        //登录情况
        $data['all_login'] = $m1->where($map)->count();
        $today_login = $this->get_today_active($stime,$etime);
        $data['today_login'] = $today_login[$stime]['total'];

        $data['online'] = $m4->count();

        $map3['date'] = array('gt',$stime);
        $daydate = $m3->where($map3)->field('sum(total) as total')->group('date')->select();

        foreach ($daydate as $v){
            $max[] = $v['total'];
        }

        $allday = $m3->field('sum(total) as total')->group('date')->select();

        foreach ($allday as $v){
            $max_all[] = $v['total'];
        }

        $data['max_online'] = max($max);
        $data['max_all_online'] = max($max_all);
        return $data;


    }


}