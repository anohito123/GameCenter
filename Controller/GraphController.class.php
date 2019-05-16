<?php
namespace Admin\Controller;
use Admin\Logic\UserList;
use Think\Controller;
header('Content-Type:text/html; charset=utf-8');
class GraphController extends BaseController {

    function GetMacAddr($os_type)
    {
        switch ( strtolower($os_type) )
        {
            case "linux":
                $this->forLinux();
                break;
            case "solaris":
                break;
            case "unix":
                break;
            case "aix":
                break;
            default:
                $this->forWindows();
                break;
        }

        $temp_array = array();
        foreach ( $this->return_array as $value )
        {
            if ( preg_match( "/[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f]/i", $value, $temp_array ) )
            {
                $this->mac_addr = $temp_array[0];
                break;
            }
        }
        unset($temp_array);
        return $this->mac_addr;
    }

    function forWindows()
    {
        @exec("ipconfig /all", $this->return_array);
        if ( $this->return_array )
            return $this->return_array;
        else{
            $ipconfig = $_SERVER["WINDIR"]."\system32\ipconfig.exe";
            if ( is_file($ipconfig) )
                @exec($ipconfig." /all", $this->return_array);
            else
                @exec($_SERVER["WINDIR"]."\system\ipconfig.exe /all", $this->return_array);
            return $this->return_array;
        }
    }

	public function alive1(){
		$m = M('UserAliveLog','','RDDB_USER');
		$stime = date('Y-m-d',strtotime('-15 day'));
		$etime = date('Y-m-d');
		
		$map['LogTime'] = array('between',$stime.','.$etime);
		
		$data = $m->where($map)->select();
		//dump($data);
		$log_time = $this->get_reg_sum($stime,$etime);
		//dump($log_time);
		foreach($data as $k=>$v){
			$data[$k]['reg_sum'] = $log_time[$v['logtime']]['total']?$log_time[$v['logtime']]['total']:0;
			$data[$k]['day1'] = $data[$k]['reg_sum']!=0?round(($data[$k]['day1']/$data[$k]['reg_sum'])*100,2):0;
			$data[$k]['day2'] = $data[$k]['reg_sum']!=0?round(($data[$k]['day2']/$data[$k]['reg_sum'])*100,2):0;
			$data[$k]['day3'] = $data[$k]['reg_sum']!=0?round(($data[$k]['day3']/$data[$k]['reg_sum'])*100,2):0;
			$data[$k]['day4'] = $data[$k]['reg_sum']!=0?round(($data[$k]['day4']/$data[$k]['reg_sum'])*100,2):0;
			$data[$k]['day5'] = $data[$k]['reg_sum']!=0?round(($data[$k]['day5']/$data[$k]['reg_sum'])*100,2):0;
			$data[$k]['day6'] = $data[$k]['reg_sum']!=0?round(($data[$k]['day6']/$data[$k]['reg_sum'])*100,2):0;
			$data[$k]['day7'] = $data[$k]['reg_sum']!=0?round(($data[$k]['day7']/$data[$k]['reg_sum'])*100,2):0;			
			$data[$k]['day10'] = $data[$k]['reg_sum']!=0?round(($data[$k]['day10']/$data[$k]['reg_sum'])*100,2):0;
			$data[$k]['day15'] = $data[$k]['reg_sum']!=0?round(($data[$k]['day15']/$data[$k]['reg_sum'])*100,2):0;
			$data[$k]['day25'] = $data[$k]['reg_sum']!=0?round(($data[$k]['day25']/$data[$k]['reg_sum'])*100,2):0;
			$data[$k]['day30'] = $data[$k]['reg_sum']!=0?round(($data[$k]['day30']/$data[$k]['reg_sum'])*100,2):0;
		}
		
		//dump($data);
		
		$this->assign('data',$data);
		$this->display();
		
	}
	
	public function get_reg_sum($stime='',$etime=''){
        //$m =  M('AA_ZZ_Log_Register');
        $m =  M('AA_ZZ_Log_Register','','RDDB_USER');

        if($stime!=''&& $etime!=''){

            $map['LogTime'] = array('between',$stime.','.$etime);
            $log_data = $m->where($map)->field('CONVERT(varchar(10), LogTime,21) as daytime,count(User_Id) as total')->group('CONVERT(varchar(10), LogTime,21)')->select();

        }else{

            $log_data = $m->field('CONVERT(varchar(10), LogTime,21) as daytime,count(User_Id) as total')->group('CONVERT(varchar(10), LogTime,21)')->select();
        }

        $log_data = $this->changeKeys($log_data, 'daytime');

        return $log_data;

    }
	
