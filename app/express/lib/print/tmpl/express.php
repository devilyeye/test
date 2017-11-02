<?php
class express_print_tmpl_express{
	
	public $smarty = null;

	static $singleton = null;

    static function instance($logi,$controller){
        $logi = strtolower($logi);
        if(self::$singleton[$logi] === null){
            self::$singleton[$logi] = kernel::single('express_print_tmpl_express',$controller);
        }

        return self::$singleton[$logi];
    }

    public function __construct($controller){
        $this->smarty = $controller;
    }

    public function setParams( $params = array() ){
        return $this;
    }

    public function getTmpl(){
        $this->smarty->pagedata['userAgent'] = $this->getUserAgent();
        $this->smarty->singlepage("admin/delivery/express_print_electron.html");
    }

    /**
     * 获得浏览器版本
     * Enter description here ...
     */
    public function getUserAgent() {
        $agent = $_SERVER["HTTP_USER_AGENT"];
        $brower = array('brower' => 'Other', 'ver' => '0', 'type' => 2);
    
        if (strpos($agent, "MSIE 10.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '10.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 9.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '9.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 8.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '8.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 7.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '7.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 6.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '6.0', 'type' => 1);
        }
        elseif (strpos($agent, "Trident")) {
            //IE11以后的版本
            $str = substr($agent, strpos($agent, 'rv:') + strlen('rv:'));
            $ver = substr($str, 0, strpos($str, ')'));
            $brower = array('brower' => 'Ie', 'ver' => $ver, 'type' => 1);
        }
        elseif (strpos($agent, "Chrome")) {
            $str = substr($agent, strpos($agent, 'Chrome/') + strlen('Chrome/'));
            $verInfo = explode(" ", $str);
            $brower = array('brower' => 'Chrome', 'ver' => $verInfo[0], 'type' => 2);
        }
        elseif (strpos($agent, "Firefox")) {
            $str = substr($agent, strpos($agent, 'Firefox/') + strlen('Firefox/'));
            $brower = array('brower' => 'Firefox', 'ver' => $str, 'type' => 2);
        }
        return $brower;
    }

    public function getExpressTpl($corp) {
        $prtTmplId = $corp['prt_tmpl_id'];
        $templateObj = app::get("express")->model('print_tmpl');
        $printTmpl = $templateObj->dump($prtTmplId);
        if(empty($printTmpl)) {
            $this->msg = '没有设定快递单模板';
            return false;
        }
        if(empty($printTmpl['template_select'])){
            $printTmpl['template_select'] = json_encode(array());
        }else{
            $printTmpl['template_select'] = json_encode(unserialize($printTmpl['template_select']));
        }
        $this->printTpl = $printTmpl;

        $this->_dataToField($printTmpl['prt_tmpl_data']);
        return true;
    }

    private function _dealUnShopexWidgetField($channelId) {
        $printTmpl = $this->printTpl;
        switch($printTmpl['template_type']) {
            case 'cainiao':
                $this->printField = array(//菜鸟打印固定部分
                    'seller_id', 'ship_mobile', 'ship_tel', 'cp_code', 'print_config', 'mailno_position', 'package_wdjc', 'package_wd', 'logi_no', 'ship_name', 'ship_detailaddr', 'dly_name', 'dly_tel', 'dly_mobile', 'dly_detailaddr', 'dly_area_1'
                );
                $this->printField = array_merge(json_decode($printTmpl['template_select'], true), $this->printField);
                break;
            case 'cainiao_standard':
                $this->printField = array('batch_logi_no','logi_no','print_config', 'json_packet');
                break;
            case 'cainiao_user':
                $this->printField = array('batch_logi_no','logi_no','print_config', 'json_packet');
                if($this->printTpl['out_template_id'] > 0) {
                    $sdf = array(
                        'template_id' => $this->printTpl['out_template_id']
                    );
                    $rs = kernel::single('erpapi_router_request')->set('logistics', $channelId)->template_getUserDefinedTpl($sdf);
                    if ($rs['rsp'] == 'succ') {
                        $this->printTpl['custom_area_url'] = $rs['data']['custom_area_url'];
                        $this->printTpl['template_select'] = json_encode($rs['data']['template_select']);
                        $this->printField = array_merge((array)$rs['data']['template_select'], $this->printField);
                    } else {
                        $this->msg = '请求客户自定义部分失败,' . $rs['msg'];
                        return false;
                    }
                }
                break;
            default : break;
        }
        return true;
    }

    //获取快递单打印模板中需要的字段
    private function _dataToField($data) {
        $arrData = explode(';', $data);
        $field = array();
        foreach($arrData as $val) {
            if(strpos($val, 'report_field:') !== false || strpos($val, 'report_barcode:') !== false) {
                $tmpData = explode(',', $val);
                $field[] = $tmpData[5];
            }
        }
        $this->printField = $field;
    }
}