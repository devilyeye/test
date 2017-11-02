<?php
class penker_api_callback_bind{

    public $app;
    public function __construct($app)
    {
        $this->app = $app;
    }
    public function callback($params){
        $penker = $this->app->model('bind');
        if(!empty($_POST['nodeId'])){
            $filter = array('node_id' => $_POST['nodeId']);
            $arr_bind = $penker->getRow('*', $filter);
            if(empty($arr_bind) && $_POST['state'] == '1' ){
                $params = array(
                    'name' => app::get('site')->getConf('site.name'),
                    'node_id' => $_POST['nodeId'],
                    'secret_key' => $_POST['secret_key'],
                    'status' => $_POST['state'],
                    );
                if($penker->insert($params)){
                    $msg = 'succ';
                }else{
                    $msg = 'fail';
                }
            }elseif(!empty($arr_bind)){
                $params = array(
                    'name' => app::get('site')->getConf('site.name'),
                    'node_id' => $_POST['nodeId'],
                    'secret_key' => $_POST['secret_key'],
                    'status' => $_POST['state'],
                    );
                if($penker->update($params,array('shop_id' => $arr_bind['shop_id']))){
                    $msg = 'succ';
                }else{
                    $msg = 'fail';
                }
            }else{
                $msg = 'fail';
            }
        }else{
            $msg = 'fail';
        }
        print_r($msg);
    }
}