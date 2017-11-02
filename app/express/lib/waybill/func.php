<?php
class express_waybill_func {
    //获取面单来源渠道
    public function channels($channel_type) {
        $channels = array(
            'unionpay'=>array('code' => 'unionpay', 'name' => '银派小跟班'),
        );

        if(!empty($channel_type)) {
            return $channels[$channel_type];
        }

        return $channels;
    }
}