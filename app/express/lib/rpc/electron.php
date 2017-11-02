<?php

class express_rpc_electron {

    //直连请求获取电子面单
    public function directGetWaybill($orderId, $channelId, $logiId, $dlyCenter) {
        $arrOrderId = explode(';', $orderId);

        $directRet = array();
        $channel = app::get('express')->model('channel')->dump(array('channel_id'=>$channelId));
        $param = kernel::single('express_rpc_electron_data_router')
            ->setChannel($channel)
            ->setLogiId($logiId)
            ->setDlyCenter($dlyCenter)
            ->directDealParam($arrOrderId, $arrBillId);
        if($param['succ']) {
            foreach($param['succ'] as $succ) {
                $directRet['succ'][] = $succ;
            }
        }
        if($param['fail']) {
            foreach($param['fail'] as $succ) {
                $directRet['fail'][] = $succ;
            }
        }
        
        if($param['sdf']) {
            $back = kernel::single('express_rpc_electron_router')->setChannel($channel)->directRequest($param['sdf']);
            $backRet = $this->directCallback($back, $param['need_request_id'], $channel, $logiId);
            if ($backRet['succ']) {
                foreach ($backRet['succ'] as $val) {
                    $directRet['succ'][] = $val;
                }
            }
            if ($backRet['fail']) {
                foreach ($backRet['fail'] as $val) {
                    $directRet['fail'][] = $val;
                }
            }
        }
        $directRet['doFail'] = count($directRet['fail']);
        $directRet['doSucc'] = count($directRet['succ']);
        $directRet['dealResult'] = 1;
        return $directRet;
    }

    public function directCallback($result, $needRequestId, $channel, $logiId) {
        $waybillCodeArr = array();
        if(!empty($result)) {
            $db = kernel::database();
            foreach ($result as $val) {
                $retData['order_id'] = $val['order_id'];
                if($val['succ']) {
                    $db->beginTransaction();
                    $retData['logi_no'] = $val['logi_no'];
                    $ret = $this->_dealDirectResult($val, $channel, $logiId);

                    if ($ret) {
                        $db->commit();
                        $waybillCodeArr['succ'][] = $retData;
                    } else {
                        $db->rollBack();
                        $waybillCodeArr['fail'][] = $retData;
                    }
                } elseif($val['succ'] === false) {
                    $retData['msg'] = $val['msg'];
                    $waybillCodeArr['fail'][] = $retData;
                }
            }
        } elseif(!empty($needRequestId)) {
            foreach ($needRequestId as $val) {
                $waybillCodeArr['fail'][] = array(
                    'request_id' => $val,
                    'msg' => '请求没有返回结果'
                );
            }
        }
        return $waybillCodeArr;
    }

    private function _dealDirectResult($params, $channel, $logiId) {
        $orderBillObj = app::get('express')->model('order_bill');
        $params['logi_no'] = trim($params['logi_no']);
        $params['logi_id'] = $logiId;
        $params['create_time'] = time();
        $ret = $orderBillObj->insert($params);
        if (!$ret) {
            return false;
        }

        $objorder_log = app::get('b2c')->model('order_log');
        $log_text = app::get('b2c')->_("获取电子面单：").$params['logi_no'];
        $this->user = kernel::single('desktop_user');

        $sdf_order_log = array(
            'rel_id' => $params['order_id'],
            'op_id' => $this->user->user_id,
            'op_name' => $this->user->user_data['account']['login_name'],
            'alttime' => time(),
            'bill_type' => 'order',
            'behavior' => 'gwaybill',
            'result' => 'SUCCESS',
            'log_text' => $log_text,
        );
        $objorder_log->save($sdf_order_log);

        return true;
    }

    //取消回收电子面单
    public function cancelWaybill($orderId, $logiInfo, $channelId)
    {
        if(empty($orderId) || empty($channelId) || empty($logiInfo)){
            return false;
        }

        //释放本地订单与物流单号的关联
        $orderBillObj = app::get('express')->model('order_bill');
        $result = $orderBillObj->delete(array('order_id'=>$orderId,'logi_id'=>$logiInfo['logi_id'],'logi_no'=>$logiInfo['logi_no'],'type'=>1));

        //请求接口释放运单号
        $channel = app::get('express')->model('channel')->dump(array('channel_id'=>$channelId));
        kernel::single('express_rpc_electron_router')->setChannel($channel)->recycleWaybill(array('logi_no'=>$logiInfo['logi_no']));

        $objorder_log = app::get('b2c')->model('order_log');
        $log_text = app::get('b2c')->_("取消电子面单：").$logiInfo['logi_no'];
        $this->user = kernel::single('desktop_user');

        $sdf_order_log = array(
            'rel_id' => $orderId,
            'op_id' => $this->user->user_id,
            'op_name' => $this->user->user_data['account']['login_name'],
            'alttime' => time(),
            'bill_type' => 'order',
            'behavior' => 'cwaybill',
            'result' => 'SUCCESS',
            'log_text' => $log_text,
        );
        $objorder_log->save($sdf_order_log);

        return $result;
    }

}