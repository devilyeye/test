<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class b2c_apiv_apis_request_wechat_openid extends b2c_apiv_extends_request
{
    var $method = 'store.user.token';
    var $callback = array();
    var $title = '微信获取openid接口';
    var $timeout = 60;
    var $async = false;

    public function get_params($code)
    {
        $params = array();
        $params['code'] = $code;
        return $params;
    }
}
