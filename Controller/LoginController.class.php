<?php
namespace Admin\Controller;
use Think\Controller;
header('Content-Type:text/html; charset=utf-8');
class LoginController extends Controller {
    public function index(){
//        $m = M('Staff');
//        $map['ip'] = '0.0.0.0';
//        $map['loginNumber'] = '0';
//        $map['account'] = 'admin';
//        $map['password'] = base64_encode('123456');
//       $m->add($map) ;
        $this->display();

    }

    public function test(){
        dump('这是测试页');
        dump($_GET);
        dump($_POST);
    }

    public function dispatch($tip,$path){
        $this->assign("tip",$tip);
        $this->assign("path",$path);
        $this->display("tip");
    }

    //执行登录
    public function do_Login(){

        $m = M('Staff');
        $m1 = M('POWER_system','','RDDB_USER');
        $m2 = M('Web_DoLog');
        //获取最后登录时间
        $time['lastLogintime'] = date('Y-m-d H:i:s',time());
        $map['account'] = I('post.user');
        $map['password'] = base64_encode(I('post.pwd'));

        $info = $m->where($map)->find();

        $id = $m->where($map)->getField('id');

        $m1map['admin'] = I('post.user');

        if($info){

            $u = $m1->where($m1map)->select();
            $_SESSION['power'] = $u[0];
            $_SESSION['admin'] = $map['account'];
            $_SESSION['pass'] = $map['password'];

            $_SESSION['adminid'] = $id;

            $data['logName'] = $_SESSION['admin'];
            $data['doIP'] = 0;
            $data['res'] = '成功';
            $data['logType'] = 1;
            $data['remark'] = '管理员登录';
            $m->where($map)->save($time);
            $m2->add($data);
            $this->redirect('System/home');

        }else{
            $this->dispatch("<i class='icon-remove'></i>账号或密码错误！","index");
        }

    }

    public function check_login(){
        $m = M('Staff');
        $time['lastLogintime'] = date('Y-m-d H:i:s',time());
        $map['account'] = I('post.user');
        $map['password'] = base64_encode(I('post.pwd'));

        $info = $m->where($map)->find();

        if($info){
            echo 'yes';
        }else{
            echo 'error';
        }
    }

}