<?php
return array(
    //'配置项'=>'配置值'
    'CACHE_TIME' => '31104000',
    'limit' => 10,
    'image_upload_limit_size'=>1024 * 1024 * 5,//上传图片大小限制
    'secure'=>[
        'sign_salt' => 'wuyuanguoshangcheng-shidaiwanwang',//用于token加密
        'express' => 8640000, //过期时效1天
        'sign_express' => 2150000 //签名过期时间120秒
    ],
);