<?php
class express_print_electron {

    public function dealElectron(&$expressOrder, $channelId, $controller) {
        return $this->_dealDeliveryElectron($expressOrder, $channelId, $controller);
        return false;
    }

    private function _dealDeliveryElectron(&$expressOrder, $channelId, $controller) {
        $noWaybillBill = array();
        if(empty($expressOrder['logi_no'])) {
            $noWaybillBill[] = $expressOrder;
        }

        if(empty($noWaybillBill)) {
            return false;
        }

        $directParam = array(
            'get' => $_GET,
            'ids' => $expressOrder['order_id'],
            'channel' => array('channel_id'=>$channelId),
            'logi_id' => $expressOrder['logi_id'],
            'dly_center' => $expressOrder['dly_center'],
            'directNum' => 1
        );

        $controller->getElectronLogiNo($directParam,true);
    }

}