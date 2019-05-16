<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/1
 * Time: 13:42
 */

namespace Admin\Logic;

class UserList extends BaseLogic
{
    //获取玩家上次买分VIP信息
    public function get_buy_log($uid,$f){
        $m =  M('ScoreChangeDetail','','RDDB_RECOED');

        $map['UserId2'] = $uid;
        $buy = $m->where($map)->where('UserId2!=UserID')->limit(1)->order('ChangeDate desc')->select();
        //$buy = $m->where($map)->where('UserId2!=UserID')->max('ChangeDate');
        $buyid = $buy[0]['userid'];

        if($buyid==null){
            return null;
        }

        if($f=='buy_name'){
            $m2 = M('AccountsInfo','','RDDB_USER');

            $buy_name = $m2->where('UserID=%d',$buyid)->getField('NickName');

            return $buy_name;

        }elseif ($f=='buy_id'){
            return $buyid;
        }elseif ($f=='buy_remark'){
            $m3 = M('AccountsInfoExtend','','RDDB_USER');

            $buy_remark = $m3->where('UserID=%d',$buyid)->getField('Remark');

            return $buy_remark;
        }elseif ($f=='buy_gold'){
            $buy_gold = $buy[0]['score'];

            return $buy_gold;
        }



    }



    //用户输赢
    public function win_data($sid='',$stime='',$etime='',$sort){


        $today = date('Y-m-d');
        $tomorrow = date("Y-m-d",strtotime("+1 day"));

        if($stime==''||$etime==''){
            $stime = $today;
            $etime = $tomorrow;
        }

        $m = M('AA_ZZ_Log_PropChange','','RDDB_USER');

        if($sid!=null){
            $map['ServerId'] = array('like',$sid.'%');
        }

        $map['User_Id'] = array('gt',10000);
        $map['ChangeType_Id'] = 1;
        $map['Prop_Id'] = 1;
        $map['LogTime'] = array('between',$stime.','.$etime);



        $count = count($m->where($map)->field('User_Id')->group('User_Id')->select());
        $page = new \Think\Page($count,10);

        $data = $m->where($map)
            ->table('AA_ZZ_Log_PropChange t1,QPTreasureDB.dbo.GameScoreInfo t2')
            ->where('User_Id=t2.UserID')
            ->field('User_Id,sum(Amount) as today_win,max(RealScore) as log_win,max(CheatRate) as cheat,max(LimitScore) as limit,max(LogTime) as logtime')
            ->group('User_Id')
            ->order($sort.' desc')
            ->limit($page->firstRow.','.$page->listRows)
            ->select();

        foreach ($data as $k=>$v){
            $uids[] = $v['user_id'];
        }

        $nick_name = $this->get_nick($uids);
        $remark = $this->get_remark($uids);

        foreach ($data as $k=>$v){
            $data[$k]['nick_name'] = $nick_name[$v['user_id']]['nickname'];
            $data[$k]['remark'] = $remark[$v['user_id']]['remark']?$remark[$v['user_id']]['remark']:'--';
            $map['ServerId'] = array('like','%');
            $rooms = $m->where($map)
                ->where('User_Id='.$v['user_id'])
                ->field('distinct ServerId')
                ->select();

            $roomstr = '';
            foreach ($rooms as $v){
                $roomstr=$roomstr.$v['serverid'].',';
            }
            $data[$k]['rooms'] = $roomstr;


        }
        $_SESSION['stime'] = $stime;
        $_SESSION['etime'] = $etime;
        $_SESSION['sid'] = $sid;
        $_SESSION['sort'] = $sort;

        $res['data'] = $data;
        $res['show'] = $page->show();

        return $res;


    }

    //玩家留言
    public function message($uid='',$stime='',$etime=''){

        $m = M('UserAdvice');
        if($stime!=''||$etime!=''){
            $map['updateDate'] =  array('between',$stime.','.$etime);
        }

        if($uid!=''){
            $map['UserID'] = $uid;
            $f = $m->where($map)->select();
            if(!$f){return null;}

        }else{
            $map['UserID'] = array('gt',10000);
        }


        $count =count($m->where($map)
            ->field('UserID')
            ->group('UserID')
            ->select()) ;
        $page = new \Think\Page($count,10);
        $data = $m->where($map)
            ->field('UserID,max(updateDate) as date,count(UserID) as sum_msg')
            ->group('UserID')
            ->order('date desc')

            ->limit($page->firstRow.','.$page->listRows)
            ->select();

        foreach ($data as $v){
            $uids[] = $v['userid'];
        }

        $msg = $this->get_last_message($uids);
        $nick_name = $this->get_nick($uids);
        $remark = $this->get_remark($uids);

        foreach ($data as $k=>$v){
            $data[$k]['msg'] = $msg[$v['userid']]['advice'];
            $data[$k]['nick_name'] = $nick_name[$v['userid']]['nickname'];
            $data[$k]['remark'] = $remark[$v['userid']]['remark']?$remark[$v['userid']]['remark']:'--';
        }

        $res['data'] = $data;
        $res['show'] = $page->show();

       return $res;
    }

    //获取玩家最新一条留言
    public function get_last_message(array $uids=array()){
        $m = M('UserAdvice');

        foreach ($uids as $v){

            $data[] = $m->where('UserID='.$v)
                ->field('UserID,Advice,updateDate')
                ->order('updateDate desc')
                ->limit(1)
                ->select();

        }

        $data = array_reduce($data, 'array_merge', array());

        $data = $this->changeKeys($data, 'userid');

       return $data;

    }

    //玩家留言详情
    public function msg_detail($uid){
        $m = M('UserAdvice');

        $map['UserID'] = $uid;
        $f = $m->where($map)->select();
        if(!$f){return null;}

        $data = $m->where($map)
            ->field('UserID,Advice,updateDate')
            ->select();

        return $data;
    }

    //回复
    public function add_reply($uid,$reply){
        $m = M('WEB_UserMessage');

        $map['UserID'] = $uid;
        $map['reply'] = $reply;
        $map['isRead'] = 0;

        $result = $m->add($map);

        return $result;
    }



