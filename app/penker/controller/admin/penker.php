<?php
class penker_ctl_admin_penker extends desktop_controller{

    public $workground = 'penker_ctl_admin_penker';

    public function __construct($app)
    {
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
    }

    public function index(){
        $penker = $this->app->model('bind');
        $penker_status = $penker->getrow();
        if($penker_status['status'] == 1){
            $this->finder('penker_mdl_bind',array(
                'title'=>app::get('penker')->_('绑定情况'),
                'use_buildin_recycle'=>false,
                'actions' => array(
                        array('label'=>app::get('b2c')->_('解绑朋客'),'icon'=>'add.gif','href'=>'index.php?app=penker&ctl=admin_penker&act=bind','target'=>'_blank'),
                    ),
            ));
        }else{
            $this->finder('penker_mdl_bind',array(
                'title'=>app::get('penker')->_('绑定情况'),
                'use_buildin_recycle'=>false,
                'actions' => array(
                        array('label'=>app::get('b2c')->_('绑定朋客'),'icon'=>'add.gif','href'=>'index.php?app=penker&ctl=admin_penker&act=bind','target'=>'_blank'),
                    ),
            ));
        }
    }

    public function bind(){
        $node_id = base_shopnode::node_id('b2c');
        $base_url = kernel::base_url(1);
        $domain = parse_url($base_url);
        $scheme = $domain['scheme'];
        $host = str_replace($scheme.'://','',$base_url);

        $params = array(
            'source' => 'ECSTORE',
            'nodeId' => $node_id,
            'domain' => $host,
            'callback' =>kernel::openapi_url('openapi.penker.callback.bind','callback'),
        );
        $params['callback'] = urlencode($params['callback']);
        $penker_bind = kernel::single('penker_service_bind');
        $url = $penker_bind->penker_bind($params);
        $html ='<div><iframe width="80%" height="600" src="'.$url.'"/></div>';
        print_r($html);
    }
}