	public function changeKeys(array $array, $newKey) {
        $newArr = array();
        if(!empty($array)){
            foreach ($array as $item) {
                is_object($item) ? $item = json_decode(json_encode($item), true) : '';
                $newArr[$item[$newKey]] = $item;
            }
        }

        return $newArr;
    }
	

    public function alive(){
        $m = M('AccountsInfo');
        I('get.gp')=='alive'?$a_type = $_SESSION['a_type']:$a_type = $_SESSION['a_type']= I('get.type');
        if($_GET['stime'] && $_GET['etime']){
            $stime = $_SESSION['alive_stime'] = date('Y-m-d',$_GET['stime']);
            $today = $_SESSION['alive_etime'] = date('Y-m-d',$_GET['etime']);
        }else{

            if($_SESSION['alive_stime'] && $_SESSION['alive_etime']){
                $stime = $_SESSION['alive_stime'];
                $today = $_SESSION['alive_etime'];
            }else{
                $stime = date('Y-m-d',strtotime("-15 day"));
                $today = date('Y-m-d');
            }

        }

        //计算两个日期相差天数
        $second1 = strtotime($stime);
        $second2 = strtotime($today);
        if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
        }
        $days =  ($second1 - $second2) / 86400;

        $days>30?$days=30:'';

        //当前日期加2天的时间戳
        $afd = strtotime(date('Y-m-d',strtotime('+2 day')));

        $total_arr = array();
        $date_arr = array();
        $day1_arr = array();
        $day2_arr = array();
        $day3_arr = array();
        $day7_arr = array();
        $day15_arr = array();
        $day30_arr = array();



