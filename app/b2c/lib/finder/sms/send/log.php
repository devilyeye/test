<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class b2c_finder_sms_send_log{
    var $detail_basic;
    public function __construct($app)
    {
        $this->app = $app;
        $this->controller = app::get('b2c')->controller('admin_sms_sendlog');

        $this->detail_basic = app::get('b2c')->_('会员信息');
    }

    function detail_basic($sms_id){
        $app = app::get('b2c');
        $sms_send_log=app::get('b2c')->model('sms_send_log');
        $sms_info = $sms_send_log->getRow('*',array('sms_id'=>$sms_id));
        $render = $app->render();
        $phone = explode(',',$sms_info['phone']);
        $sms_info['phones']=$phone;
        $render->pagedata['sms_info'] = $sms_info;
        return $render->fetch('admin/sms/send_log.html');
    }

}
