<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class b2c_apiv_apis_request_member_point_change_v3 extends b2c_apiv_extends_request
{
    var $method = 'store.point.update';
    var $callback = array();
    var $title = '会员积分更新接口';
    var $timeout = 10;
    var $async = false;

    public function get_params($point_id)
    {
        $data = array();
        $point_data = app::get('b2c')->model('member_point')->getRow('member_id,change_point,addtime,expiretime,reason,remark,related_id,operator',array('id'=>$point_id,'status'=>'false'));

        $data['member_id'] = app::get('b2c')->model('members')->get_crm_member_id($point_data['member_id']);
        $data['point'] = $point_data['change_point'];
        $data['type'] = '2';
        $data['point_desc'] = $point_data['reason'];
        if($point_data['remark']){
            $data['point_desc'] = $data['point_desc'].":".$point_data['remark'];
        }
        $data['invalid_time'] = $point_data['expiretime'];
        $data['batch_id'] = $point_id;
        $data['tid'] = $point_data['member_id'];//单据号
//        $data[''] = $point_data['addtime'];
//        $data[''] = $point_data['remark'];
//        $data[''] = $point_data['related_id'];
//        $data[''] = $point_data['operator'];

        return $data;
    }
}
