<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class b2c_apiv_apis_request_wechat_code extends b2c_apiv_extends_request
{
    var $method = 'store.user.oauth';
    var $callback = array();
    var $title = '微信获取code接口';
    var $timeout = 100;
    var $async = false;

    public function get_params($arr)
    {
        $params = array();
        $params['callback'] = $arr['callback'];
        $params['state'] = $arr['state'];
        return $params;
    }
}
