<?php/** * 支付宝支付功能 */namespace  api\controller;use Think\Controller;use Home\Lib\Tool;use Common\Service\LogicController;class Alipayment extends  Controller{    public function alipayapi(){        /**************************请求参数**************************/        $post=I('post.');        //商户订单号，商户网站订单系统中唯一订单号，必填        if($post['order_no']){            $out_trade_no = $post['order_no'];        }else{            $out_trade_no = Tool::createOrderSn();        }        //订单名称，必填        $subject = $post['subject'];        $chongzhi = $post['recharge'];//充值用        //付款金额，必填        $total_amount = $post['recharge'];        //测试完成后删除该行------------------        //$total_amount = '0.01';        //测试完成后删除该行------------------        //商品描述，可空        $body = $post['WIDbody'];        /************************************************************/        //添加订单信息        $data['uid'] = getUid();        if($post['type'] == 10){            $data['uid'] = $post['uid'];        }        $data['order_no'] = $out_trade_no;        $data['type'] = $post['type'];        $data['money'] = $total_amount;        $data['pay_type'] = 1;        $data['add_time'] = time();        if ($post['type'] == 4) {//加盟商家需要记录等级            $data['jiameng_id'] = $post['viptype'];        }        if($post['type'] == 5){//记录升级段位            $data['sheng_duan'] = $post['duan'];        }        if(!isset($post['type'])){//防止瞎鸡巴返回            redirect(U('Home/Order/myorder',['status'=>10]));        }        if ($post['type'] != 3 && $post['type'] != 6 && $post['type'] != '') {            M('order')->add($data);        }        //添加斗宝        if ($post['type'] == 2 ) {//斗宝需要记录斗宝订单            $info = M('doubao_order')->where(['uid'=>$post['uid'],'dou_id'=>$post['id'],'pay_status'=>1])->select();            if(count($info) >= 3){                redirect(U('Doubao/index'));                exit;            }            $re = M('doubao_order')->where(['ordernum'=>$out_trade_no])->find();            if(!$re){                $row = ['dou_id'=>$post['id'],                    'uid'=>$post['uid'],                    'name'=>$post['formName'],                    'phonecode'=>$post['formTel'],                    'ordernum'=>$out_trade_no,                    'c_time'=>time()                ];                M('doubao_order')->add($row);            }        }        require VENDOR_PATH.'Ali/wappay/pay.php';    }    //同步回传    public function return_url(){        require VENDOR_PATH.'Ali/config.php';        require VENDOR_PATH.'Ali/wappay/service/AlipayTradeService.php';        $arr=$_GET;        $alipaySevice = new \AlipayTradeService($config);        $result = $alipaySevice->check($arr);        /* 实际验证过程建议商户添加以下校验。         1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，        2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），        3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）        4、验证app_id是否为该商户本身。        */        if($result) {//验证成功            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////            //请在这里加上商户的业务逻辑程序代码            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表            //商户订单号            $out_trade_no = htmlspecialchars($_GET['out_trade_no']);            $orderObj = M('order');            $json = json_encode($_GET,true);            $orderData = $orderObj->where(['order_no'=>$out_trade_no])->find();            $logic = new LogicController($out_trade_no);            if ($orderData['pay_status'] == 0) {                if($orderData['type'] != 3 & $orderData['type'] != 6){                    $orderObj->where(['id'=>$orderData['id'],'order_no'=>$out_trade_no])->save(['pay_status'=>1,'pay_time'=>time(),'return'=>$json]);                }                if ($orderData['type'] == 1) {//充值                    //添加充值所得金额                    redirect(U('User/myJinBi'));                }elseif($orderData['type'] == 2){//斗宝                    //斗宝返回                    $jumpData = M('doubao_order')->field('dou_id,ordernum')->where(['ordernum'=>$out_trade_no,'pay_status'=>1])->find();                    redirect(U('Canjia/uploadingBaby',['id'=>$jumpData['dou_id'],'order'=>$jumpData['ordernum']]));                }else if($orderData['type'] == 3){//商家加盟                    redirect(U('Order/myorder',array('status'=>10)));                }else if($orderData['type'] == 4){//商家加盟                    redirect(U('Ruzhu/applyformerchant'));                }else if($orderData['type'] == 5){//加盟升级                    redirect(U('Ruzhu/merchant'));                }else if($orderData['type'] == 6){//加盟升级                    redirect(U('Order/myorder',array('status'=>10)));                }else if($orderData['type'] == 10){//用户缴纳会员费                    //*****************************************                    redirect(U('User/index'));                }else {//其他                    redirect(U('Order/myorder',array('status'=>10)));                }            }else if($orderData['pay_status'] == 1){                if ($orderData['type'] == 1) {                    //充值返回                    redirect(U('User/myJinBi'));                }elseif($orderData['type'] == 2){                    //斗宝返回                    //首先查询跳转信息                    $jumpData = M('doubao_order')->field('dou_id,ordernum')->where(['ordernum'=>$out_trade_no,'pay_status'=>1])->find();                    redirect(U('Canjia/uploadingBaby',['id'=>$jumpData['dou_id'],'order'=>$jumpData['ordernum']]));                }elseif($orderData['type'] == 4){//商家加盟                    redirect(U('Ruzhu/applyformerchant'));                }else if($orderData['type'] == 5){//会员升级                    redirect(U('Ruzhu/merchant'));                }elseif($orderData['type'] == 10){//用户缴纳会员费                    redirect(U('User/index'));                }else{//其他                    redirect(U('Order/myorder',array('status'=>10)));                }            }            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////        }else {            //验证失败            echo "验证失败";        }    }    //异步回调    public  function  notify_url(){        require VENDOR_PATH.'Ali/config.php';        require VENDOR_PATH.'Ali/wappay/service/AlipayTradeService.php';        $arr=$_POST;        $alipaySevice = new \AlipayTradeService($config);        $alipaySevice->writeLog(var_export($_POST,true));        $result = $alipaySevice->check($arr);        /* 实际验证过程建议商户添加以下校验。         1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，        2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），        3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）        4、验证app_id是否为该商户本身。        */        if($result) {//验证成功            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////            //将返回的结果转成json记录下来            $json = json_encode($arr,true);            //商户订单号            $out_trade_no = $_POST['out_trade_no'];            //支付宝交易号            $trade_no = $_POST['trade_no'];            //交易状态            $trade_status = $_POST['trade_status'];            if($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS') {                //判断该笔订单是否在商户网站中已经做过处理                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的                //如果有做过处理，不执行商户的业务程序                //注意：                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知                $orderObj = M('order');                $json = json_encode($_POST,true);                $orderData = $orderObj->where(['order_no'=>$out_trade_no])->find();                $logic = new LogicController($out_trade_no,$this->order_id);                if ($orderData['pay_status'] == 0) {                    $orderObj->where(['id'=>$orderData['id'],'order_no'=>$out_trade_no])->save(['pay_type'=>1,'pay_status'=>1,'pay_time'=>time(),'return'=>$json]);                    if ($orderData['type'] == 1) {                        //添加充值所得金额                        $logic->recharge();                    }elseif($orderData['type'] == 2){                        //斗宝返回                        //查找该用户的信息                        $where['uid'] = $orderData['uid'];                        /* $where['is_me'] = 0;*/                        $user = M("user")->where($where)->find();                        //判断该用户是否是被邀请斗宝进来的用户并且还没有把斗宝名额赠送给上级                        $user['fid']=!empty($user['fid'])?$user['fid']:$_SESSION['fid'];                        //查找分享者信息和最近一条斗宝订单                        if($user['fid'] && $_SESSION['fid'] != $orderData['uid']){                            $num = M("setting")->where(['k'=>'collection'])->getField("v");//赠送名额数                            $fuser = M("user")->where(['uid'=>$user['fid']])->find();                            $forder = M('doubao_order')->where(['uid'=>$user['fid'],'pay_status'=>1])->find();                            $dou_id = M("doubao_order")->where(['ordernum'=>$out_trade_no])->getField("dou_id");                            if($num > 0) {                                for ($x = 0; $x < $num; $x++) {                                    //添加订单                                    $arr = array(                                        'uid' => $fuser['uid'],                                        'name' => !empty($forder['name']) ? $forder['name'] : $fuser['uname'],                                        'phonecode' => !empty($forder['phonecode']) ? $forder['phonecode'] : $fuser['mobile'],                                        'dou_id' => $dou_id,                                        'ordernum' => $out_trade_no,                                        'pay_status' => 1,                                        'type' => 1,                                    );                                    M("doubao_order")->add($arr);                                }                                //发送系统消息给上级                                $arr = array(                                    'title'=>'邀请好友斗宝赠送名额到账通知',                                    'content'=>'您邀请的'.$user['name'].'用户报名参加斗宝成功，名额已赠送到您的账户，你可进入“我的斗宝”栏查看并使用名额',                                    'is_all'=>0,                                    'touid'=>!empty($user['fid'])?$user['fid']:$_SESSION['fid'],                                    'addtime'=>time(),                                    'status'=>0,                                );                                M("message")->add($arr);                                //增加邀请记录表                                $yqing = array(                                    'bname'=>$user['uname'],                                    'bmobile'=>$user['mobile'],                                    'yname'=>$fuser['uname'],                                    'ymobile'=>$fuser['mobile'],                                    'add_time'=>time(),                                    'type'=>2,                                    'num'=>$num                                );                                M('yqing')->add($yqing);                            }                        }                        $logic->doubao();                    }else if($orderData['type'] == 3){                        $logic->buying();                    }else if($orderData['type'] == 4){//商家加盟                        $logic->ruzhu();                        redirect(U('Ruzhu/merchant'));                    }else if($orderData['type'] == 5){//加盟升级                        $logic->ruzhuUp();                        redirect(U('Ruzhu/merchant'));                    }else if($orderData['type'] == 6){                        $logic->collection();                    }else if($orderData['type'] == 10){//用户缴纳会员费                        $logic->addMember();                    }else{                        M('order')->where(['order_no'=>$out_trade_no])->save(['pay_status'=>2,'pay_time'=>time(),'return'=>$json]);                    }                }            }else{                M('order')->where(['order_no'=>$out_trade_no])->save(['pay_status'=>2,'pay_time'=>time(),'return'=>$json]);            }            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——            echo "success";		//请不要修改或删除        }else {            //验证失败            echo "fail";	//请不要修改或删除        }    }}