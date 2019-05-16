<?php
namespace Admin\Controller;
use Admin\Logic\RoomConfig;
use Admin\Logic\PlatformDetail;
use Admin\Logic\UserList;
use Admin\Logic\Notice;

header('Content-Type:text/html; charset=utf-8');
class SystemController extends BaseController {

    //欢迎页
    public function home(){
        $admin = $_SESSION['admin'];

        $str = substr($admin,0,2);

        if($str=='kv'){
            $this->display('only_v');
        }else{
            $this->display();
        }


    }

    public function ip_allow(){
        $m = M('IP_Flag','','RDDB_USER');
        $data = $m->select();
        $this->assign('data',$data);
        $this->display();

    }
    public function change_ip_allow(){
        $m = M('IP_Flag','','RDDB_USER');
        $map['state'] = I('post.state');
        $map1['id'] = 0;
        $m->where($map1)->save($map);
        $this->success('操作成功！');
    }

    public function ban_ip(){
        $m = M('IP_Allow_List','','RDDB_USER');
        $ip = I('get.ip');
        $map['IP'] = $ip;
        if($m->where($map)->find()){
            $m->where($map)->delete();
        }
        $this->success('操作成功！');
    }

    public function unlock_ip(){
        $m = M('IP_Allow_List','','RDDB_USER');
        $ip = I('get.ip');
        $map['IP'] = $ip;
        if(!$m->where($map)->find()){
            $m->add($map);
        }
        $this->success('操作成功！');
    }

    public function token(){
        $token = I('get.token');

        if($_SESSION['token']==$token){
            echo '合法提交！提交内容：';
            dump(I('get.'));
        }else{
            echo '非法提交！';
            dump($_SESSION);
            dump(I('get.'));
        }
    }

    //清楚缓存
    public function unlink(){
        $dir = "././Application/Runtime/";

        $this->deldir($dir);
        $this->display('home');
    }