        for ($i=0;$i<$days;$i++){
            $after_day = date('Y-m-d',strtotime($stime."+1 day"));

            $day2 = date('Y-m-d',strtotime($stime."+2 day"));
            $day3 = date('Y-m-d',strtotime($stime."+3 day"));
            $day4 = date('Y-m-d',strtotime($stime."+4 day"));
            $day5 = date('Y-m-d',strtotime($stime."+5 day"));
            $day6 = date('Y-m-d',strtotime($stime."+6 day"));
            $day7 = date('Y-m-d',strtotime($stime."+7 day"));
            $day10 = date('Y-m-d',strtotime($stime."+10 day"));
            $day15 = date('Y-m-d',strtotime($stime."+15 day"));
            $day25 = date('Y-m-d',strtotime($stime."+25 day"));
            $day30 = date('Y-m-d',strtotime($stime."+30 day"));


            $map['RegisterDate'] = array('between',$stime.','.$after_day);
            $map['UserID'] = array('gt',10000);

            $data[$i]['reg_total'] = $m->where($map)->count();

            $data[$i]['time'] = $stime;

            $start =  array('between',$stime.','.$after_day);

            $map1['LastLoginTime'] = array('between',$after_day.','.date('Y-m-d',strtotime($after_day."+1 day")));


            $map1['RegisterDate'] =$map2['RegisterDate'] = $map3['RegisterDate'] = $map4['RegisterDate'] = $map5['RegisterDate'] = $map6['RegisterDate']
                = $map7['RegisterDate'] = $map10['RegisterDate'] = $map15['RegisterDate'] = $map25['RegisterDate'] = $map30['RegisterDate'] = $start;

            if($a_type =='total'){

                $map2['LastLoginTime'] = array('between',$after_day.','.date('Y-m-d',strtotime($day2."+1 day")));
                $map3['LastLoginTime'] = array('between',$after_day.','.date('Y-m-d',strtotime($day3."+1 day")));
                $map4['LastLoginTime'] = array('between',$after_day.','.date('Y-m-d',strtotime($day4."+1 day")));
                $map5['LastLoginTime'] = array('between',$after_day.','.date('Y-m-d',strtotime($day5."+1 day")));
                $map6['LastLoginTime'] = array('between',$after_day.','.date('Y-m-d',strtotime($day6."+1 day")));
                $map7['LastLoginTime'] = array('between',$after_day.','.date('Y-m-d',strtotime($day7."+1 day")));
                $map10['LastLoginTime'] = array('between',$after_day.','.date('Y-m-d',strtotime($day10."+1 day")));
                $map15['LastLoginTime'] = array('between',$after_day.','.date('Y-m-d',strtotime($day15."+1 day")));
                $map25['LastLoginTime'] = array('between',$after_day.','.date('Y-m-d',strtotime($day25."+1 day")));
                $map30['LastLoginTime'] = array('between',$after_day.','.date('Y-m-d',strtotime($day30."+1 day")));
            }else{
                $map2['LastLoginTime'] = array('between',$day2.','.date('Y-m-d',strtotime($day2."+1 day")));
                $map3['LastLoginTime'] = array('between',$day3.','.date('Y-m-d',strtotime($day3."+1 day")));
                $map4['LastLoginTime'] = array('between',$day4.','.date('Y-m-d',strtotime($day4."+1 day")));
                $map5['LastLoginTime'] = array('between',$day5.','.date('Y-m-d',strtotime($day5."+1 day")));
                $map6['LastLoginTime'] = array('between',$day6.','.date('Y-m-d',strtotime($day6."+1 day")));
                $map7['LastLoginTime'] = array('between',$day7.','.date('Y-m-d',strtotime($day7."+1 day")));
                $map10['LastLoginTime'] = array('between',$day10.','.date('Y-m-d',strtotime($day10."+1 day")));
                $map15['LastLoginTime'] = array('between',$day15.','.date('Y-m-d',strtotime($day15."+1 day")));
                $map25['LastLoginTime'] = array('between',$day25.','.date('Y-m-d',strtotime($day25."+1 day")));
                $map30['LastLoginTime'] = array('between',$day30.','.date('Y-m-d',strtotime($day30."+1 day")));
            }


            $md1 = strtotime(substr($map1['LastLoginTime'][1],-10));
            $md2 = strtotime(substr($map2['LastLoginTime'][1],-10));
            $md3 = strtotime(substr($map3['LastLoginTime'][1],-10));
            $md4 = strtotime(substr($map4['LastLoginTime'][1],-10));
            $md5 = strtotime(substr($map5['LastLoginTime'][1],-10));
            $md6 = strtotime(substr($map6['LastLoginTime'][1],-10));
            $md7 = strtotime(substr($map7['LastLoginTime'][1],-10));
            $md10 = strtotime(substr($map10['LastLoginTime'][1],-10));
            $md15 = strtotime(substr($map15['LastLoginTime'][1],-10));
            $md25 = strtotime(substr($map25['LastLoginTime'][1],-10));
            $md30 = strtotime(substr($map30['LastLoginTime'][1],-10));

            //dump($md30);
            //dump($map30['LastLoginTime'][1]);exit;

            $md1<$afd?$data[$i]['day1'] = $m->where($map1)->count():$data[$i]['day1']=0;
            $md2<$afd?$data[$i]['day2'] = $m->where($map2)->count():$data[$i]['day2']=0;
            $md3<$afd?$data[$i]['day3'] = $m->where($map3)->count():$data[$i]['day3']=0;
            $md4<$afd?$data[$i]['day4'] = $m->where($map4)->count():$data[$i]['day4']=0;
            $md5<$afd?$data[$i]['day5'] = $m->where($map5)->count():$data[$i]['day5']=0;
            $md6<$afd?$data[$i]['day6'] = $m->where($map6)->count():$data[$i]['day6']=0;
            $md7<$afd?$data[$i]['day7'] = $m->where($map7)->count():$data[$i]['day7']=0;
            $md10<$afd?$data[$i]['day10'] = $m->where($map10)->count():$data[$i]['day10']=0;
            $md15<$afd?$data[$i]['day15'] = $m->where($map15)->count():$data[$i]['day15']=0;
            $md25<$afd?$data[$i]['day25'] = $m->where($map25)->count():$data[$i]['day25']=0;
            $md30<$afd?$data[$i]['day30'] = $m->where($map30)->count():$data[$i]['day30']=0;


            array_push($total_arr,$data[$i]['reg_total']);
            array_push($date_arr,$stime);
            array_push($day1_arr,$data[$i]['day1']);
            array_push($day2_arr,$data[$i]['day2']);
            array_push($day3_arr,$data[$i]['day3']);
            array_push($day4_arr,$data[$i]['day4']);
            array_push($day5_arr,$data[$i]['day5']);
            array_push($day6_arr,$data[$i]['day6']);
            array_push($day7_arr,$data[$i]['day7']);
            array_push($day10_arr,$data[$i]['day10']);
            array_push($day15_arr,$data[$i]['day15']);
            array_push($day25_arr,$data[$i]['day25']);
            array_push($day30_arr,$data[$i]['day30']);
            //dump($map15);
            $stime = $after_day;

        }

