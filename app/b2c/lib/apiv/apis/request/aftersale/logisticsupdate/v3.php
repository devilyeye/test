<?php

class b2c_apiv_apis_request_aftersale_logisticsupdate_v3 extends b2c_apiv_extends_request
{
    var $method = 'store.trade.aftersale.logistics.update';
    var $callback = array();
    var $title = '售后物流信息';
    var $timeout = 5;
    var $async = true;

    public function get_params($sdf){
        $order_detail['aftersale_id'] = $sdf['return_id'];
        $order_detail['tid'] = $sdf['order_id'];
        $order_detail['logistics_info'] = json_encode(array(
            'logistics_company' => $sdf['logistics_company_name'],
            'logistics_no' => $sdf['logistics_no'],
        ));
        return $order_detail;
    }
    
}