	//玩家交易
	public function  deal_data($uid = '',$stime='',$etime='',$type = 'R',$sort,$page_count){
        $title['pr_type'] = $type;
        $title['page_count'] = $page_count;


        $m = M('AccountsInfo');
		$m1 =  M('ScoreChangeDetail','','DB_RECOED');

		$today = date('Y-m-d');
        $tomorrow = date("Y-m-d",strtotime("+1 day"));
        $uids = array();

		if($stime==''||$etime==''){
			$stime = $today;
			$etime = $tomorrow;
		}

		//总接收
        $t_r = $m1->query("SELECT sum(t.sum_r) from (SELECT sum(Score) as sum_r
FROM [dbo].[ScoreChangeDetail] 
where UserID!=UserId2

and UserID  in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and UserId2 not in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and ChangeDate BETWEEN '$stime' and '$etime'
GROUP BY UserId2
) as  t");

		//总赠送
        $t_p = $m1->query("SELECT sum(t.sum_p) from (SELECT sum(Score) as sum_p
FROM [dbo].[ScoreChangeDetail] 
where UserID!=UserId2

and UserID not in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and UserId2  in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and ChangeDate BETWEEN '$stime' and '$etime'
GROUP BY UserID
) as  t");

        //接收人数
        $t_r_count = $m1->query("SELECT count(UserId2) as sum_r
FROM [dbo].[ScoreChangeDetail] 
where UserID!=UserId2

and UserID  in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and UserId2 not in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and ChangeDate BETWEEN '$stime' and '$etime'
GROUP BY UserId2");

        //赠送人数
        $t_p_count = $m1->query("SELECT count(UserID) as sum_r
FROM [dbo].[ScoreChangeDetail] 
where UserID!=UserId2

and UserID  not in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and UserId2 in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and ChangeDate BETWEEN '$stime' and '$etime'
GROUP BY UserID");



        $title['t_r'] = abs($t_r[0]['']);
        $title['t_p'] = abs($t_p[0]['']);
        $title['t_ab'] = $title['t_p'] - $title['t_r'];
        $title['t_r_count'] = count($t_r_count);
        $title['t_p_count'] = count($t_p_count);
        $title['t_pr_count'] = $title['t_r_count']+$title['t_p_count'];



        $date['stime'] = $stime;
		$date['etime'] = $etime;
		
		$vip = $m->where('usertype=4')->field('UserID')->select();
		foreach ($vip as $k=>$v){
            $vips[] = $v['userid'];
        }
		$map['ChangeDate'] = array('between',$stime.','.$etime);
        $sqlmap['t1.ChangeDate'] = array('between',$stime.','.$etime);
		if($type == 'R'){
			$map['UserID'] = array('in',$vips);
			$map['UserId2'] = array('not in',$vips);

            $sqlmap['t1.UserID'] = array('in',$vips);
            $sqlmap['t1.UserId2'] = array('not in',$vips);
			
			$count = $m1->where($map)->where('UserID!=UserId2')
			->field('distinct UserId2')->select();
			
			$count = count($count);
        	$page = new \Think\Page($count,$page_count);


        	$data = $m1->table('ScoreChangeDetail t1,QPGameUserDB.dbo.AA_Shop_Prop_UserProp t2')
                ->where($sqlmap)->where('t1.UserID!=t1.UserId2')->where('t1.UserId2=t2.User_Id')

			->field('t1.UserId2,max(t2.Amount) as sum_gold,count(UserId2) as r_count,sum(Score) as r_sum,max(ChangeDate) as last_date')
			->group('t1.UserId2')
			->order('r_sum')
                ->limit($page->firstRow.','.$page->listRows)
			->select();


        	$t_gold = $m1->query("SELECT sum(t.sum_gold) from (SELECT max(Amount) as sum_gold
FROM [dbo].[ScoreChangeDetail] t1,QPGameUserDB.dbo.AA_Shop_Prop_UserProp t2
where t1.UserID!=UserId2
and UserId2=User_Id
and t1.UserID  in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and UserId2 not in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and ChangeDate BETWEEN '$stime' and '$etime'
GROUP BY UserId2
) as  t");

        	$t_log_win = $m1->query("SELECT sum(t.log_win) from (SELECT max(t2.RealScore) as log_win
FROM [dbo].[ScoreChangeDetail] t1,QPTreasureDB.dbo.GameScoreInfo t2
where t1.UserID!=UserId2
and UserId2=t2.UserID

and t1.UserID  in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and UserId2 not in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and ChangeDate BETWEEN '$stime' and '$etime'
GROUP BY UserId2
) as  t");
            $t_today_win = $m1->query("SELECT sum(t.log_win) from (SELECT max(t2.day_income) as log_win
FROM [dbo].[ScoreChangeDetail] t1,QPTreasureDB.dbo.GameScoreInfo t2
where t1.UserID!=UserId2
and UserId2=t2.UserID

and t1.UserID  in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and UserId2 not in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and ChangeDate BETWEEN '$stime' and '$etime'
GROUP BY UserId2
) as  t");




        	$title['t_gold'] = $t_gold[0][''];
            $title['t_log_win'] = $t_log_win[0][''];
            $title['t_today_win'] = $t_today_win[0][''];




            //获取玩家ID
            foreach ($data as $k=>$v){
                $uids[] = $v['userid2'];
            }

           //dump($data);


		}elseif($type == 'P'){
			$map['UserID'] = array('not in',$vips);
			$map['UserId2'] = array('in',$vips);
			
			$count = $m1->where($map)->where('UserID!=UserId2')
			->field('distinct UserID')->select();
			
			$count = count($count);
        	$page = new \Think\Page($count,$page_count);
        	$data = $m1->table('ScoreChangeDetail t1,QPGameUserDB.dbo.AA_Shop_Prop_UserProp t2')
                ->where($map)->where('UserID!=UserId2')->where('t1.UserID=t2.User_Id')


			->field('UserID,max(t2.Amount) as sum_gold,count(UserID) as p_count,sum(Score) as p_sum,max(ChangeDate) as last_date')
			->group('UserID')
			->order('p_sum')
                ->limit($page->firstRow.','.$page->listRows)
			->select();


            $t_gold = $m1->query("SELECT sum(t.sum_gold) from (SELECT max(Amount) as sum_gold
FROM [dbo].[ScoreChangeDetail] t1,QPGameUserDB.dbo.AA_Shop_Prop_UserProp t2
where t1.UserID!=UserId2
and UserID=User_Id
and t1.UserID not in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and UserId2 in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and ChangeDate BETWEEN '$stime' and '$etime'
GROUP BY UserID
) as  t");

            $t_log_win = $m1->query("SELECT sum(t.log_win) from (SELECT max(t2.RealScore) as log_win
FROM [dbo].[ScoreChangeDetail] t1,QPTreasureDB.dbo.GameScoreInfo t2
where t1.UserID!=UserId2
and t1.UserID=t2.UserID

and t1.UserID not  in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and UserId2  in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and ChangeDate BETWEEN '$stime' and '$etime'
GROUP BY t1.UserID
) as  t");

            $t_today_win = $m1->query("SELECT sum(t.log_win) from (SELECT max(t2.day_income) as log_win
FROM [dbo].[ScoreChangeDetail] t1,QPTreasureDB.dbo.GameScoreInfo t2
where t1.UserID!=UserId2
and t1.UserID=t2.UserID

and t1.UserID not  in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and UserId2  in (SELECT UserID from QPGameUserDB.dbo.AccountsInfo where usertype = 4)
and ChangeDate BETWEEN '$stime' and '$etime'
GROUP BY t1.UserID
) as  t");



            $title['t_gold'] = $t_gold[0][''];
            $title['t_log_win'] = $t_log_win[0][''];
            $title['t_today_win'] = $t_today_win[0][''];


            //获取玩家ID
            foreach ($data as $k=>$v){
                $uids[] = $v['userid'];
            }
		}




        $nick_name = $this->get_nick($uids);
        $insure_gold = $this->get_sum_win($uids,false,true);
        $log_win = $this->get_sum_win($uids,true);
        $today_win = $this->get_sum_win($uids);
        $last_cheat = $this->get_last_cheat($uids);
        $reg_date = $this->get_reg_date($uids);
        $remark = $this->get_remark($uids);


        $prmap['ChangeDate'] = array('between',$stime.','.$etime);

        $received_sum = $this->get_received_presents($uids,'R',false, $prmap);
        $r_count = $this->get_received_presents($uids,'R', false, $prmap,true);

        $present_sum = $this->get_received_presents($uids,'P',false,$prmap);
        $p_count = $this->get_received_presents($uids,'P',false, $prmap,true);

        $r_all = $this->get_received_presents($uids,'R');
        $p_all = $this->get_received_presents($uids,'P');
        $cheat = $this->get_cheat_limit($uids);

       // dump($present_sum);exit;
        if($type=='R'){
            foreach ($data as $k=>$v){

                $data[$k]['nick_name'] = $nick_name[$v['userid2']]['nickname'];
                $data[$k]['insure_gold'] = $insure_gold[$v['userid2']]['insurescore'];
                $data[$k]['log_win'] = $log_win[$v['userid2']]['realscore'];
                $data[$k]['today_win'] = $today_win[$v['userid2']]['day_income']?$today_win[$v['userid2']]['day_income']:0;
                $data[$k]['last_cheat'] = $last_cheat[$v['userid2']]['endcheatdate'];
                $data[$k]['reg_date'] = $reg_date[$v['userid2']]['registerdate'];
                $data[$k]['p_sum'] = $present_sum[$v['userid2']]['total']?$present_sum[$v['userid2']]['total']:0;
                $data[$k]['p_time'] = $present_sum[$v['userid2']]['time']?substr($present_sum[$v['userid2']]['time'],0,10):'--';

                //dump(count($data));exit;

                $data[$k]['p_count'] = $p_count[$v['userid2']]['total']?$p_count[$v['userid2']]['total']:0;
                $data[$k]['remark'] = $remark[$v['userid2']]['remark']?$remark[$v['userid2']]['remark']:'--';

                $data[$k]['r_all'] = $r_all[$v['userid2']]['total']?$r_all[$v['userid2']]['total']:0;
                $data[$k]['p_all'] = $p_all[$v['userid2']]['total']?$p_all[$v['userid2']]['total']:0;

                $data[$k]['cheat'] = $cheat[$v['userid2']]['cheatrate'];

            }
        }elseif ($type=='P'){
            foreach ($data as $k=>$v){

                $data[$k]['nick_name'] = $nick_name[$v['userid']]['nickname'];
                $data[$k]['insure_gold'] = $insure_gold[$v['userid']]['insurescore'];
                $data[$k]['log_win'] = $log_win[$v['userid']]['realscore'];
                $data[$k]['today_win'] = $today_win[$v['userid']]['day_income']?$today_win[$v['userid2']]['day_income']:0;
                $data[$k]['last_cheat'] = $last_cheat[$v['userid']]['endcheatdate'];
                $data[$k]['reg_date'] = $reg_date[$v['userid']]['registerdate'];
                $data[$k]['r_sum'] = $received_sum[$v['userid']]['total']?$received_sum[$v['userid']]['total']:0;
                $data[$k]['r_count'] = $r_count[$v['userid']]['total']?$r_count[$v['userid']]['total']:0;
                $data[$k]['remark'] = $remark[$v['userid']]['remark']?$remark[$v['userid']]['remark']:'--';
                $data[$k]['p_time'] = $present_sum[$v['userid']]['time']?substr($present_sum[$v['userid']]['time'],0,10):'--';
                $data[$k]['r_all'] = $r_all[$v['userid']]['total']?$r_all[$v['userid']]['total']:0;
                $data[$k]['p_all'] = $p_all[$v['userid']]['total']?$p_all[$v['userid']]['total']:0;

                $data[$k]['cheat'] = $cheat[$v['userid']]['cheatrate'];

               // $data[$k]['p_time'] = $received_sum[$v['userid']]['total']?$received_sum[$v['userid']]['total']:0;
            }
        }



        //dump($present_sum);
        foreach ($data as $k=>$v){
            $data[$k]['tab'] =   abs($data[$k]['p_sum']) - abs($data[$k]['r_sum']);
            //$data[$k]['p_time'] = $present_sum[$v['userid']]['time']?substr($present_sum[$v['userid']]['time'],0,10):'--';
            //$data[$k]['p_time'] = $present_sum[$v['userid']]['time']?$present_sum[$v['userid']]['time']:0;
        }

		$result['show'] = $page->show();

        if($sort!=0){
            $sort ==1?array_multisort(array_column($data,'last_cheat'),SORT_DESC,$data):'';
            $sort ==2?array_multisort(array_column($data,'remark'),SORT_DESC,$data):'';
            $sort ==3?array_multisort(array_column($data,'sum_gold'),SORT_DESC,$data):'';
            $sort ==4?array_multisort(array_column($data,'insure_gold'),SORT_DESC,$data):'';
            $sort ==5?array_multisort(array_column($data,'log_win'),SORT_DESC,$data):'';
            $sort ==6?array_multisort(array_column($data,'today_win'),SORT_DESC,$data):'';
            $sort ==7?array_multisort(array_column($data,'r_all'),SORT_ASC,$data):'';
            $sort ==8?array_multisort(array_column($data,'p_all'),SORT_ASC,$data):'';
            $sort ==9?array_multisort(array_column($data,'r_sum'),SORT_ASC,$data):'';
            $sort ==11?array_multisort(array_column($data,'p_sum'),SORT_ASC,$data):'';
            $sort ==12?array_multisort(array_column($data,'tab'),SORT_ASC,$data):'';
            $sort ==15?array_multisort(array_column($data,'p_time'),SORT_ASC,$data):'';


            $sort ==10?array_multisort(array_column($data,'last_cheat'),SORT_ASC,$data):'';
            $sort ==20?array_multisort(array_column($data,'remark'),SORT_ASC,$data):'';
            $sort ==30?array_multisort(array_column($data,'sum_gold'),SORT_ASC,$data):'';
            $sort ==40?array_multisort(array_column($data,'insure_gold'),SORT_ASC,$data):'';
            $sort ==50?array_multisort(array_column($data,'log_win'),SORT_ASC,$data):'';
            $sort ==60?array_multisort(array_column($data,'today_win'),SORT_ASC,$data):'';
            $sort ==70?array_multisort(array_column($data,'r_all'),SORT_DESC,$data):'';
            $sort ==80?array_multisort(array_column($data,'p_all'),SORT_DESC,$data):'';
            $sort ==90?array_multisort(array_column($data,'r_sum'),SORT_DESC,$data):'';
            $sort ==111?array_multisort(array_column($data,'p_sum'),SORT_DESC,$data):'';
            $sort ==112?array_multisort(array_column($data,'tab'),SORT_DESC,$data):'';
            $sort ==115?array_multisort(array_column($data,'p_time'),SORT_DESC,$data):'';


            $sort==0;
        }

		$result['data'] = $data;
		$result['date'] = $date;
        $result['title'] = $title;


		return $result;
		
	}
	
	
    //玩家列表、详情
    public function data1($type='player_list',$map,$sort=1,$isDetail = false,$go_remark,$map_buy,$map_win,$page_count){

        $m = M('AccountsInfo');
        $m2 = M('GameScoreLocker','','RDDB_TREASURE');
        $m3 = M('GameRoomItem','','RDDB_SERVER');


        //$sum_count = $m2->count();

//        $map_vip['t1.usertype'] = 4;
//        $map_player['t1.usertype'] = 0;
//
//        $vip_count = $m->field('t2.UserID')
//            ->table('AccountsInfo t1,QPTreasureDB.dbo.GameScoreLocker t2')
//            ->where($map_vip)
//            ->where('t1.UserID = t2.UserID')
//            ->count();
//
//        $player_count = $m->field('t2.UserID')
//            ->table('AccountsInfo t1,QPTreasureDB.dbo.GameScoreLocker t2')
//            ->where($map_player)
//            ->where('t1.UserID = t2.UserID')
//            ->count();
//
//        $count['sum_count'] = $sum_count;
//        $count['vip_count'] = $vip_count;
//        $count['player_count'] = $player_count;


        if($type=='player_list'){

            if(!$m->where($map)->find()){
                return null;
            }

//            if($map_buy){
//                $map_buy['min']?$map_buy['min']=$map_buy['min']:$map_buy['min']=0;
//                $map_buy['max']?$map_buy['max']=$map_buy['max']:$map_buy['max']=0;
//                $buy= $this->get_sum_buy_uids(0-$map_buy['min'],0-$map_buy['max']);
//
//                foreach ($buy as $v){
//                    $buids[] = $v['userid2'];
//                }
//
//                //dump($buids);
//                if($buids){
//                    $map['UserID'] = array('in',$buids);
//                }else{
//                    $map['UserID'] = array('lt',0);
//                }
//
//            }
//
//            if($map_win){
//                $map_win['min']?$map_win['min']=$map_win['min']:$map_win['min']=0;
//                $map_win['max']?$map_win['max']=$map_win['max']:$map_win['max']=0;
//
//                $win = $this->get_sum_win_uids($map_win['min'],$map_win['max']);
//
//                foreach ($win as $v){
//                    $wuids[] = $v['userid'];
//                }
//
//                if($wuids){
//                    $map['UserID'] = array('in',$wuids);
//                }else{
//                    $map['UserID'] = array('lt',0);
//                }
//
//            }
                $_SESSION['plpage'] = $page_count;

                $count = $m->where($map)->count();
                $page = new \Think\Page($count,$page_count);
                $users = $m->where($map)
                    //->order('RegisterDate desc')
                    ->limit($page->firstRow.','.$page->listRows)
                    ->select();





        }elseif ($type=='online_list'){

            if($map['t2.ServerID'][0]=='like' && $map['t1.usertype']==4){
                return null;
            }

            if($map['t2.ServerID'][0]=='like' && $map['t1.usertype']==0){

                $map3['ServerID'] = $map['t2.ServerID'];

                  $res = $m2  ->where($map3)->select();

                if(!$res){
                    return null;
                }


            }

            $count_page  = $m->table('AccountsInfo t1,QPTreasureDB.dbo.GameScoreLocker t2')
                    ->where($map)
                    ->where('t1.UserID = t2.UserID')
                    ->count();
            $count['search_count'] = $count_page+80;
            $page = new \Think\Page($count_page,50);

            $users  = $m->field('t2.UserID,t2.ServerID,t2.Platform_Id,t1.GameID,t1.NickName,t1.RegisterDate,t1.LastLoginTime,t1.usertype,t1.FirstServerId,t1.EndCheatDate')
                ->table('AccountsInfo t1,QPTreasureDB.dbo.GameScoreLocker t2')
                ->where($map)
                ->where('t1.UserID = t2.UserID')
                ->limit($page->firstRow.','.$page->listRows)
                ->select();

        }elseif($type=='protect_list'){
            if(!$map['ProtectID']){
                $map['ProtectID'] = array('gt',0);
            }

            if(!$m->where($map)->find()){
                return null;
            }
            $count = $m->where($map)->count();
            $page = new \Think\Page($count,15);
            $users = $m->where($map)->field('UserID,GameID,NickName,RegisterIP,LastLogonIP,PhoneNum,GameLogonTimes,MachineSerial,LastLoginTime,RegisterDate,FirstServerId,usertype,LimitLogin,EndCheatDate,ProtectID')
                ->order('RegisterDate desc')->limit($page->firstRow.','.$page->listRows)->select();
        }

        if($isDetail){
            $m = M('AA_ban_deal_account');
        }
        //获取玩家ID
        foreach ($users as $k=>$v){
            $uids[] = $v['userid'];
        }

        //获取房间ID

//        if($type!='online_list'){
//            foreach ($users as $k=>$v){
//                $sids[] = $v['firstserverid'];
//            }
//            $first_room = $this->get_room($sids);
//        }



//        $today_win = $this->get_sum_win($uids);
//        $sum_gold = $this->get_sum_gold($uids);
//        $insure_gold = $this->get_sum_win($uids,false,true);
//        $received_sum = $this->get_received_presents($uids,'R');
//        $present_sum = $this->get_received_presents($uids,'P');
//
//        $received_p = $this->get_received_presents($uids,'R_P');
//        $present_p = $this->get_received_presents($uids,'P_P');
//
//        $received_v = $this->get_received_presents($uids,'R_V');
//        $present_v = $this->get_received_presents($uids,'P_V');
//        $log_win = $this->get_sum_win($uids,true);
//
//        //$current_room = $this->get_room($sids);
//
//        $remark = $this->get_remark($uids);
//
//        $cheat = $this->get_cheat_limit($uids);
//        $cheat_limit = $this->get_cheat_limit($uids,'limit');
//        $cheat_blood = $this->get_cheat_limit($uids,'blood');
//
//        $day3_win = $this->get_3day_win($uids);


//        foreach ($users as $k=>$v){
//            $rid = $m2->where('UserID='.$v['userid'])->getField('ServerID');
//            $roomname = $m3->where('ServerID='.$rid)->getField('RoomName');
//            $users[$k]['today_win'] = $today_win[$v['userid']]['day_income']?$today_win[$v['userid']]['day_income']:0;
//            $users[$k]['sum_gold'] = $sum_gold[$v['userid']]['amount'];
//            $users[$k]['insure_gold'] = $insure_gold[$v['userid']]['insurescore'];
//            $users[$k]['received_sum'] = $received_sum[$v['userid']]['total']?abs($received_sum[$v['userid']]['total']):0;
//            $users[$k]['present_sum'] = $present_sum[$v['userid']]['total']?abs($present_sum[$v['userid']]['total']):0;
//            $users[$k]['present_p'] = $present_p[$v['userid']]['total']?abs($present_p[$v['userid']]['total']):0;
//            $users[$k]['received_p'] = $received_p[$v['userid2']]['total']?abs($received_p[$v['userid2']]['total']):0;
//
//            $users[$k]['present_v'] = $present_v[$v['userid']]['total']?abs($present_v[$v['userid']]['total']):0;
//            $users[$k]['received_v'] = $received_v[$v['userid']]['total']?abs($received_v[$v['userid']]['total']):0;
//            $users[$k]['p_ab'] = $users[$k]['present_p'] - $users[$k]['received_p'];
//
//            $users[$k]['ab'] = $users[$k]['present_sum'] - $users[$k]['received_sum'];
//            $users[$k]['log_win'] = $log_win[$v['userid']]['realscore'];
//            if($type!='online_list'){
//                $users[$k]['first_room'] = $first_room[$v['firstserverid']]['roomname']?$first_room[$v['firstserverid']]['roomname']:'未选择房间';
//
//            }
//            $users[$k]['current_room'] = $roomname?$roomname:'大厅';
//
//            $users[$k]['remark'] = $remark[$v['userid']]['remark'];
//
//            $users[$k]['cheat'] = $cheat[$v['userid']]['cheatrate'];
//            $users[$k]['limit'] = $cheat_limit[$v['userid']]['limitscore'];
//            $users[$k]['blood'] = $cheat_blood[$v['userid']]['bloodscore'];
//            $users[$k]['day3_win'] = $day3_win[$v['userid']]['total'];
//
//            if( $users[$k]['cheat']>0){
//                $c_limit = 0 - $users[$k]['limit'];
//            }else{
//                $c_limit = $users[$k]['limit'];
//            }
//
//            $users[$k]['sp'] = abs($c_limit - $users[$k]['blood']);
//
//            if($type=='online_list'){
//                $users[$k]['buy_id'] = $this->get_buy_log($v['userid'],'buy_id');
//                $users[$k]['buy_name'] = $this->get_buy_log($v['userid'],'buy_name');
//                $users[$k]['buy_remark'] = $this->get_buy_log($v['userid'],'buy_remark');
//                $users[$k]['buy_gold'] = abs($this->get_buy_log($v['userid'],'buy_gold'));
//            }
//
//
//            if($isDetail){
//               // $users[$k]['reg_city'] = $this->getCity($users[$k]['registerip']);
//               // $users[$k]['log_city'] = $this->getCity($users[$k]['lastlogonip']);
//                $users[$k]['isDeal'] = $m->where('user_id ='.$v['userid'])->find()?1:0;
//
//            }
//
//        }

//        if($type=='player_list' || $type=='protect_list' ){
//            $result['show'] = $page->show();
//            $sort ==1?array_multisort(array_column($users,'registerdate'),SORT_DESC,$users):'';
//            $sort ==2?array_multisort(array_column($users,'today_win'),SORT_DESC,$users):'';
//            $sort ==3?array_multisort(array_column($users,'log_win'),SORT_DESC,$users):'';
//
//        }else{
//            $result['show'] = $page->show();
//            $sort ==1?array_multisort(array_column($users,'sum_gold'),SORT_DESC,$users):'';
//            $sort ==2?array_multisort(array_column($users,'insure_gold'),SORT_DESC,$users):'';
//            $sort ==3?array_multisort(array_column($users,'present_sum'),SORT_DESC,$users):'';
//            $sort ==4?array_multisort(array_column($users,'received_sum'),SORT_DESC,$users):'';
//            $sort ==5?array_multisort(array_column($users,'buy_gold'),SORT_DESC,$users):'';
//
//
//        }



//        if($go_remark){
//
//            $arr = array();
//            foreach($users as $k=>$v ){
//
//                if (strstr( $users[$k]['remark'] , $go_remark )){
//                    array_push($arr, $users[$k]);
//                }
//            }
//
//            $users = $arr;
//        }
        //dump($sort);
        $result['data'] = $users;
        $result['count'] = $count;
        return $result;

    }




    //玩家列表、详情
    public function data2($map,$sort=1,$go_remark){

        $m = M('AccountsInfo');
        $m2 = M('GameScoreLocker','','RDDB_TREASURE');
        $m3 = M('GameRoomItem','','RDDB_SERVER');

//        $m = M('AccountsInfo');
//        $m2 = M('GameScoreLocker','','DB_TREASURE');
//        $m3 = M('GameRoomItem','','DB_SERVER');

        $smap['ServerID'] = array('neq',0);
        $smap1['t1.ServerID'] = array('neq',0);

        $sum_count = $m2->count();

        $map_vip['t1.usertype'] = 4;
        $map_player['t1.usertype'] = 0;

        $vip_count = $m->field('t2.UserID')
            ->table('AccountsInfo t1,QPTreasureDB.dbo.GameScoreLocker t2')
            ->where($map_vip)
            ->where('t1.UserID = t2.UserID')

           // ->where('ServerID!=0')
            ->count();

        $player_count = $m->field('t2.UserID')
            ->table('AccountsInfo t1,QPTreasureDB.dbo.GameScoreLocker t2')
            ->where($map_player)
            ->where('t1.UserID = t2.UserID')

            //->where('ServerID!=0')
            ->count();

        $count['sum_count'] = $sum_count;
        $count['vip_count'] = $vip_count;
        $count['player_count'] = $player_count;


            if($map['t2.ServerID'][0]=='like' && $map['t1.usertype']==4){
                return null;
            }

            if($map['t2.ServerID'][0]=='like' && $map['t1.usertype']==0){

                $map3['ServerID'] = $map['t2.ServerID'];
				
                $res = $m2  ->where($map3)->select();


				$map11['ServerID'] = array('neq',0);
				
				$yes_count = $m2->where($map11)->count();
				
                if(!$res){
                    return null;
                }

            }else{
                $map['t2.ServerID'] = array('neq',0);
				
				
            }

            $count_page  = $m->table('AccountsInfo t1,QPTreasureDB.dbo.GameScoreLocker t2')
                ->where($map)
                ->where('t1.UserID = t2.UserID')

                //->where('ServerID!=0')
                ->count();
            $count['search_count'] = $count_page;
			if($yes_count){
				$count['hall_count'] = $sum_count - $yes_count;	
			}else{
				$count['hall_count'] = $sum_count - $count_page;	
			}
			
            $page = new \Think\Page($count_page,53);

            $users  = $m->field('t2.UserID,t2.ServerID,t1.usertype')
                ->table('AccountsInfo t1,QPTreasureDB.dbo.GameScoreLocker t2')
                ->where($map)
                ->where('t1.UserID = t2.UserID')

                ->limit($page->firstRow.','.$page->listRows)
                ->select();
		//dump($users);exit;

        //获取玩家ID
        foreach ($users as $k=>$v){
            $uids[] = $v['userid'];
        }

        //获取房间ID

        $today_win = $this->get_sum_win($uids);
        $sum_gold = $this->get_sum_gold($uids);
        $insure_gold = $this->get_sum_win($uids,false,true);
        $log_win = $this->get_sum_win($uids,true);
        //$current_room = $this->get_room($sids);
        $remark = $this->get_remark($uids);
        $cheat = $this->get_cheat_limit($uids);
		$online_sid = $this->get_online_serverid($uids);

		//dump($users);exit;

        foreach ($users as $k=>$v){
            //$rid = $m2->where('UserID='.$v['userid'])->getField('ServerID');

            $users[$k]['serverid'] = $online_sid[$v['userid']]['serverid'];
            $roomname = $m3->where('ServerID='.$users[$k]['serverid'])->getField('RoomName');
			
			//dump($rid);
            $users[$k]['today_win'] = $today_win[$v['userid']]['day_income']?$today_win[$v['userid']]['day_income']:0;
            $users[$k]['sum_gold'] = $sum_gold[$v['userid']]['amount'];
            $users[$k]['insure_gold'] = $insure_gold[$v['userid']]['insurescore'];

            $users[$k]['log_win'] = $log_win[$v['userid']]['realscore'];
            $users[$k]['current_room'] = $roomname?$roomname:'大厅';
			//$users[$k]['current_room'] = $online_sid[$v['userid']]['serverid'];;
			
            $users[$k]['remark'] = $remark[$v['userid']]['remark'];
            $users[$k]['cheat'] = $cheat[$v['userid']]['cheatrate'];

                $users[$k]['buy_id'] = $this->get_buy_log($v['userid'],'buy_id');
                //$users[$k]['buy_name'] = $this->get_buy_log($v['userid'],'buy_name');
                $users[$k]['buy_remark'] = $this->get_buy_log($v['userid'],'buy_remark');
                $users[$k]['buy_gold'] = abs($this->get_buy_log($v['userid'],'buy_gold'));
        }


            $result['show'] = $page->show();
            $sort ==1?array_multisort(array_column($users,'sum_gold'),SORT_DESC,$users):'';
            $sort ==2?array_multisort(array_column($users,'insure_gold'),SORT_DESC,$users):'';

            $sort ==5?array_multisort(array_column($users,'buy_gold'),SORT_DESC,$users):'';


        if($go_remark){

            $arr = array();
            foreach($users as $k=>$v ){

                if (strstr( $users[$k]['remark'] , $go_remark )){
                    array_push($arr, $users[$k]);
                }
            }

            $users = $arr;
        }

        $result['data'] = $users;
        $result['count'] = $count;
        return $result;

    }

    //获取3日输赢
    public function get_3day_win(array $uids = array()){


        $m = M('AA_ZZ_Log_PropChange','','RDDB_USER');

        $map['LogTime'] = array('between',date("Y-m-d",strtotime("-3 day")).','.date("Y-m-d",strtotime("+1 day")));
        $map['User_Id'] =array('in',$uids);
        $map['ChangeType_Id'] =1;
        $map['Prop_Id'] =1;

        $data = $m->where($map)->field('User_Id,sum(Amount) as total')
            ->group('User_Id')
            ->select();


        $data = $this->changeKeys($data, 'user_id');

        return $data;

    }

    public function get_sum_win_uids($min,$max){
        $m = M('GameScoreInfo','','RDDB_TREASURE');
        $map['RealScore'] = array('between',$min.','.$max);

       // dump($map);
        $data = $m->where($map)->field('UserID')->select();
        return $data;

    }

    /**
     * 获取指定玩家当日输赢/保险柜金币/总输赢
     * 参数（数组玩家ID,是否3日总输赢，起止时间，默认查询玩家当日输赢）
     */
    public function get_sum_win(array $uids = array(),$isLogWin = false,$isInsure=false,$isThreeDay = false,$stime='',$etime=''){

        $today = date('Y-m-d');
        $tomorrow = date("Y-m-d",strtotime("+1 day"));

        $m = M('GameScoreInfo','','RDDB_TREASURE');

        $map['UserID']=array('in',$uids);
        $map1['UserID']=array('in',$uids);

        if($isThreeDay){
            $map['day_date'] = array('between',date("Y-m-d",strtotime("-3 day")).','.$tomorrow);
        }elseif ($stime!='' && $etime!=''){
            $map['day_date'] = array('between',$stime.','.$etime);
        }else{
            $map['day_date'] = array('between',$today.','.$tomorrow);
        }

        if($isInsure){
            $log_data = $m->where($map1)->field('UserID,InsureScore')->select();
        }else{
            if($isLogWin){
                $log_data = $m->where($map1)->field('UserID,RealScore')->select();
            }else{
                $log_data = $m->where($map)->field('UserID,day_income')->select();
            }
        }


        $log_data = $this->changeKeys($log_data, 'userid');

        return $log_data;

    }
    /**
     * 获取指定玩家总金币
     * 参数（数组玩家ID）
     */
    public function get_sum_gold(array $uids = array()){
        $m = M('AA_Shop_Prop_UserProp','','RDDB_USER');

        $map['User_Id']=array('in',$uids);
        $map['Prop_Id'] = '1';

        $log_data = $m->where($map)->field('User_Id,Amount')->order('Amount')->select();

        $log_data = $this->changeKeys($log_data, 'user_id');

        return $log_data;

    }

    /**
     * 获取指定玩家最后兑换时间
     * 参数（数组玩家ID）
     */
    public function get_exchange_field(array $uids = array(),$filed=''){
        $m = M('NExchangeCardList','','DB_TREASURE');

        $map['ExchangeUserID']=array('in',$uids);

        if($filed=='ex_date'){
            $log_data = $m->where($map)->field('ExchangeUserID,max(ExchangeDate) as exdate')->group('ExchangeUserID')->select();

        }elseif ($filed=='ex_count'){
            $log_data = $m->where($map)->field('ExchangeUserID,count(ExchangeUserID) as total')->group('ExchangeUserID')->select();
        }

        $log_data = $this->changeKeys($log_data, 'exchangeuserid');

        return $log_data;

    }

    /**
     * 获取指定玩家作弊限额或作弊率
     * 参数（数组玩家ID）
     */
    public function get_cheat_limit(array $uids = array(),$field='cheat'){
        $m = M('GameScoreInfo','','DB_TREASURE');

        $map['UserID']=array('in',$uids);


        if($field == 'cheat'){
            $log_data = $m->where($map)->field('UserID,CheatRate')->select();
        }elseif ($field == 'limit'){
            $log_data = $m->where($map)->field('UserID,LimitScore')->select();
        }elseif ($field == 'blood'){
            $log_data = $m->where($map)->field('UserID,BloodScore')->select();
        }

        $log_data = $this->changeKeys($log_data, 'userid');

        return $log_data;

    }



    /**
     * 获取指定玩家备注
     * 参数（数组玩家ID）
     */
    public function get_remark(array $uids = array()){
        $m = M('AccountsInfoExtend','','RDDB_USER');

        $map['UserID']=array('in',$uids);

        $log_data = $m->where($map)->field('UserID,Remark')->select();

        $log_data = $this->changeKeys($log_data, 'userid');
        return $log_data;
    }
	
	/**
     * 获取指定玩家在线房间ID
     * 参数（数组玩家ID）
     */
    public function get_online_serverid(array $uids = array()){
        $m = M('GameScoreLocker','','RDDB_TREASURE');
        $map['UserID']=array('in',$uids);
        $log_data = $m->where($map)->field('UserID,ServerID')->select();
        $log_data = $this->changeKeys($log_data, 'userid');
        return $log_data;
    }

    /**
     * 获取指定玩家最后作弊时间
     * 参数（数组玩家ID）
     */
    public function get_last_cheat(array $uids = array()){
        $m = M('AccountsInfo','','RDDB_USER');

        $map['UserID']=array('in',$uids);

        $log_data = $m->where($map)->field('UserID,endcheatdate')->select();

        $log_data = $this->changeKeys($log_data, 'userid');
        return $log_data;
    }


    /**
     * 获取指定玩家注册时间
     * 参数（数组玩家ID）
     */
    public function get_reg_date(array $uids = array()){
        $m = M('AccountsInfo');

        $map['UserID']=array('in',$uids);

        $log_data = $m->where($map)->field('UserID,registerdate')->select();

        $log_data = $this->changeKeys($log_data, 'userid');
        return $log_data;
    }


    /**
     * 获取指定玩家首选游戏
     * 参数（数组房间ID）
     */
    public function get_room(array $sids = array()){
        $m = M('GameRoomItem','','DB_SERVER');

        $map['ServerID']=array('in',$sids);

        $log_data = $m->where($map)->field('ServerID,RoomName')->select();

        $log_data = $this->changeKeys($log_data, 'serverid');

        return $log_data;

    }

    /**
     * 获取指定玩家昵称
     * 参数（数组玩家ID）
     */
    public function get_nick(array $uids = array()){
        $m = M('AccountsInfo');

        $map['UserID']=array('in',$uids);

        $log_data = $m->where($map)->field('UserID,NickName')->select();

        $log_data = $this->changeKeys($log_data, 'userid');

        return $log_data;

    }

    public function get_sum_buy_uids($min,$max){
        $m1 =  M('ScoreChangeDetail','','RDDB_RECOED');
        $m2 = M('AccountsInfo');

        $vip = $m2->where('usertype='.'4')->select();
        $vips = array();
        foreach ($vip as $v){
            $vips[] = $v['userid'];
        }

        $map['UserID'] =  array('in',$vips);
        if($min==$max && $min!=0 && $max!=0){
            $data = $m1->where($map)->where('UserID!=UserId2')
                ->field('sum(Score) as total,UserId2')
                ->group('UserId2')
                ->having('sum(Score)='.$max)
                ->select();
        }else{
            $data = $m1->where($map)->where('UserID!=UserId2')
                ->field('sum(Score) as total,UserId2')
                ->group('UserId2')
                ->having('sum(Score)>'.$max.' and sum(Score)<'.$min)
                ->select();
        }


        return $data;
    }

    /**
     * 获取指定玩家接收/赠送，总数或每条
     * 参数（数组玩家ID，是否接收，是否为每条，默认查询总数）
     */
    public function get_received_presents(array $uids = array(),$datatype = 'R',$isEach = false,array $map = array(),$isCount=false,$isToday=false){
        $m1 =  M('ScoreChangeDetail','','RDDB_RECOED');
        $m2 = M('AccountsInfo');

        $today = date('Y-m-d');
        $tomorrow = date("Y-m-d",strtotime("+1 day"));

        if($isToday){
            $map['ChangeDate'] = array('between',$today.','.$tomorrow);
        }

        $vip = $m2->where('usertype='.'4')->select();

        $vips = array();

        foreach ($vip as $v){
            $vips[] = $v['userid'];
        }

        if($isEach){
            if($datatype=='R'){
                $map['UserId2']=$uids[0];
            }elseif($datatype=='P'){
                $map['UserID']=$uids[0];
            }elseif ($datatype=='v2v'){
                $map['UserID'] = array('in',$vips);
                $map['UserId2'] = array('in',$vips);
            }elseif($datatype=='p2p'){
                $map['UserID'] = array('not in',$vips);
                $map['UserId2'] = array('not in',$vips);
            }elseif($datatype=='p2v'){
                $map['UserID'] = array('not in',$vips);
                $map['UserId2'] = array('in',$vips);
            }elseif($datatype=='v2p'){
                $map['UserID'] = array('in',$vips);
                $map['UserId2'] = array('not in',$vips);
            }

            //dump($map);

            $count = $m1->where($map)->where('UserID != UserId2')->count();

            //dump($count);

            $page = new \Think\Page($count,15);
            $show = $page->show();

            $data = $m1->where($map)->where('UserID != UserId2')->field('UserID,Score,UserId2,ChangeDate,nIndex')
                ->order('ChangeDate desc')->limit($page->firstRow.','.$page->listRows)->select();

            if($data){
                $result['show'] = $show;
                $result['data'] = $data;

                return $result;
            }else{
                return null;
            }


        }else{
            if($datatype=='R'){
                $map['UserId2']=array('in',$uids);
                if($isCount){
                    $log_data = $m1->where($map)->where('UserID != UserId2')->field('UserId2,count(UserId2) as total')->group('UserId2')->order('UserId2')->select();
                }else{
                    $log_data = $m1->where($map)->where('UserID != UserId2')->field('UserId2,sum(Score) as total')->group('UserId2')->order('UserId2')->select();
                }


                $log_data = $this->changeKeys($log_data, 'userid2');
            }elseif($datatype=='P'){
                $map['UserID']=array('in',$uids);

                if($isCount){
                    $log_data = $m1->where($map)->where('UserID != UserId2')->field('UserID,count(UserID) as total')->group('UserID')->order('UserID')->select();
                }else{
                    $log_data = $m1->where($map)->where('UserID != UserId2')->field('UserID,sum(Score) as total,max(ChangeDate) as time')->group('UserID')->order('UserID')->select();
                }

                $log_data = $this->changeKeys($log_data, 'userid');
            }elseif($datatype=='R_P'){
                $map['UserId2']=array('in',$uids);
                $map['UserID']=array('not in',$vips);


                if($isCount){
                    $log_data = $m1->where($map)->where('UserID != UserId2')->field('UserId2,count(UserId2) as total')->group('UserId2')->order('UserId2')->select();
                }else{
                    $log_data = $m1->where($map)->where('UserID != UserId2')->field('UserId2,sum(Score) as total')->group('UserId2')->order('UserId2')->select();
                }
                //dump($log_data);

                $log_data = $this->changeKeys($log_data, 'userid');
            }elseif($datatype=='P_P'){
                $map['UserID']=array('in',$uids);
                $map['UserId2']=array('not in',$vips);

                if($isCount){
                    $log_data = $m1->where($map)->where('UserID != UserId2')->field('UserID,count(UserID) as total')->group('UserID')->order('UserID')->select();
                }else{
                    $log_data = $m1->where($map)->where('UserID != UserId2')->field('UserID,sum(Score) as total')->group('UserID')->order('UserID')->select();
                }

                $log_data = $this->changeKeys($log_data, 'userid');
            }elseif($datatype=='R_V'){
                $map['UserId2']=array('in',$uids);
                $map['UserID']=array('in',$vips);

                if($isCount){
                    $log_data = $m1->where($map)->where('UserID != UserId2')->field('UserID,count(UserID) as total')->group('UserID')->order('UserID')->select();
                }else{
                    $log_data = $m1->where($map)->where('UserID != UserId2')->field('UserID,sum(Score) as total')->group('UserID')->order('UserID')->select();
                }

                $log_data = $this->changeKeys($log_data, 'userid');
            }elseif($datatype=='P_V'){
                $map['UserID']=array('in',$uids);
                $map['UserId2']=array('in',$vips);

                if($isCount){
                    $log_data = $m1->where($map)->where('UserID != UserId2')->field('UserID,count(UserID) as total')->group('UserID')->order('UserID')->select();
                }else{
                    $log_data = $m1->where($map)->where('UserID != UserId2')->field('UserID,sum(Score) as total')->group('UserID')->order('UserID')->select();
                }

                $log_data = $this->changeKeys($log_data, 'userid');
            }else{

                $log_data = $m1->where($map)->where('UserID != UserId2')->order('ChangeDate desc')->select();


            }
        }

        return $log_data;
    }

    //获取玩家最近交易时间及总额
    public function get_deal_field(array $uids = array(),$deal_type='',$times=0){
        $m =  M('ScoreChangeDetail','','DB_RECOED');
        $m2 = M('AccountsInfo');

        $vip = $m2->where('usertype=4')->select();

        $vips = array();

        foreach ($vip as $v){
            $vips[] = $v['userid'];
        }

        if($deal_type=='R'){
            $map['UserID'] = array('in',$vips);
            $map['UserId2'] = array('in',$uids);

            $log_data = $m->where($map)->where('UserID != UserId2')->field('UserId2,ChangeDate,Score')->order('ChangeDate desc')->limit(3)->select();

            if($times==1){
                $log_data[0]?$log_data=array($log_data[0]):$log_data=array();
            }
            if($times==2){
                $log_data[1]?$log_data=array($log_data[1]):$log_data=array();
            }
            if($times==3){
                $log_data[2]?$log_data=array($log_data[2]):$log_data=array();
            }

            $log_data = $this->changeKeys($log_data, 'userid2');

        }elseif ($deal_type=='P'){
            $map['UserId2'] = array('in',$vips);
            $map['UserID'] = array('in',$uids);

            $log_data = $m->where($map)->where('UserID != UserId2')->field('UserId2,ChangeDate,Score')->order('ChangeDate desc')->limit(3)->select();

            if($times==1){
                $log_data[0]?$log_data=array($log_data[0]):$log_data=array();
            }
            if($times==2){
                $log_data[1]?$log_data=array($log_data[1]):$log_data=array();
            }
            if($times==3){
                $log_data[2]?$log_data=array($log_data[2]):$log_data=array();
            }

            $log_data = $this->changeKeys($log_data, 'userid');
        }




        return $log_data;
    }

    //获取登录数据

    public function login_data(array $users = array()){
        //获取玩家ID
        foreach ($users as $k=>$v){
            $uids[] = $v['userid'];
        }

        //获取房间ID
        foreach ($users as $k=>$v){
            $sids[] = $v['serverid'];
        }

        $room_name = $this->get_room($sids);
        $nick_name = $this->get_nick($uids);

        foreach ($users as $k=>$v){
            $users[$k]['room_name'] = $room_name[$v['serverid']]['roomname'];
            $users[$k]['nick_name'] = $nick_name[$v['userid']]['nickname'];

        }

        return $users;
    }

    //赠送接收拓展数据

    public function received_presents_data(array $users = array()){
        //获取玩家ID

        if($users==null){
            return $users;
        }

        foreach ($users as $k=>$v){
            $p_uids[] = $v['userid'];
            $r_uids[] = $v['userid2'];
        }

        $p_nick_name = $this->get_nick($p_uids);
        $r_nick_name = $this->get_nick($r_uids);

        $p_gid = $this->get_gameID($p_uids);
        $r_gid = $this->get_gameID($r_uids);


        foreach ($users as $k=>$v){
            $users[$k]['p_nick_name'] = $p_nick_name[$v['userid']]['nickname'];
            $users[$k]['r_nick_name'] = $r_nick_name[$v['userid2']]['nickname'];

            $users[$k]['p_gid'] = $p_gid[$v['userid']]['gameid'];
            $users[$k]['r_gid'] = $r_gid[$v['userid2']]['gameid'];

        }

        return $users;

    }

    //获取GameID
    public  function get_gameID(array $uids = array()){
        $m = M('AccountsInfo');
        $map['UserID'] = array('in',$uids);

        $log_data = $m->where($map)->field('UserID,GameID')->select();

        $log_data = $this->changeKeys($log_data, 'userid');

        return $log_data;

    }


    //金币变动数据

    public function gold_change_data($uid='',$stime='',$etime=''){

        $m = M('AA_ZZ_Log_PropChange');
        $map['User_Id'] = $uid;


        $count = $m->where($map)->count();

        $page = new \Think\Page($count,15);
        $show = $page->show();

        $data = $m->where($map)->field('User_Id,PreAmount,Amount,AftAmount,ServerId,LogTime,ChangeType_Id')
            ->order('LogTime desc')->limit($page->firstRow.','.$page->listRows)->select();

        if(!$data){
            return null;
        }

        foreach ($data as $k=>$v){
            $tids[] = $v['changetype_id'];
            $sids[] = $v['serverid'];
            $uids[] = $v['user_id'];
        }



        $reason = $this->get_change_type($tids);
        $roomname = $this->get_room($sids);
        $nickname = $this->get_nick($uids);


        foreach ($data as $k=>$v){
            $data[$k]['gameid'] = $v['User_Id']+10000;
            $data[$k]['reason'] = $reason[$v['changetype_id']]['remark']?$reason[$v['changetype_id']]['remark']:'未知';
            $data[$k]['roomname'] = $roomname[$v['serverid']]['roomname']?$roomname[$v['serverid']]['roomname']:'--';
            $data[$k]['nickname'] = $nickname[$v['user_id']]['nickname']?$nickname[$v['user_id']]['nickname']:'--';
        }


            $result['show'] = $show;
            $result['data'] = $data;

            return $result;


    }

    //获取变动原因
    public function get_change_type(array $tids = array()){
        $m = M('AA_ZZ_Log_PropChange_ChangeType');

        $map['ChangeType_Id']=array('in',$tids);

        $log_data = $m->where($map)->field('ChangeType_Id,Remark')->select();

        $log_data = $this->changeKeys($log_data, 'changetype_id');

        return $log_data;
    }

    //添加金币操作
    public function add_gold($uid='',$gold=0,$remark,$pid=1){

        $m1 = M('AA_Shop_Prop_UserProp');
        $m2 = M('AA_ZZ_Log_PropChange');
        $m3 = M('Web_DoLog');
        $m4 = M('WebOpLog');


        $map['User_Id'] = $uid;
        $map['Prop_Id'] = $pid;
        $pre_gold = $m1->where($map)->getField('Amount');
		
		

        $aft_gold = $pre_gold + $gold;

        $save['Amount'] = $aft_gold;

        $result = $m1->where($map)->save($save);

        if($result){
            $data['User_Id'] = $uid;
            $data['Prop_Id'] = $pid;
            $data['PreAmount'] = $pre_gold;
            $data['Amount'] = $gold;
            $data['AftAmount'] = $aft_gold;
            $data['Remark'] = $remark;
            $data['KindId'] = 24;
            $data['ServerId'] = 0;
            $data['TableId'] = 0;
            $data['No'] = 0;
            $data['ChangeType_Id'] = 2;

            $m2->add($data);

            $data1['logName'] = $_SESSION['admin'];
            $data1['doIP'] = 0;
            $data1['res'] = '成功';
            $data1['remark'] = '为【'.$uid.'】增加【'.$gold.'】金币';
            $data1['logType'] = 2;

            $web['UserId'] = $uid;
            if(!$m4->where('UserId='.$web['UserId'])->select()){
                $m4->add($web);
            }
            return $m3->add($data1);
        }

        return null;


    }

    //执行点控
    public function do_ctr($uid,$cheat,$limit){
        $m1 = M('GameScoreInfo','','DB_TREASURE');
        $m2 = M('WEB_ControllerUser','','DB_TREASURE');
        $m3 = M('AccountsInfo');

        $map['UserID'] = $uid;

        $pre_cheat = $m1->where($map)->getField('CheatRate');
        $pre_limit = $m1->where($map)->getField('LimitScore');
        $pre_blood = $m1->where($map)->getField('BloodScore');

        $data1['UserID'] = $uid;
        $data1['NickName'] = $m3->where($map)->getField('NickName');
        $data1['Admin'] = $_SESSION['admin'];

        $data1['OldCheat'] = $pre_cheat;
        $data1['BloodScore'] = $pre_blood;
        $data1['Cheat'] = $cheat;
        $data1['LimitScore'] = $limit;
        $data1['Remark'] = "修改【".$uid."】作弊率，作弊率限额由【".$pre_limit."】修改为【".$limit."】";

        $m2->add($data1);

        $data['CheatRate'] = $cheat;
        $data['LimitScore'] = $limit;
        $data['BloodScore'] = 0;

        $result = $m1->where($map)->save($data);

        return $result;


    }

    public function player_ctr_log($uid='',$admin=''){
        $m = M('WEB_ControllerUser','','DB_TREASURE');
       // dump($uid);
        if($uid!=null){
            $map['UserID'] =  $uid;
        }elseif ($admin!=null){
            $map['Admin'] =  $admin;
        }else{
            $map['UserID'] =  array('gt','10000');
        }

        $count = $m->where($map)->count();

        $page = new \Think\Page($count,15);
        $show = $page->show();

        $data = $m->where($map)->order('CtrDate desc')
            ->limit($page->firstRow.','.$page->listRows)->select();

        if($data){
            $result['show'] = $show;
            $result['data'] = $data;

            return $result;
        }else{
            return null;
        }
    }


    public function admin_do_log($typeid,$uid='',$admin='',$roomname=''){

        $m = M('Web_DoLog');
        $map['logType'] = $typeid;

        if($uid!=''){
            $map['remark'] = array('like','%'.$uid.'%');
        }

        if($admin!=''){
            $map['logName'] = $admin;
        }

        if($roomname!=''){
            $map['remark'] = array('like','%'.$roomname.'%');
        }


        $count = $m->where($map)->count();

        $page = new \Think\Page($count,15);
        $show = $page->show();

        $data = $m->where($map)->order('doDate desc')
            ->limit($page->firstRow.','.$page->listRows)->select();

        if($data){
            $result['show'] = $show;
            $result['data'] = $data;

            return $result;
        }else{
            return null;
        }
    }




    function getCity($ip = '')
    {
        if($ip == ''){
            $url = "http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json";
            $ip=json_decode(file_get_contents($url),true);
            $data = $ip;
        }else{
            $url="http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
            $ip=json_decode(file_get_contents($url));
            if((string)$ip->code=='1'){
                return false;
            }
            $data = (array)$ip->data;
        }

        return $data;
    }


    //锁定/解锁玩家    logType = 3
    public function do_lock($uid,$lock,$remark){

        $m1 = M('AccountsInfo');
        $m2 = M('Web_DoLog');
        $m4 = M('WebOpLog');

        $map['UserID'] = $uid;
        $save['LimitLogin'] = $lock;
        $result = $m1->where($map)->save($save);



        if($result){
            $web['UserId'] = $uid;
            if(!$m4->where('UserId='.$web['UserId'])->select()){
                $m4->add($web);
            }
            $data['logName'] = $_SESSION['admin'];
            $data['doIP'] = 0;
            $data['res'] = '成功';
            $data['logType'] = 3;

            if($lock){
                $data['remark'] = '锁定玩家【'.$uid.'】- 备注信息：'.$remark;
                return $m2->add($data);

            }else{
                $data['remark'] = '解锁玩家【'.$uid.'】- 备注信息：'.$remark;
                return $m2->add($data);
            }

        }else{
            return null;
        }

    }

    //禁止/解除交易   logType = 4
    public function do_ban_deal($uid,$tid=1,$rmk){
        $m = M('AA_ban_deal_account');
        $m2 =  M('Web_DoLog');
        $map['user_id'] = $uid;
        $result = $m->where($map)->find();


        $data['logName'] = $_SESSION['admin'];
        $data['doIP'] = 0;
        $data['res'] = '成功';
        $data['logType'] = 4;

        if($tid == 1){
            if($result){
                return "已禁止";
            }else{
                $data['remark'] = '禁止玩家【'.$uid.'】交易- 备注信息：'.$rmk;
                $m2->add($data);
                return $m->add($map);
            }
        }else{
            if($result){
                $data['remark'] = '允许玩家【'.$uid.'】交易- 备注信息：'.$rmk;
                $m2->add($data);
                return $m->where($map)->delete();
            }else{
                return "已解除";
            }

        }

    }

    //修改玩家类型   logType = 5

    public function do_change_type($uid,$usertype,$rmk){
        $m1 = M('AccountsInfo');
        $m2 = M('Web_DoLog');
        $m4 = M('WebOpLog');

        $map['UserID'] = $uid;

        $save['usertype'] = $usertype;

        $usertype == 4 ? $type='VIP' : $type = '普通玩家';

        $result = $m1->where($map)->save($save);

        $data['logName'] = $_SESSION['admin'];
        $data['doIP'] = 0;
        $data['res'] = '成功';
        $data['logType'] = 5;
        $data['remark'] = '修改玩家【'.$uid.'】类型为【'.$type.'】- 备注信息：'.$rmk;
        if($result){
            $web['UserId'] = $uid;
            if(!$m4->where('UserId='.$web['UserId'])->select()){
                $m4->add($web);
            }
            return $m2->add($data);
        }else{
            return "已修改";
        }

    }

    //修改玩家类型   logType = 15

    public function do_card_log($num,$str,$val){

        $m2 = M('Web_DoLog');
        $data['logName'] = $_SESSION['admin'];
        $data['doIP'] = 0;
        $data['res'] = '成功';
        $data['logType'] = 15;
        $data['remark'] = '生成【'.$str.'】【'.$num.'】张，面值【'.$val.'】';
        return $m2->add($data);

    }


 //修改玩家类型   logType =11

    public function do_roll_back_log($uid,$uid2,$gold,$time){
      
        $m2 = M('Web_DoLog');
		
		$date = date("Y-m-d H:i:s");;

        $data['logName'] = $_SESSION['admin'];
        $data['doIP'] = 0;
        $data['res'] = '成功';
        $data['logType'] = 11;
        $data['remark'] = '赠送ID【'.$uid.'】，接收ID【'.$uid2.'】交易已撤回，撤回金额：'.$gold;
        return $m2->add($data);
  

    }


    //修改玩家备注

    public function do_remark($uid,$rmk){
        $m1 = M('AccountsInfoExtend');
        $map['UserID'] = $uid;

        if($m1->where($map)->find()){
            $save['Remark'] = $rmk;
            return $m1->where($map)->save($save);
        }else{
            $map['Remark'] = $rmk;
            return $m1->add($map);
        }

    }


    //添加奖池金币  logType = 6

    public function do_pool_gold($uid,$roomid,$gold){
        $m = M('NewShuiGuoLaBaPrizePoolAward','','DB_TREASURE');
        $m2 = M('Web_DoLog');
        $m3 = M('GameRoomItem','','DB_SERVER');

        $data['UserID'] = $uid;
        $data['ServerID'] = $roomid;
        $data['Gold'] = $gold;
        $data['IsGet'] = 0;
        $data['AdminID'] =$_SESSION['adminid'];


        $map['UserID'] = $uid;
        $map['ServerID'] = $roomid;


        $isset = $m->where($map)->find();


dump($isset);

        if($isset){
            unset($data['UserID']);
            $result = $m->where($map)->save($data);
            dump($result);
        }else{
            $result = $m->add($data);
        }




        if($result){
            $map['ServerID'] = $roomid;
            $roomname = $m3->where($map)->getField('RoomName');

            $data1['logName'] = $_SESSION['admin'];
            $data1['doIP'] = 0;
            $data1['res'] = '成功';
            $data1['logType'] = 6;
            $data1['remark'] = '为玩家【'.$uid.'】添加奖池金币【'.$gold.'】- 游戏房间：【'.$roomname.'】';
            return $m2->add($data1);

        }else{
            return null;
        }
    }

    public function do_pool_gold23($userid='',$serverid, $range,$num ){
        $m = M('NewShuiGuoLaBaPrizePoolAward','','DB_TREASURE');
        $out2 =array();
        $out3 = array();
        $new_out2 = array();
        $new_out3 = array();

        $out2_file = fopen("out2.txt","r");
        while(! feof($out2_file))
        {
            $out2[] = fgets($out2_file);
        }
        fclose($out2_file);

        $out3_file = fopen("out3.txt","r");
        while(! feof($out3_file))
        {
            $out3[] = fgets($out3_file);
        }
        fclose($out3_file);
        array_pop($out2);
        array_pop($out3);


        foreach ($out2 as $k=>$v){

            $temp = explode(',',$out2[$k]);
            $new_out2[$k]['gid'] = $temp[0];
            $new_out2[$k]['power'] = end($temp);
        }

        foreach ($out3 as $k=>$v){

            $temp = explode(',',$out3[$k]);
            $new_out3[$k]['gid'] = $temp[0];
            $new_out3[$k]['power'] = end($temp);
        }

        $rand = array();
        if($num == 2){

            $temp = explode('-',$range);

            foreach ($new_out2 as $k=>$v){
                if($new_out2[$k]['power']>= $temp[0] && $new_out2[$k]['power']<= $temp[1]){
                    $rand[$k]['gid'] = $new_out2[$k]['gid'];
                    $rand[$k]['power'] = $new_out2[$k]['power'];
                }
                // $randarr = array_rand($rand);

            }

            $randarr = array_rand($rand);
            //dd($randarr);exit;



            $map['AdminID'] = $_SESSION['adminid'];
            $map['UserID'] = $userid;
            $map['Gold'] = $new_out2[$randarr]['gid'];
            $map['ServerID'] = $serverid;
            $map['IsGet'] = 0;

            $result = $m->add($map);

            if($result){
                return 'ok';
            }else{
                return null;
            }

        }elseif ($num == 3){
            $temp = explode('-',$range);

            foreach ($new_out3 as $k=>$v){
                if($new_out3[$k]['power']>= $temp[0] && $new_out3[$k]['power']<= $temp[1]){
                    $rand[$k]['gid'] = $new_out3[$k]['gid'];
                    $rand[$k]['power'] = $new_out3[$k]['power'];
                }
                // $randarr = array_rand($rand);

            }

            $randarr = array_rand($rand);
            //dd($randarr);exit;



            $map['AdminID'] = $_SESSION['adminid'];
            $map['UserID'] = $userid;
            $map['Gold'] = $new_out3[$randarr]['gid'];
            $map['ServerID'] = $serverid;
            $map['IsGet'] = 0;

            $result = $m->add($map);

            if($result){
                return 'ok';
            }else{
                return null;
            }


        }else{
            return false;
        }
    }

}