    public function deldir($dir) {
        //先删除目录下的文件：
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if($file != "." && $file!="..") {
                $fullpath = $dir."/".$file;
                if(!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    $this->deldir($fullpath);
                }
            }
        }
        closedir($dh);

    }



    //管理员列表
    public function admin_list(){
        $m = M('Staff');
        $data = $m->select();

        dump($data);
        $this->assign('data',$data);
        $this->display();

    }
	
	//权限列表
	public function power_list(){
		$m = M('POWER_system','','RDDB_USER');
		$admin = I('get.admin');
		$map['admin'] = $admin;
		$data = $m->where($map)->select();
		
		$this->assign('data',$data);
		$this->assign('admin',$admin);
        $this->display();
	}

	//修改权限
	public function power_change(){
		$m = M('POWER_system','','RDDB_USER');
		$map['admin'] = I('post.admin');
		unset($_POST['admin']);
		
		$zero['adminList']=0;
		$zero['loginLog']=0;
		$zero['goldLog']=0;
		$zero['lockLog']=0;
		$zero['ban_deal_log']=0;
		$zero['usertype_log']=0;
		$zero['pool_log']=0;
		$zero['controllerLog']=0;
		$zero['room']=0;
		$zero['room_out_in']=0;
		$zero['notice_log']=0;
		$zero['platform']=0;
		$zero['platform_all_log']=0;
		$zero['searach']=0;
		$zero['onlineList']=0;
		$zero['tradeList']=0;
		$zero['protect_list']=0;
		$zero['message']=0;
		$zero['user_login']=0;
		$zero['all_pr']=0;
		$zero['p_log']=0;
		$zero['r_log']=0;
		$zero['goldChange']=0;
		$zero['winList']=0;	
		$zero['vipList']=0;	
		$zero['ud_card_list']=0;	
		$zero['ud_addgold']=0;	
		$zero['ud_ctr']=0;	
		$zero['ud_lock']=0;	
		$zero['ud_ban_deal']=0;	
		$zero['ud_usertype']=0;	
		$zero['ud_remark']=0;	
		$zero['ud_pwd_change']=0;	
		$zero['ud_sglb']=0;
		$zero['ud_sjdz']=0;	
		$zero['ud_mysm']=0;	
		$zero['ud_zlj']=0;	
		$zero['ud_dsx']=0;	
		$zero['ud_wflm']=0;	
		$zero['ud_bxqy']=0;	
		$zero['link_user']=0;	
		$zero['ud_roll_back']=0;
        $zero['ud_ctr_add']=0;
        $zero['ud_card_add']=0;
        $zero['ud_21']=0;
        $zero['ud_2dby']=0;
        $zero['ud_sglhj']=0;
        $zero['ud_xar']=0;
        $zero['ud_bs']=0;
        $zero['ud_dx2']=0;
        $zero['ud_add_insure']=0;
        $zero['card_log']=0;


        $zero['add_user']=0;
        $zero['gp_online']=0;
        $zero['gp_online1']=0;
        $zero['gp_reg']=0;
        $zero['gp_active']=0;
        $zero['ud_sgml']=0;
        $zero['ud_cjsgml']=0;
        $zero['pt_ctr']=0;
        $zero['ud_mla']=0;
        $zero['gp_alive']=0;
        $zero['gp_room']=0;
        $zero['ud_phone']=0;
        $zero['lot_ban_deal']=0;





        $init = $m->where($map)->save($zero);
		
		$result = $m->where($map)->save(I('post.'));
		if($result){
			$this->redirect('admin_list');
		}else{
			echo 'ERRO';	
		}
	}

    //点卡
    public function card_list(){
		
		$m = M('NExchangeCardList','','DB_TREASURE');

        if(I('get.code')){
            $map['ExchangeCode'] = I('get.code');
            $data = $m->where($map)->select();
            $count = $m->where($map)->count();
            $page = new \Think\Page($count,100);
        }elseif (I('get.userid')){
            $map['ExchangeUserID'] = I('get.userid');
            $data = $m->where($map)->select();
            $count = $m->where($map)->count();
            $page = new \Think\Page($count,100);
        }else{
            $count = $m->count();
            $page = new \Think\Page($count,100);
            $data = $m->limit($page->firstRow.','.$page->listRows)->select();
        }



        $show = $page->show();
		$this->assign('data',$data);
		$this->assign('page',$show);
        $this->display();
			
	}

	//生成点卡
    public function init_card(){
        $m = M('NExchangeCardList','','DB_TREASURE');
        $index = I('get.card_val');
        $type = I('get.card_type');
        $num = I('get.card_num');

        if($type) {
            $str = '体验卡';
        }else{
            $str = '点卡';
        }

        if($index==1){
            $val = 200000;
        }else{
            $val = 100000;
        }

        if($num>100){

            $this->error("抱歉，一次最多生成100张！");
        }

        $card_arr = array();
        $result = null;

        $txt_arr = array();


            for ($i=0;$i<$num;$i++){
                $uniqid = md5(uniqid(microtime(true),true));
                $card_arr['ExchangeCode'] = substr($uniqid,15);
                array_push($txt_arr,$card_arr['ExchangeCode']);
                $card_arr['ExchangeCardIndex'] = $index;
                $card_arr['NewUserCard'] = $type;
                $result = $m->add($card_arr);
            }

        if($result){
            $arr = implode(',',$txt_arr);

            dump($arr);

            $myfile = fopen("newfile.txt", "w") or die("Unable to open file!");

            fwrite($myfile, $arr);

            fclose($myfile);

            $user = new UserList();
            $user->do_card_log($num,$str,$val);
        }


    }


    //删除管理员
    public  function  delete_admin(){
        $id = I('get.id');
        $m = M('Staff');
        $map['id'] = $id;
        $res = $m->where($map)->select();
        if($res){
            $result = $m->where($map)->delete();
            if($result){
                $this->redirect('admin_list');
            }else{
                echo '删除失败';
            }

        }else{
            echo '删除失败';
        }
    }

    //房间配置
    public function room_config1(){

        $roomconf = new RoomConfig();

        $data = $roomconf->room_data();

        $this->assign('data',$data['data']);
        $this->assign('other',$data['other']);

        $this->display();

    }

    //房间配置
    public function room_config(){

        $res = M()->query("declare @count int exec @count = [dbo].[Web_Room_Config] select @count");

        dump($res);

        $this->assign('data',$res);
        $this->display();

    }

    //用户点控记录
    public function ctr_record(){
        $uid = I('get.userid');
        $admin = I('get.admin');

        $d_id = $uid - 10000;

        $user = new UserList();

        if($uid){
            $data = $user->player_ctr_log($d_id);
        }elseif ($admin){

            $data = $user->player_ctr_log('',$admin);
        }else{
            $data = $user->player_ctr_log();
        }


        $this->assign('data',$data['data']);
        $this->assign('page',$data['show']);
        $this->display();
    }


    //管理员操作记录
    public function do_log(){
        $uid = I('get.userid');
        $tid = I('get.tid');
        $admin = I('get.admin');
        $roomname =  I('get.roomname');

        $d_id = $uid - 10000;

        $user = new UserList();

        if($admin){
            $data = $user->admin_do_log($tid,'',$admin);
        }elseif ($roomname){
            $data = $user->admin_do_log($tid,'','',$roomname);
        }
        else{
            $uid?$data = $user->admin_do_log($tid,$d_id):$data = $user->admin_do_log($tid);
        }

        $this->assign('data',$data['data']);
        $this->assign('page',$data['show']);

        if($tid==1){
            $this->display('login_log');
        }elseif ($tid==2){
            $this->display('add_gold_log');
        }elseif ($tid==3){
            $this->display('lock_log');
        }elseif ($tid==4){
            $this->display('ban_deal_log');
        }elseif ($tid==5){
            $this->display('change_type_log');
        }elseif ($tid==6){
            $this->display('pool_log');
        }elseif ($tid==7){
            $this->display('room_cheat_log');
        }elseif ($tid==8){
            $this->display('room_balance_log');
        }elseif ($tid==11){
            $this->display('roll_back_log');
        }elseif ($tid==15){
            $this->display('card_log');
        }

    }

    //添加金币记录
    public function gold_add_record(){

    }

    //平台明细
    public function platform_detail(){
        $platform = new PlatformDetail();

        if(I('post.stime') && I('post.etime')){
            $stime = I('post.stime');
            $etime = I('post.etime');
        }else{
            $stime = '';
            $etime = '';
        }


        if($stime!=null && $etime!=null){

            $data = $platform->data(date('Y-m-d',$stime),date('Y-m-d',strtotime('+1 day',$etime)));

        }else{
            $data = S('platform_detail');
            if(empty($data)){
                $data = $platform->data();
                S('platform_detail',$data,20);
            }
        }

        $this->assign('data',$data);
        $this->display();
    }

    //平台汇总
    public function platform_all(){
        $platform = new PlatformDetail();

        $data = S('platform_all');

        if(empty($data)){
            $data  = $platform->platform_all();
            S('platform_all',$data,600);
        }

        $this->assign('data',$data);
        $this->display();
    }

    //添加管理员
    public function go_add_admin(){

        if(I('post.admin') && I('post.pwd')){
            $m = M('staff');
            $m1 = M('POWER_system');
            $map['ip'] = '0.0.0.0';
            $map['loginNumber'] = '0';
            $map['account'] = I('post.admin');
            $map['password'] = base64_encode(I('post.pwd'));
            $m->add($map);

            unset($_POST['pwd']);
            unset($_POST['pwd1']);
            $m1map['admin'] = I('post.admin');
            $isIn = $m1->where($m1map)->select();

            $str = substr(I('post.admin'),0,2);

            if($isIn){

                $res = $m1->where($m1map)->save(I('post.'));
            }elseif ($str=='kv'){

            }
            else{

                $res = $m1->add(I('post.'));
            }

            if($res){
                $this->redirect('admin_list');
            }else{
                echo 'erro';
            }

        }else{
            return null;
        }
    }


        //房间血池修改
    public  function  do_room_cheat(){
        $roomid = I('post.roomid');

        I('post.cheat')?$cheat = I('post.cheat'):$cheat = 0;

        $roomconf = new RoomConfig();
        $data = $roomconf->do_room_cheat($roomid,$cheat);

        if($data){
            $this->redirect('room_config');
        }else{
            echo 'ERRO';
        }
    }


    //房间平衡值修改
    public  function  do_room_balance(){
        $roomid =  I('post.roomid');

        I('post.balance')?$balance = I('post.balance'):$balance = 0;

        $roomconf = new RoomConfig();
        $data = $roomconf->do_room_balance($roomid,$balance);

        if($data){
            $this->redirect('room_config');
        }else{
            echo 'ERRO';
        }
    }

    //房间吞吐率
    public function room_rate(){

        I('get.sid')?$sid = I('get.sid'):$sid = 6041;

        $roomconf = new RoomConfig();


        $data = $roomconf->room_rate($sid);
        $room = $roomconf->room_data(true);

        $this->assign('data',$data['data']);
        $this->assign('room',$room);
        $this->assign('page',$data['show']);

        $this->display();

    }

    //公告
    public function notice(){

        $notice = new Notice();

        $data = $notice->data();

        $this->assign('data',$data);

        $this->display();

    }

    //添加公告
    public function add_notice(){
        $title = $_POST['title'];
        $content = $_POST['content'];
        $index = $_POST['index'];

        $notice = new Notice();

        $result = $notice->add($title,$content,$index);

        if($result){
            $this->redirect('notice');
        }else{
            echo 'ERRO';
        }

    }

    //删除公告
    public function delete_notice(){
        $notice = new Notice();

        $result = $notice->delete(I('get.id'));

        if($result){
            $this->redirect('notice');
        }else{
            echo 'ERRO';
        }

    }

    //保存公告
    public function save_notice(){
        $notice = new Notice();

        $id = $_POST['id'];
        $index = $_POST['index'];
        $title = $_POST['title'];
        $content = $_POST['content'];


        $result = $notice->update($id,$title,$content,$index);

        if($result){
            $this->redirect('notice');
        }else{
            echo 'ERRO';
        }
    }

    public function  update_notice(){
        $id = I('get.id');

        $m = M('AA_bulletin');

        $map['id'] = $id;

        $data = $m->where($map)->find();

        $this->assign('data',$data);
        $this->display();
    }


    //登出
    public function log_out(){
        session_destroy();
        //unset($_SESSION);

        $this->redirect('Login/index');
    }


    public function check_login(){
        $m = M('Staff');

        $map['account'] = I('post.user');
        $map['password'] = base64_encode(I('post.pwd'));

        $info = $m->where($map)->find();

        if($info){
            echo 'yes';
        }else{
            echo 'error';
        }
    }

    public function update_pwd_go(){
        $pwd = I('post.pwd');
        $admin = I('post.user');
        $m = M('staff');
        $save['password'] = base64_encode($pwd);

        $map['account'] = $admin;
        $res = $m->where($map)->save($save);
        if($res){
            $this->redirect('../Admin/Login/index');
        }else{
            echo "ERRO";
        }
    }


}