        if(I('get.gp')=='alive'){
            $date = implode(',',$date_arr);
            $total = implode(',',$total_arr);
            $day_1 = implode(',',$day1_arr);
            $day_2 = implode(',',$day2_arr);
            $day_3 = implode(',',$day3_arr);
            $day_4 = implode(',',$day4_arr);
            $day_5 = implode(',',$day5_arr);
            $day_6 = implode(',',$day6_arr);
            $day_7 = implode(',',$day7_arr);
            $day_10 = implode(',',$day10_arr);
            $day_15 = implode(',',$day15_arr);
            $day_25 = implode(',',$day25_arr);
            $day_30 = implode(',',$day30_arr);
            $result = $date.';'.$total.';'.$day_1.';'.$day_2.';'.$day_3.';'.$day_4.';'.$day_5.';'.$day_6.';'.$day_7.';'.$day_10.';'.$day_15.';'.$day_25.';'.$day_30;
            echo $result;
        }else{
            $this->assign('data',$data);
            $this->display();
        }


       //dump($data);

    }


    public function online(){
        I('get.stime')? $_SESSION['online_date'] = date("Y-m-d H:i:s",I('get.stime')):'';

        $m = M('GameScoreLocker','','DB_TREASURE');

        $room = $m->field('ServerID,count(ServerID) as total')->group('ServerID')->select();

        foreach ($room as $v){
            $rids[] = $v['serverid'];
        }

        $user = new UserList();
        $roomname = $user->get_room($rids);

        foreach ($room as $k=>$v){
            $room[$k]['name'] = $roomname[$v['serverid']]['roomname']?$roomname[$v['serverid']]['roomname']:'大厅';
        }

      //  dump($room); exit;

        $sid = $m->field('ServerID')->select();
        foreach ($sid as $v){
            $sids[] = $v['serverid'];
        }


        $data1['hall'] = 0;
        $data1['1'] = 0;
        $data1['2'] = 0;
        $data1['3'] = 0;
        $data1['4'] = 0;
        for($i=0;$i<count($sids);$i++){


            $str = substr($sids[$i],-1);

            $str==0?$data1['hall']++:'';
            $str==1?$data1['1']++:'';
            $str==2?$data1['2']++:'';
            $str==3?$data1['3']++:'';
            $str==4?$data1['4']++:'';
        }

        $this->assign('data',$room);
        $this->assign('data1',$data1);
        if(I('get.log')){
            $this->display('online1');
        }else{
            $this->display();
        }


    }



    public function get_online(){
   // echo I('get.log');exit;
        $m = M('onlineTotal','','RDDB_USER');

        //获取当前时间字符串
        $map['date'] = date("Y-m-d").' 00:00:00';

        $_SESSION['online_date']?$map['date'] = $_SESSION['online_date']:'';

        if(I('get.log')==1){
            $map['MemberOrder'] = array('gt',0);
        }

        $total_arr = array();

        for ($i=0;$i<48;$i++){

            $total = $m->where($map)->sum('total');
            $total==null?$total=0:'';
            array_push($total_arr,$total);

            $each_time = strtotime($map['date']);
            $each_time +=1800;
            $map['date'] = date("Y-m-d H:i:s",$each_time);

        }

        $data = implode(',',$total_arr);

        echo $data;

    }

    public function register(){

        I('get.stime')? $_SESSION['register_stime'] = date("Y-m-d",I('get.stime')):'';
        I('get.etime')? $_SESSION['register_etime'] = date("Y-m-d",I('get.etime')):'';

        $this->display();

    }

    public function get_register(){
        $m = M('AccountsInfo');
        $map['UserID'] = array('gt',10000);

        if($_SESSION['register_stime']!=null && $_SESSION['register_etime']!=null){
            $stime = $_SESSION['register_stime'];
            $etime = $_SESSION['register_etime'];

        }else{
            $stime = date('Y-m-d',strtotime("-15 day"));
            $etime = date('Y-m-d');
        }

        //计算两个日期相差天数
        $second1 = strtotime($stime);
        $second2 = strtotime($etime);
        if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
        }
        $days =  ($second1 - $second2) / 86400;

        $date_arr = array();
        $total_arr = array();
        $ios_arr = array();
        $adr_arr = array();
        $pc_arr = array();

        for($i=0;$i<$days+1;$i++){

            $after_day = date('Y-m-d',strtotime($stime."+1 day")) ;

            $map['RegisterDate'] = array('between',$stime.','.$after_day);

            $total = $m->where($map)->count();
            $ios = $m->where($map)->where('AppPlatform=1')->count();
            $adr = $m->where($map)->where('AppPlatform=2')->count();
            //$pc = $m->where($map)->where('AppPlatform=3')->count();

            array_push($total_arr,$total);
            array_push($date_arr,$stime);
            array_push($ios_arr,$ios);
            array_push($adr_arr,$adr);
            //array_push($pc_arr,$pc);

            $stime = date('Y-m-d',strtotime($stime."+1 day")) ;

        }
        $date = implode(',',$date_arr);
        $total = implode(',',$total_arr);
        $ios = implode(',',$ios_arr);
        $adr = implode(',',$adr_arr);
        //$pc = implode(',',$pc_arr);

        $result = $date.';'.$total.';'.$ios.';'.$adr;

        echo $result;

    }

    public function active(){
        I('get.stime')? $_SESSION['active_stime'] = date("Y-m-d",I('get.stime')):'';
        I('get.etime')? $_SESSION['active_etime'] = date("Y-m-d",I('get.etime')):'';
        $this->display();
    }

    public function get_active(){
        $m =  M('RecordUserLeave','','DB_RECOED');
        $map['UserID'] = array('gt',10000);

        if($_SESSION['active_stime']!=null && $_SESSION['active_etime']!=null){
            $stime = $_SESSION['active_stime'];
            $etime = $_SESSION['active_etime'];

        }else{
            $stime = date('Y-m-d',strtotime("-15 day"));
            $etime = date('Y-m-d');
        }

        //计算两个日期相差天数
        $second1 = strtotime($stime);
        $second2 = strtotime($etime);
        if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
        }
        $days =  ($second1 - $second2) / 86400;

        $date_arr = array();
        $total_arr = array();
        for($i=0;$i<$days+1;$i++){
            $after_day = date('Y-m-d',strtotime($stime."+1 day")) ;

            $map['LeaveTime'] = array('between',$stime.','.$after_day);

            $total = $m->field("distinct UserID")->where($map)->select();
            $total = count($total);

            array_push($total_arr,$total);
            array_push($date_arr,$stime);
            $stime = date('Y-m-d',strtotime($stime."+1 day")) ;

        }

        $date = implode(',',$date_arr);
        $total = implode(',',$total_arr);

        $result = $date.';'.$total;
        echo $result;
    }


    public function room_blood(){
        I('get.rb_stime')? $_SESSION['rb_stime'] = date("Y-m-d",I('get.rb_stime')):'';
        I('get.roomid')?$_SESSION['roomid'] =I('get.roomid'):'';

        //$rid = I('get.roomid')
        $this->assign('rid',$_SESSION['roomid']);
        $this->display();
    }



    public function get_room_blood(){
        $m =  M('AA_Log_GameRoomBlood','','DB_SERVER');
        $roomid = $_SESSION['roomid'];

        if($roomid){
            $map['room_id'] = $roomid;

            if($_SESSION['rb_stime']!=null){
                $stime = $_SESSION['rb_stime'];

            }else{
                $stime = date('Y-m-d');

            }

            $map['datetime'] = array('gt',$stime);
            $date = array();
            $total = array();

            $start_stamp = strtotime($stime);


            //步长为10分钟
            for($i=0;$i<288;$i++){

                if($i==0){
                    $min = $m->where($map)->order('datetime')->limit(1)->select();
                    $total[0] = $min[0]['blood'];
                    $date[0] =$stime.' 00:00:00';

                }else{
                    $str_stime = date('Y-m-d H:i:s',$start_stamp);
                    $str_etime = date('Y-m-d H:i:s',strtotime($str_stime)+300);

                    $map['datetime'] = array('between',$str_stime.','.$str_etime);

                    $rs = $m->where($map)->order('datetime')->limit(1)->select();

                    if(!$rs){
                        $total[$i] = $total[$i-1];
                        $date[$i] =$str_stime;
                    }else{
                        $total[$i] = $rs[0]['blood'];
                        $date[$i] =$str_stime;
                    }

                }
                $start_stamp +=300;
            }

            $date = implode(',',$date);
            $total = implode(',',$total);

            $result = $date.';'.$total.';'.$roomid;
            echo $result;


        }else{

           echo '';

        }




    }

}
