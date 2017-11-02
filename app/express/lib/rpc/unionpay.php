<?php

class express_rpc_unionpay {

    const _TO_NODE_ID = '1705101437';

    /**
     * 绑定
     *
     * @return void
     * @author 
     **/
    public function bind()
    {
        $params = array(
            'app' => 'app.applyNodeBind',
            'node_id' => base_shopnode::node_id('b2c'),
            'from_certi_id' => base_certificate::certi_id(),
            'callback' => '',
            'sess_callback' => '',
            //'api_url'       => 'http://210.22.91.77:24511/front_xgb/FrontService',
            'api_url' =>'http://180.166.112.20:4111/front_xgb/FrontService',
            'node_type' => 'ums',
            'to_node' => self::_TO_NODE_ID,
            'shop_name' => app::get('site')->getConf('site.name'),
            'api_secret' => 'c582cd9c33ca4fedb887d15fc46689cf',
        );

        $params['certi_ac']=$this->_gen_bind_sign($params);

        //$api_url = 'http://sws.ex-sandbox.com/api.php';
        $api_url = 'http://www.matrix.ecos.shopex.cn/api.php';
        $headers = array('Connection' => 5);
        $core_http = kernel::single('base_httpclient');
        $response = $core_http->set_timeout(5)->post($api_url, $params,$headers);
        $response = json_decode($response,true);
        if ($response['res'] == 'succ' || $response['msg']['errorDescription'] == '绑定关系已存在,不需要重复绑定' ) {
            base_kvstore::instance('b2c/bind/unionpay')->store('b2c_bind_unionpay', true);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 绑定
     *
     * @return void
     * @author
     **/
    public function unbind()
    {
        $params = array(
            'app'           => 'app.changeBindRelStatus',
            'from_node'       => base_shopnode::node_id('b2c'),
            'from_certi_id' => base_certificate::certi_id(),
            'node_type'     => 'ums',
            'to_node'       => self::_TO_NODE_ID,
            'status'        =>  'del',
            'reason'        =>  '重新解绑啦',
        );

        $params['certi_ac']=$this->_gen_bind_sign($params);

        //$api_url = 'http://sws.ex-sandbox.com/api.php';
        $api_url = 'http://www.matrix.ecos.shopex.cn/api.php';
        $headers = array('Connection' => 5);
        $core_http = kernel::single('base_httpclient');
        $response = $core_http->set_timeout(5)->post($api_url, $params,$headers);
        $response = json_decode($response,true);
        if ($response['res'] == 'succ') {
            base_kvstore::instance('b2c/bind/unionpay')->store('b2c_bind_unionpay', false);
            return true;
        } else {
            return false;
        }
    }

    private function _gen_bind_sign($params)
    {
        $token = base_certificate::token();
        ksort($params);
        $str = '';
        foreach ($params as $key =>$value) {
            $str .= $value;
        }

        $sign = md5($str.$token);
        return $sign;
    }
}