<?php
class penker_service_bind{

    function __construct($app){
        $this->app = $app;
    }
    function penker_bind($params){
        $data= json_encode($params);
        $key = md5('ECSTORE_PK');
        $iv = "0000000000000000";
        $pack=base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv));
        $url = 'http://penkr.shopex.cn/index.php?ctl=bind&source=ECSTORE&pack='.urlencode($pack);
        $core_http = kernel::single('base_httpclient');
        $response = $core_http->get($url);
        return $url;
    }
    function order_create($params){
        $data = array(
            'openid' => $params['openid'],
            'User_info' => $params['User_info'],
            'Goods_id' => $params['Goods_id'],
            'Guide_identity' => $params['Guide_identity'],
            );
        $data= josn_encode($data);
        $pk = $secret_key;
        $iv = substr(md5(nodeId),0,16);
        $pack=base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv));
    }
}

