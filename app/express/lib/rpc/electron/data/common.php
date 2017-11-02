<?php
class express_rpc_electron_data_common {
    public $channel;
    public $logiId;
    public $dlyCenter;
    protected $directRet = array();
    protected $needRequestId = array();
    protected $needRequestOrderId = array();
    protected $needGetWBExtend = false;

    public function setChannel($channel) {
        $this->channel = $channel;
        return $this;
    }

    public function setLogiId($logiId) {
        $this->logiId = $logiId;
        return $this;
    }

    public function setDlyCenter($dlyCenter) {
        $this->dlyCenter = $dlyCenter;
        return $this;
    }

    protected function getDirectSdf($arrDelivery, $arrBill, $shop) {
        return false;
    }

    public function directDealParam($arrOrderId, $arrBillId) {
        $this->directRet = array();
        $this->needRequestId = array();
        $this->needRequestOrderId = array();

        if(empty($arrBillId)) {
            $arrOrder = $this->preDealOrder($arrOrderId);
        } else {
            //$arrOrder = app::get('wms')->model('delivery')->getList('*', array('delivery_id'=>$arrOrderId));
            //$arrBill = $this->preDealBillDelivery($arrBillId, $arrOrder[0]);
        }

        if(empty($arrOrder) || (isset($arrBill) && empty($arrBill))) {
            return array(
                'succ' => $this->directRet['succ'],
                'fail' => $this->directRet['fail']
            );
        }

        //发货信息
        $dlyCenterObj = app::get('express')->model('dly_center');
        $shop = $dlyCenterObj->dump($this->dlyCenter);

        $sdf = $this->getDirectSdf($arrOrder, $arrBill, $shop);
        return array(
            'succ' => $this->directRet['succ'],
            'fail' => $this->directRet['fail'],
            'sdf' =>  $sdf,
            'need_request_id' => $this->needRequestId
        );
    }

    public function preDealOrder($arrOrderId) {

        $arrOrder = app::get('b2c')->model('orders')->getList('*', array('order_id'=>$arrOrderId));
        $billObj = app::get('express')->model('order_bill');
        $objWaybill = app::get('express')->model('waybill');
        $needRequest = $hasOrder = array();
        foreach($arrOrder as $order) {
            $arrBill = $billObj->dump(array('order_id'=>$order['order_id'],'logi_id'=>$this->logiId,'type'=>'1','status'=>'0'),'logi_no');
            $logi_no = $arrBill['logi_no'];
            $hasOrder[] = $order['order_id'];
            $filter = array('channel_id' => $this->channel['channel_id'], 'waybill_number' => $logi_no);

            if (!empty($logi_no) && $objWaybill->dump($filter)) {
                $this->directRet['succ'][] = array(
                    'logi_no' => $logi_no,
                    'order_id' => $order['order_id']
                );
            } else {
                $needRequest[] = $order;
                $this->needRequestOrderId[] = $order['order_id'];
            }
        }
        $noOrder = array_diff($arrOrderId, $hasOrder);
        if($noOrder) {
            foreach($noOrder as $val) {
                $this->directRet['fail'][] = array(
                    'order_id' => $val,
                    'msg' => '没有该订单'
                );
            }
        }
        return $needRequest;
    }

    protected function getDeliveryOrder($deliveryId) {
        $db = kernel::database();
        $deliveryIds = $this->getDeliveryIdBywms($deliveryId);
        $where = is_array($deliveryId) ? 'd.delivery_id in (' . (implode(',', $deliveryIds)) . ')' : 'd.delivery_id = "' . $deliveryIds .'"';
        $field = 'o.order_id, o.order_bn, o.total_amount, o.shop_id, o.createway, d.delivery_id';
        $sql = 'select '. $field . ' from sdb_ome_delivery_order as d left join sdb_ome_orders as o using(order_id) where ' . $where;
        $result = $db->select($sql);
        return $result;
    }

    //获取两条订单明细
    protected function getOrderItems($order_id) {
        static $orderItems = array();
        if (!$orderItems[$order_id]) {
            $orderItems[$order_id] = app::get('b2c')->model('order_items')->getList('*', array('order_id' => $order_id), 0, 2);
        }
        return $orderItems[$order_id];
    }

    //获取两条发货单明细
    public function getDeliveryItems($delivery_id) {
        static $deliveryItems = array();
        if(!$deliveryItems[$delivery_id]) {
            $deliveryItems[$delivery_id] = app::get('wms')->model('delivery_items')->getList('*', array('delivery_id' => $delivery_id),0,2);
        }
        return $deliveryItems[$delivery_id];
    }

    #设置子单的请求的订单号
    public function setChildRqOrdNo($deliveryBn, $billId){
        $deliveryBn = $deliveryBn."cldordno".$billId;
        return $deliveryBn;
    }

