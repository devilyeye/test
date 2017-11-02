<?php
/**
 * ShopEx licence
 * 获取微信接口路由器
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class b2c_apiv_exchanges_request_wechat extends b2c_apiv_exchanges_request
{

    //获取code
    public function getcode($params){
        $data = array();
        if( isset($params['callback']) ){
            $result = $this->rpc_caller_request($params, 'wechatcode');
            $result = json_decode($result,true);

//            if( isset($result['code']) )
//            {
                $data = $result;
//            }
        }
        return $data;
    }

    //获取openid
    public function getopenid($params){
        $data = '';
        if( isset($params['code']) ){
            $result = $this->rpc_caller_request($params, 'wechatopenid');
            $result = json_decode($result,true);

            if( isset($result['openid']) )
            {
                $data = $result;
            }
        }

        return $data;
    }

    //获取userinfo
    public function getuserinfo($params){
        $data = '';
        if( isset($params['openid']) ){
            $result = $this->rpc_caller_request($params, 'wechatuserinfo');
            $result = json_decode($result,true);

            if( isset($result['nickname']) )
            {
                $data = $result;
            }
        }

        return $data;
    }
}
