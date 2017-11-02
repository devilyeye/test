<?php

/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class b2c_apiv_apis_request_order_refund_v3 extends b2c_apiv_extends_request
{

    var $method = 'store.trade.refund.add';
    var $callback = array();
    var $title = '退款申请新增';
    var $timeout = 5;
    var $async = true;

    public function get_params($sdf){
        $mdl_b2c_orders = app::get('b2c')->model('orders');
        $login_name = kernel::single('b2c_user_object')->get_member_name(null, $sdf['member_id']);
        $mdl_b2c_refund_apply = app::get('b2c')->model('refund_apply');
        $refunds_reason_text = $mdl_b2c_refund_apply->get_refunds_reason_text($sdf["refunds_reason"]);
        $arr_data = array(
            "tid" => $sdf["order_id"],
            "refund_id" => $sdf["refund_apply_bn"],
            "buyer_name" => $login_name,
            "refund_fee" => $sdf["money"],
            "currency" => "CNY",
            "currency_fee" => $sdf["money"],
            "t_begin" => $sdf["current_time"],
            "t_sent" => $sdf["current_time"],
            "refund_type" => "apply",
            "status" => "APPLY",
            "memo" => $refunds_reason_text,
            "buyer_bank" => "",
            "buyer_account" => "",
            "pay_type" => "",
            "payment_tid" => "",
            "payment_type" => "",
            "seller_account" => "",
            "t_received" => "",
            "outer_no" => "",
        );
        return $arr_data;
    }
}