    #检查是否是子单的请求的订单号
    public function checkChildRqOrdNo($deliveryBn, &$main_order_no, &$waybill_cid){
        $pos = strpos($deliveryBn,'cldordno');
        if( $pos !== false){
            $main_order_no = substr($deliveryBn,0,$pos);
            $waybill_cid = substr($deliveryBn,$pos+8);
            return true;
        }else{
            return false;
        }
    }

    public function waybillExtendDealParam($arrDelivery, $arrBill) {
        if(!$this->needGetWBExtend) {
            return false;
        }
        $waybill = array();
        $waybillDelivery = array();
        foreach($arrDelivery as $delivery) {
            if(!empty($delivery['logi_no'])) { //需要有运单号
                $waybill[] = $delivery['logi_no'];
                $waybillDelivery[$delivery['logi_no']] = $delivery;
            }
        }
        if(empty($waybill)) {
            return array('sdf' => false);
        }
        $objExtend = app::get("logisticsmanager")->model("waybill_extend");
        $sql = 'SELECT w.waybill_number, e.id FROM sdb_logisticsmanager_waybill w LEFT JOIN sdb_logisticsmanager_waybill_extend e ON (w.id = e.waybill_id) WHERE w.waybill_number in ("'. implode('","', $waybill) .'") AND w.channel_id = "' . $this->channel['channel_id'] . '"';
        $arrExtend = $objExtend->db->select($sql);
        $needExtendDelivery = array();
        foreach($arrExtend as $val) {
            if(empty($val['id']) && $val['waybill_number']) {//没有大头笔
                $needExtendDelivery[] = $waybillDelivery[$val['waybill_number']];
            }
        }
        if(empty($needExtendDelivery)) {
            $billExtend = true;
            if(!empty($arrBill)) {
                $billExtend = $this->dealBillExtend($arrBill, $arrExtend[0]['id']);
            }
            return array('sdf' => false, 'bill_extend_fail' => !$billExtend);
        }
        $shop = app::get('logisticsmanager')->model('channel_extend')->dump(array('channel_id'=>$this->channel['channel_id']),'province,city,area,address_detail,seller_id,default_sender,mobile,tel,zip');
        $sdf = $this->getWaybillExtendSdf($needExtendDelivery, $shop);
        return array('sdf' => $sdf);
    }

    protected function getWaybillExtendSdf($arrDelivery, $shop) {
        return false;
    }

    protected function dealBillExtend($arrBill, $extendId) {
        $billWaybill = array();
        foreach($arrBill as $bill) {
            $billWaybill[] = $bill['logi_no'];
        }
        $objWaybill = app::get("logisticsmanager")->model("waybill");
        $filter = array(
            'waybill_number' => $billWaybill,
            'channel_id' => $this->channel['channel_id']
        );
        $waybill = $objWaybill->getList('id', $filter);
        $waybillId = array();
        foreach($waybill as $val) {
            $waybillId[$val['id']] = 1;
        }
        $objExtend = app::get("logisticsmanager")->model("waybill_extend");
        $extend = $objExtend->getList('waybill_id', array('waybill_id'=>array_keys($waybillId)));
        foreach($extend as $value) {
            if($waybillId[$value['waybill_id']]) {//有大头笔不更新
                unset($waybillId[$value['waybill_id']]);
            }
        }
        $upWaybillId = array_keys($waybillId);
        if($upWaybillId) {
            $extendData = $objExtend->dump(array('id'=>$extendId));
            unset($extendData['id']);
            $insertData = array();
            foreach($upWaybillId as $wbId) {
                $tmp = $extendData;
                $tmp['waybill_id'] = $wbId;
                $insertData[] = $tmp;
            }
            $sql = ome_func::get_insert_sql($objExtend, $insertData);
            return kernel::database()->exec($sql);
        }
        return true;
    }

    public function getDeliveryIdBywms($deliveryId){
        $db = kernel::database();
        $sql = "SELECT w.outer_delivery_bn FROM sdb_wms_delivery as w WHERE w.delivery_id in (".implode(',', $deliveryId).")";
        $delivery_list = $db->select($sql);
        $delivery_bnList = array();
        foreach ($delivery_list as $delivery){
            $delivery_bnList[] = $delivery['outer_delivery_bn'];
        }

        $deliveryArr = $db->select("SELECT d.delivery_id FROM sdb_ome_delivery as d WHERE d.delivery_bn in(".'\''.implode('\',\'',$delivery).'\''.")");
        $deliveryIds = array();
        foreach ($deliveryArr as $deliverys){
            $deliveryIds[] = $deliverys['delivery_id'];
        }
        return $deliveryIds;
    }

    
}