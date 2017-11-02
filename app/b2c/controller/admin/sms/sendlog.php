<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class b2c_ctl_admin_sms_sendlog extends desktop_controller{

    var $workground = 'b2c_ctl_admin_sms_sendlog';

    public function __construct($app)
    {
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
    }

    function index(){
        $this->finder('b2c_mdl_sms_send_log',$actions_base);
    }





}
?>