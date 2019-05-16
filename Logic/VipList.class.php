<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/5
 * Time: 14:53
 */

namespace Admin\Logic;
use Admin\Logic\UserList;


class VipList extends BaseLogic{

    public function vip_data($stime='',$etime='', $type='player'){

        if($type=='player'){
            $_SESSION['deal_type'] = 'player';
        }elseif ($type=='vip'){
            $_SESSION['deal_type'] = 'vip';
        }else{
            $_SESSION['deal_type'] = 'all';
        }

        $today = date('Y-m-d');
        $tomorrow = date("Y-m-d",strtotime("+1 day"));

        if($stime==''||$etime==''){
            $stime = $today;
            $etime = $tomorrow;
        }

        $m = M('AccountsInfo');
        $m1 = M('GameScoreInfo','','DB_TREASURE');
        $ul = new UserList();
        $map['usertype'] = 4;
        $users = $m->where($map)->field('UserID,NickName,RegisterDate')->select();

        //获取玩家ID
        foreach ($users as $k=>$v){
            $uids[] = $v['userid'];
        }

        $sum_gold = $ul->get_sum_gold($uids);
        $remark = $ul->get_remark($uids);
        $insure_gold = $ul->get_sum_win($uids,false,true);

        //总赠送
        $sum_present = $this->vip_pr($uids,$type,'P',$stime,$etime);

        //总赠送笔数
        $sum_present_times = $this->vip_pr($uids,$type,'P',$stime,$etime,'times');

        //总赠送人数
        $sum_present_person = $this->vip_pr($uids,$type,'P',$stime,$etime,'person');

        //总接收
        $sum_received = $this->vip_pr($uids,$type,'R',$stime,$etime);


        //总接收笔数
        $sum_received_times = $this->vip_pr($uids,$type,'R',$stime,$etime,'times');

        //总接收人数
        $sum_received_person = $this->vip_pr($uids,$type,'R',$stime,$etime,'person');


        $title['t_vips'] = count($uids);

        $map_si['UserID'] = array('in',$uids);

        $title['sum_insure'] = $m1->where($map_si)->sum('InsureScore');

        //总税收
        $title['t_all_tax'] = abs($this->vip_pr($uids,'player','P',$stime,$etime,'sum_present'))*0.02;

        foreach ($users as $k=>$v){

            $users[$k]['sum_gold'] = $sum_gold[$v['userid']]['amount'];
            $title['t_gold']+=$users[$k]['sum_gold'];
            $users[$k]['remark'] = $remark[$v['userid']]['remark']?$remark[$v['userid']]['remark']:'--';
            $users[$k]['insure_gold'] = $insure_gold[$v['userid']]['insurescore'];
            $users[$k]['sum_present'] = $sum_present[$v['userid']]['total']?abs($sum_present[$v['userid']]['total']):0;

            if($type=='player'){
                $users[$k]['sum_tax'] = abs($users[$k]['sum_present'])*0.02;
            }else{
                $users[$k]['sum_tax'] = 0;
            }


            $title['t_pgold']+=$users[$k]['sum_present'];

            $users[$k]['sum_received'] = $sum_received[$v['userid']]['total']?abs($sum_received[$v['userid']]['total']):0;
            $title['t_rgold']+=$users[$k]['sum_received'];

            $users[$k]['sum_present_times'] = $sum_present_times[$v['userid']]['total']?$sum_present_times[$v['userid']]['total']:0;
            $title['t_ptimes']+=$users[$k]['sum_present_times'];

            $users[$k]['sum_present_person'] = $sum_present_person[$v['userid']]['total']?$sum_present_person[$v['userid']]['total']:0;
            $title['t_pperson']+=$users[$k]['sum_present_person'];

            $users[$k]['sum_received_times'] = $sum_received_times[$v['userid']]['total']?$sum_received_times[$v['userid']]['total']:0;
            $title['t_rtimes']+=$users[$k]['sum_received_times'];

            $users[$k]['sum_received_person'] = $sum_received_person[$v['userid']]['total']?$sum_received_person[$v['userid']]['total']:0;
            $title['t_rperson']+=$users[$k]['sum_received_person'];

            $users[$k]['ab'] = abs($users[$k]['sum_present'])-abs($users[$k]['sum_received']);

            $title['t_ab']+=$users[$k]['ab'];

            $users[$k]['sum_times'] = $users[$k]['sum_present_times'] + $users[$k]['sum_received_times'] ;
            $title['t_sum_times'] += $users[$k]['sum_times'];

            $users[$k]['sum_person'] = $users[$k]['sum_present_person'] + $users[$k]['sum_received_person'] ;
            $title['t_sum_person'] += $users[$k]['sum_person'];
        }

        $date['stime'] = $stime;
        $date['etime'] = $etime;

        $result['data'] = $users;
        $result['date'] = $date;
        $result['title'] = $title;


        return $result;

    }

    public function vip_pr($uids = array(),$type,$key,$stime='',$etime='',$countType='gold'){

        $map['ChangeDate'] = array('between',$stime.','.$etime);

        $m1 =  M('ScoreChangeDetail','','DB_RECOED');

            if($key=='P'){
                $map['UserID'] = array('in',$uids);

                if($type=='player'){
                    $map['UserId2'] = array('not in',$uids);
                }elseif ($type=='vip'){
                    $map['UserId2'] = array('in',$uids);
                }

                if($countType=='times'){
                    $data = $m1->where($map)->where('UserID!=UserId2')->field('UserID,count(UserID) as total')->group('UserID')->select();
                }elseif ($countType=='person'){
                    $data = $m1->where($map)->where('UserID!=UserId2')->field('UserID,count(distinct UserId2) as total')->group('UserID')->select();
                }elseif ($countType=='sum_present'){
                    $data = $m1->where($map)->where('UserID!=UserId2')->sum('Score');
                    return $data;
                }else{
                    $data = $m1->where($map)->where('UserID!=UserId2')->field('UserID,sum(Score) as total')->group('UserID')->select();
                }

                $data = $this->changeKeys($data, 'userid');

            }elseif ($key=='R'){

                $map['UserId2'] = array('in',$uids);

                if($type=='player'){
                    $map['UserID'] = array('not in',$uids);
                }elseif ($type=='vip'){
                    $map['UserID'] = array('in',$uids);
                }

                if($countType=='times'){
                    $data = $m1->where($map)->where('UserID!=UserId2')->field('UserId2,count(UserId2) as total')->group('UserId2')->select();
                }elseif ($countType=='person'){
                    $data = $m1->where($map)->where('UserID!=UserId2')->field('UserId2,count(distinct UserID) as total')->group('UserId2')->select();
                }else{
                    $data = $m1->where($map)->where('UserID!=UserId2')->field('UserId2,sum(Score) as total')->group('UserId2')->select();
                }

                $data = $this->changeKeys($data, 'userid2');
            }

        return $data;
    }

}