<?php

namespace app\api\model;

use app\api\service\Token;
use think\Model;

class Users extends Model
{


    public static function getUserToken($openid){
        $res = Users::get(['openid'=>$openid]);
        if($res){
            return $res['token'];
        }else{
            return false;
        }
    }

    /**
     * 新增用户
     * @param $openid
     * @return mixed
     */
    public static function addUser($openid,$post){
        $data = array(
            'token' => Token::makeUserToken(),
            'token_express' => time()+config('secure.express'),
            'openid' => $openid,
            'reg_time' => time(),
        );
        $res = Users::insert($data);
        return $res;
    }

    /**
     * 通过token获取用户信息
     * @param $token
     */
    public static function getUserInfo($token){
        return self::get(['token'=>$token]);
    }
}