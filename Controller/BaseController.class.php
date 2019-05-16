<?php
namespace Admin\Controller;
use Think\Controller;
header('Content-Type:text/html; charset=utf-8');
class BaseController extends Controller {
    public function _initialize()
    {
        //$str = $str = substr($_SESSION['admin'],0,2);
        $m = M('Staff');

        $map['account'] = $_SESSION['admin'];
        $map['password'] = $_SESSION['pass'];
        $info = $m->where($map)->find();

            if(!$info){

                unset($_SESSION);
            }

          if(!$_SESSION['admin'] ){
              $this->redirect('login/index');
          }
    }



}