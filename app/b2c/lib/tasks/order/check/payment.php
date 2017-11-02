<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class b2c_tasks_order_check_payment extends base_task_abstract implements base_interface_task{

    public function exec($params=null){

        $obj_payments = app::get('ectools')->model('payments');
        $obj_order_bills = app::get('ectools')->model('order_bills');
        $obj_orders = app::get('b2c')->model('orders');
        $obj_abnormal_orders = app::get('b2c')->model('order_newabnormal');
        $order_pay = kernel::single('b2c_order_pay');
        $worker = 'b2c_tasks_order_check_update';

        $ret['checked']= 0;
        $ret['status']= 'succ';
        $ret['t_payed|bthan']= time()-3*24*3600;
        //$orders=array();
        $result = $obj_payments->getList('payment_id',$ret);

        if($result){
            $payments_id=array();
            foreach($result as $val){
                $payments_id[] = $val['payment_id'];
            }
            $order_bills = $obj_order_bills->getList('*',array('bill_id'=>$payments_id,'pay_object'=>'order'));
            if($order_bills){
                foreach($order_bills as $val){
                    if($val['bill_type'] == 'payments' && $val['pay_object'] == 'order'){
                        $order = $obj_orders->getRow('total_amount,pay_status,status',array('order_id'=>$val['rel_id']));
                        //暂定订单支付状态作为基础判断
                        if(($order['pay_status']==0 || $order['pay_status']==3) && $order['status'] =='active'){
                            $val['order_id']=$val['rel_id'];
                            $order_payed=$order_pay->check_payed($val['rel_id']);
                            if($order['total_amount']>$order_payed && $order['pay_status']==3 ){//部分付款订单
                                $obj_payments->update(array('checked'=>'1'),array('payment_id'=>$val['bill_id']));
                                continue;
                            }
                            //放到检查更新的队列中
                            // system_queue::instance()->publish($worker, $worker, $val);
                            // if($order['total_amount']>$order_payed){//部分付款，变更部分支付
                            // }elseif($order['total_amount']==$order_payed){//全额付款，变更为已支付订单
                            // }
                            //
                            $abnormal_order['order_id']=$val['rel_id'];
                            if($order['total_amount']>$order_payed ){
                                $abnormal_order['abnormal_type']='订单部分支付订单状态未更改';
                            }else{
                                $abnormal_order['abnormal_type']='已支付订单状态未更改';        
                            }
                            $abnormal_order['updatetime']=time();
                            $obj_abnormal_orders->save($abnormal_order);
                        }
                    }else{
                        //$obj_payments->update(array('checked'=>'1'),array('payment_id'=>$val['bill_id']));
                    }
                    $obj_payments->update(array('checked'=>'1'),array('payment_id'=>$val['bill_id']));
                }
            }
        }
    }
}