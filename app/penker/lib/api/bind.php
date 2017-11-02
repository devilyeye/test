<?php
class penker_api_bind{

    public $app;
    public function __construct($app)
    {
        $this->app = $app;
    }
    public function create(){
        logger::info('$_POST:'.var_export($_POST,1));
    }


    public function penker_bind(){
        $penker = $this->app->model('bind');
        $params = array(
            'name' => app::get('site')->getConf('site.name'),
            'node_id' => $_POST['nodeId'],
            'secret_key' => $_POST['secret_key'],
            'state' => $_POST['state'],
            );
        if($penker->insert($params)){
            $msg = 'succ';
        }else{
            $msg ='fail';
        }
        return $msg;
    }
}