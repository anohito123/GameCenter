<?php
namespace Admin\Model;
use Think\Model;
class StaffModel extends Model {
    protected $tableName = 'Staff';

    protected $_auto = array (
        array('status','0'),  // 新增的时候把status字段设置为1
        array('ip','0.0.0.0'),
        array('loginNumber','0'),
        array('updated_at','time',3,'function'),
    );

}