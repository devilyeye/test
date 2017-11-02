<?php
/**
 * 银联电子面单
 * sunjing@shopex.cn
 * @param void
 * @return void
 */
class express_waybill_unionpay
{
    /**
     * 默认订单来源类型
     * @var String 默认来源
     */
    public static $defaultChannelsType = 'OTHER';

    public static $businessType = array(
        'EMS' => 1,
        'SF' => 2,
        'ZJS' => 3,
        'ZTO' => 4,
        'HTKY' => 5,
        'YTO' => 6,
        'STO' => 7,
        'YUNDA' => 8,
        'DBKD'=>9,
        'FAST'=>10,
        'EYB'=>11,
        'UC'=>12
    );

    /**
     * 获取物流公司编码
     * @param Sring $logistics_code 物流代码
     */
    public function logistics($logistics_code) {
        $logistics = array(
              'EMS' => array('code'=>'EMS','name'=>'普通EMS'),
              'EYB'=>array('code'=>'EYB','name'=>'EMS快递包裹'),
//            'SF'=>array('code'=>'SF','name'=>'顺丰'),
//            'ZJS' => array('code' => 'ZJS', 'name'=>'宅急送'),
//            'ZTO' => array('code' => 'ZTO', 'name' => '中通'),
//            'HTKY' => array('code' => 'HTKY', 'name'=>'百世汇通'),
              'YTO' => array('code' => 'YTO', 'name' => '圆通'),
              'STO' => array('code' => 'STO', 'name' => '申通'),
              'FAST' => array('code' => 'FAST', 'name' => '快捷'),
//            'YUNDA'=>array('code' => 'YUNDA', 'name' => '韵达快递'),
//            'DBKD'=>array('code' => 'DBKD', 'name' => '德邦快递'),
              'UC'=>array('code'=>'UC','name'=>'优速快递'),
              'RFD'=>array('code'=>'RFD','name'=>'如风达'),
        );

        if (!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }
        return $logistics;
    }

    public function pay_method($method = '') {
        $payMethod = array(
            '1' => array('code' => '1', 'name' => '现付'),
            '2' => array('code' => '2', 'name' => '到付'),
            '3' => array('code' => '3', 'name' => '月结'),
            '4' => array('code' => '4', 'name' => '第三方支付'),
        );
        if (!empty($method)) {
            return $payMethod[$method];
        }
        return $payMethod;
    }

    public function exp_type($method = ''){//1-标准快件 2-当日达(
        $expType = array(
            '1'=>array('code' => '1', 'name' => '标准快件'),
            '2'=>array('code' => '2', 'name' => '当日达'),
        );
        if (!empty($method)) {
            return $expType[$method];
        }
        return $expType;
    }
}