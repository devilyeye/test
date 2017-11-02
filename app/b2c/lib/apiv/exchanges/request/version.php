<?php
/**
 * ShopEx licence
 * 获取crm版本接口路由器
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class b2c_apiv_exchanges_request_version extends b2c_apiv_exchanges_request
{

    //获取绑定的crm的版本号
    public function getActive(){
        //存在crm_version的kv直接返回
        base_kvstore::instance('b2c.apiv.exchanges.request.version')->fetch('crm_version',$version);
        if ($version){
            return $version;
        }
        
        $version = '';
        $result = $this->rpc_caller_request($version, 'versiongetcrm');
        $result = json_decode($result,true);

        if(isset($result['crm_version']))
        {
            $version = $result['crm_version'];
            //kv存version值一天
            base_kvstore::instance('b2c.apiv.exchanges.request.version')->store('crm_version',$version,86400);
        }

        return $version;
    }

}
