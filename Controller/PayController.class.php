<?php
namespace Admin\Controller;
use Think\Controller;
use Admin\Logic\UserList;
header('Content-Type:text/html; charset=utf-8');
class PayController extends Controller {

    public function index(){
        $this->display();
    }


    public function go_pay(){
        //获取USER AGENT
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);

        //分析数据
        $is_pc = (strpos($agent, 'windows nt')) ? true : false;
        $is_iphone = (strpos($agent, 'iphone')) ? true : false;
        $is_ipad = (strpos($agent, 'ipad')) ? true : false;
        $is_android = (strpos($agent, 'android')) ? true : false;


        $bank = '911';
        //输出数据
        if($is_pc){
            $bank = '911';
        }
        if($is_iphone){
            $bank = '904';
        }
        if($is_ipad){
            $bank = '904';
        }
        if($is_android){
            $bank = '904';
        }

        $gameid = I('post.gameid');
        $rmb = I('post.rmb');

        $this->request($rmb,$bank,$gameid);


    }

    public function check_gameid(){

        $m = M('AccountsInfo','','RDDB_USER');

        $map['GameID'] = I('post.gameid');

        $info = $m->where($map)->find();

        if(!$info){
            echo 'error';
        }else{
            echo 'ok';
        }

    }

    public function request($rmb,$bank,$gameid){

        $pay_memberid = "181128301";   //商户ID
        $pay_orderid = 'E'.date("YmdHis").rand(100000,999999);    //订单号
        $pay_amount = $rmb;    //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
//        $pay_notifyurl = "http://localhost:8022/game_admin/index.php/Admin/Pay/pay_out";   //服务端返回地址
//        $pay_callbackurl = "http://localhost:8022/game_admin/index.php/Admin/Pay/pay_out";  //页面跳转返回地址

        $pay_notifyurl = "http://mgm.ailhj.com:8022/Admin/Pay/pay_out";   //服务端返回地址
        $pay_callbackurl = "http://mgm.ailhj.com:8022/Admin/Pay/pay_result";  //页面跳转返回地址
        $Md5key = "zg7zym372xwvuzlyfu87xyfe14e3zf3g";   //密钥
        $tjurl = "http://www.daodianpay.com/Pay_Index.html";   //提交地址
        $pay_bankcode = $bank;   //银行编码
//扫码
        $native = array(
            "pay_memberid" => $pay_memberid,
            "pay_orderid" => $pay_orderid,
            "pay_amount" => $pay_amount,
            "pay_applydate" => $pay_applydate,
            "pay_bankcode" => $pay_bankcode,
            "pay_notifyurl" => $pay_notifyurl,
            "pay_callbackurl" => $pay_callbackurl,
        );
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
//echo($md5str . "key=" . $Md5key);
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["pay_md5sign"] = $sign;
        $native['pay_attach'] = $gameid.';'.$pay_orderid;
        $native['pay_productname'] ='道具服务';

       //$this->assign('tjurl',$tjurl);
        $this->assign('native',$native);
        $this->assign('pay_amount',$pay_amount);



        $this->display('request');
    }

    public function pay_out(){
        $orderid = I('post.orderid');
        $rcode = I('post.returncode');
        $attach = I('post.attach');
        $rmb = I('post.amount');

        $data = explode(";",$attach);

        $gameid = $data[0];
        $oid = $data[1];

        if($rcode=='00' && $oid==$orderid){
            $uid = $gameid - 10000;
            $gold = $rmb*10000;
            $user = new UserList();
            $user->add_gold($uid,$gold,'玩家充值');

        }else{
            echo "充值失败！";
        }
    }

    public function pay_result(){
        dump($_POST);
        $rcode = I('post.returncode');

        if($rcode=='00'){
            $this->assign('msg','充值成功！');
        }else{
            $this->assign('msg','充值失败！');
        }

        $this->display();
    }

}