<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class b2c_tasks_order_check_update extends base_task_abstract implements base_interface_task{

    function exec($params=null)
    {
        $obj_payments = app::get('ectools')->model('payments');
        $obj_order_bills = app::get('ectools')->model('order_bills');
        $obj_orders = app::get('b2c')->model('orders');
        $obj_abnormal_orders = app::get('b2c')->model('order_newabnormal');
        $order_pay = kernel::single('b2c_order_pay');

        $order = $obj_orders->getRow('total_amount,pay_status,status',array('order_id'=>$params['order_id']));
        if($order['status'] != 'active' ){
            return ;
        }
        $order_payed=$order_pay->check_payed($params['order_id']);
        if($order['total_amount']>$order_payed && $order['pay_status']==3 ){
            return ;
        }

        if($order['total_amount']>$order_payed && $order['pay_status']==0 ){//把未支付的订单变为部分付款
            $obj_orders->update();
        }





        $order_id = $params['order_id'];
        if( $order_id ){
            $this->cancel_orders($order_id);
        }
    }

    function cancel_orders($order_id)
    {
        $order_payed = kernel::single('b2c_order_pay')->check_payed($order_id);
        if($order_payed>0){//支付过的订单无法自动取消订单
            return ;
        }

        $obj_checkorder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));
        $mdl_order_cancel_reason = app::get('b2c')->model('order_cancel_reason');
        if ($obj_checkorder->check_order_cancel($order_id,'',$message))
        {
            $sdf['order_id'] = $order_id;
            $sdf['op_id'] = 1;
            $sdf['opname'] = 'admin';
            $sdf['account_type'] = 'auto';

            $order_cancel_reason = array(
                'order_id' => $order_id,
                'reason_type' => 7,
                'reason_desc' => '订单超过设置的支付时间，自动取消',
                'cancel_time' => time(),
            );
            $b2c_order_cancel = kernel::single("b2c_order_cancel");
            if ($b2c_order_cancel->generate($sdf, $null, $message))
            {
                $result = $mdl_order_cancel_reason->save($order_cancel_reason);
                if($order_object = kernel::service('b2c_order_rpc_async')){
                    $order_object->modifyActive($sdf['order_id']);
                }
                $obj_coupon = kernel::single("b2c_coupon_order");
                if( $obj_coupon ){
                    $obj_coupon->use_c($sdf['order_id']);
                }
            }
        }
    }
}

