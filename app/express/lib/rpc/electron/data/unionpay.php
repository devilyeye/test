<?php
/**
 * 银联电子面单
 */
class express_rpc_electron_data_unionpay extends express_rpc_electron_data_common
{
    public function getDirectSdf($arrOrder, $arrBill, $shop)
    {
        $order = $arrOrder[0];
        $orderItems = $this->getOrderItems($order['order_id']);
        if(empty($arrBill)) {
            $this->needRequestId[] = $order['order_id'];
        } else {
            //$this->needRequestId[] = $arrBill[0]['b_id'];
            //$delivery['delivery_bn'] = $this->setChildRqOrdNo($delivery['delivery_bn'], $arrBill[0]['b_id']);
        }

        $sdf = array(
            'primary_bn' => $order['order_id'],
            'order' => $order,
            'shop' => $shop,
            'order_item' => $orderItems,
        );
        return $sdf;
    }

}