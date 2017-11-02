<?php
class penker_api_goods{

    public $app;
    public function __construct($app)
    {
        $this->app = $app;
    }
    public function get_order_list(){
        $penker = $this->app->model('bind');
        $arr_bind = $penker->getList();
        $pack = $_GET['pack'];
        $key = $arr_bind[0]['secret_key'];
        $iv = substr(md5($arr_bind[0]['node_id']),0,16);
        $params = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($pack), MCRYPT_MODE_CBC,$iv);
        $params = json_decode($params,true);
        if(!empty($params)){
            
        }else{
            $result['rsp'] = 'fail';
            $result['data'] = 'pack is wrong';
            print_r(json_encode($result));
        }
    }
}