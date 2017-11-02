<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class b2c_ctl_admin_refund_apply extends desktop_controller{
    
    //显示
    public function index(){
        $this->finder('b2c_mdl_refund_apply',array(
                'title'=>app::get('b2c')->_('退款申请列表'),
                'use_buildin_set_tag'=>false,
                'use_buildin_recycle'=>false,
                'use_buildin_filter'=>true,
                'use_view_tab'=>true,
                'orderBy'=>'apply_id DESC',
        ));
    }

    //tab标签页
    public function _views(){
        $mdl_b2c_refund_apply = app::get('b2c')->model('refund_apply');
        $sub_menu = array(
                0 => array('label'=>app::get('base')->_('全部'),'optional'=>false),
                1 => array('label'=>app::get('base')->_('待处理'),'filter'=>array('status'=>'0'),'optional'=>false),
                2 => array('label'=>app::get('base')->_('已拒绝'),'filter'=>array('status'=>'1'),'optional'=>false),
                3 => array('label'=>app::get('base')->_('已退款'),'filter'=>array('status'=>'2'),'optional'=>false),
        );
        foreach($sub_menu as $k=>$v){
            if($k == intval($_GET['view'])){
                //获取显示当前tab文字旁的统计数字
                $sub_menu[$k]['newcount'] = true;
                $sub_menu[$k]['addon'] = $mdl_b2c_refund_apply->count($v['filter']);
            }
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['href'] = 'index.php?app=b2c&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
        }
        return $sub_menu;
    }
    
    //拒绝操作
    public function dorefuse($refund_apply_bn,$order_id){
        $this->begin();
        if ($_POST["p"][0] && $_POST["p"][1]){
            $refund_apply_bn = $_POST["p"][0];
            $order_id = $_POST["p"][1];
        }
        if(!$refund_apply_bn){
            $this->end(false, "无效退款申请");
        }
        $mdl_b2c_refund_apply = app::get('b2c')->model('refund_apply');
        $info_refund_apply = $mdl_b2c_refund_apply->dump(array("refund_apply_bn"=>$refund_apply_bn));
        if(intval($info_refund_apply["status"]) != 0){
            $this->end(false, "已对此退款申请做过操作了");
        }
        //退款申请单处理状态更新为已拒绝1
        $result_refund = $mdl_b2c_refund_apply->update_refund_apply($refund_apply_bn,"1","拒绝退款申请");
        //订单支付状态更新为已支付1
        $mdl_b2c_orders = app::get('b2c')->model('orders');
        $result_order = $mdl_b2c_orders->update(array("pay_status"=>"1"),array("order_id"=>$order_id));
        if($result_refund && $result_order){
            $this->end(true, app::get('b2c')->_('拒绝退款申请成功'));
        }
    }
    
    //退款弹窗
    public function gorefund($refund_apply_bn,$order_id){
        $mdl_b2c_refund_apply = app::get('b2c')->model('refund_apply');
        $info_refund_apply = $mdl_b2c_refund_apply->dump(array("refund_apply_bn"=>$refund_apply_bn));
        if(intval($info_refund_apply["status"]) != 0){
            header('Content-Type:text/jcmd; charset=utf-8');
            echo '{error:"'.app::get('b2c')->_("已对此退款申请做过操作了").'",_:null}';exit;
        }
        
        if (!$order_id){
            header('Content-Type:text/jcmd; charset=utf-8');
            echo '{error:"'.app::get('b2c')->_("订单号传递出错.").'",_:null}';exit;
        }
        
        $this->pagedata['refund_apply_bn'] = $refund_apply_bn;
        $this->pagedata['orderid'] = $order_id;
        $objOrder = $this->app->model('orders');
        $aORet = $objOrder->dump($order_id);
        
        $this->pagedata['payment_id'] = $aORet['payment'];
        $this->pagedata['op_name'] = 'admin';
        
        if ($aORet['member_id']){
            $this->pagedata['typeList'] = array('online'=>app::get('b2c')->_("在线支付"), 'offline'=>app::get('b2c')->_("线下支付"));
        }else{
            $this->pagedata['typeList'] = array('online'=>app::get('b2c')->_("在线支付"), 'offline'=>app::get('b2c')->_("线下支付"));
        }
            
        $this->pagedata['pay_type'] = ($aPayid['pay_type'] == 'ADVANCE' ? 'deposit' : 'offline');
    
        if ($aORet['member_id'] > 0){
            $objPayments = app::get('ectools')->model('refunds');
            $aRet = $objPayments->getAccount();
            $this->pagedata['member'] = $aRet;
        }else{
            $this->pagedata['member'] = array();
        }
        $this->pagedata['order'] = $aORet;
    
        $aAccount = array(app::get('b2c')->_('--使用已存在帐户--'));
        if (isset($aRet) && $aRet){
            foreach ($aRet as $v){
                $aAccount[$v['bank']."-".$v['account']] = $v['bank']." - ".$v['account'];
            }
        }
        $this->pagedata['pay_account'] = $aAccount;
    
        $opayment = app::get('ectools')->model('payment_cfgs');
        $this->pagedata['payment'] = $opayment->getList('*', array('status' => 'true', 'platform'=>array('iscommon','ispc'), 'is_frontend' => true));
    
        $obj_members_point = $this->app->model('member_point');
        $reasons = $obj_members_point->getHistoryReason();
        $arr_return_score = $obj_members_point->db->select("SELECT * FROM ".$obj_members_point->table_name(1)." WHERE member_id=".$aORet['member_id']." AND related_id='".$aORet['order_id']."' AND type='".$reasons['order_refund_use']['type']."' AND reason='".$reasons['order_refund_use']['describe']."'");
        $is_returned_score = 0;
        foreach ((array)$arr_return_score as $arr_is_returned){
            $is_returned_score += abs($arr_is_returned['change_point']);
        }
    
        // 退还订单消费积分
        $this->pagedata['order']['score_g'] = $aORet['score_g'] - $is_returned_score;;
        $this->display('admin/refund/apply/gorefund.html');
    }
    
    /**
     * 退款处理
     * @params null
     * @return null
     */
    public function dorefund(){
        $sdf = $_POST;
        $this->begin();
        if(!$sdf["order_id"] || !$sdf["refund_apply_bn"]){
            $this->end(false, "必要参数缺失");
        }
        $refund_apply_bn = $sdf["refund_apply_bn"];
        $mdl_b2c_refund_apply = app::get('b2c')->model('refund_apply');
        $info_refund_apply = $mdl_b2c_refund_apply->dump(array("refund_apply_bn"=>$refund_apply_bn));
        if(intval($info_refund_apply["status"]) != 0){
            $this->end(false, "已对此退款申请做过操作了");
        }
    
        $obj_checkorder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));
        if (!$obj_checkorder->check_order_refund_apply($sdf['order_id'],$sdf,$message)){
            $this->end(false, $message);
        }
    
        $obj_order = $this->app->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $sdf_order = $obj_order->dump($sdf['order_id'],'*',$subsdf);
        $sdf['score_u'] = $sdf_order['score_u'];
        $sdf['op_id'] = $this->user->user_id;
        $sdf['op_name'] = $this->user->user_data['account']['login_name'];
        $sdf['status'] = 'succ';
        unset($sdf['inContent']);
    
        $objPaymemtcfg = app::get('ectools')->model('payment_cfgs');
        $sdf['payment'] = ($sdf['payment']) ? $sdf['payment'] : $sdf_order['payinfo']['pay_app_id'];
        if ($sdf['payment'] == '-1'){
            $arrPaymentInfo['app_name'] = app::get('b2c')->_("货到付款");
            $arrPaymentInfo['app_version'] = "1.0";
        }else{
            $arrPaymentInfo = $objPaymemtcfg->getPaymentInfo($sdf['payment']);
        }
        
        $refunds = app::get('ectools')->model('refunds');
        $time = time();
        $sdf['refund_id'] = $refund_id = $refunds->gen_id();
        $sdf['pay_app_id'] = $sdf['payment'];
        $sdf['member_id'] = $sdf_order['member_id'] ? $sdf_order['member_id'] : 0;
        $sdf['currency'] = $sdf_order['currency'];
        $sdf['paycost'] = 0;
        $sdf['cur_money'] = $sdf['money'];
        $sdf['money'] = kernel::single('ectools_math')->number_div(array($sdf['cur_money'], $sdf_order['cur_rate']));
        $sdf['t_begin'] = $time;
        $sdf['t_payed'] = $time;
        $sdf['t_confirm'] = $time;
        $sdf['pay_object'] = 'order';
        $sdf['op_id'] = $this->user->user_id;
        $sdf['op_name'] = $this->user->user_data['account']['login_name'];
        $sdf['status'] = 'ready';
        $sdf['app_name'] = $arrPaymentInfo['app_name'];
        $sdf['app_version'] = $arrPaymentInfo['app_version'];
        $obj_refunds = kernel::single("ectools_refund");
        if ($obj_refunds->generate($sdf, $this, $msg)){
            $is_refund_finished = false;
            $obj_refund_lists = kernel::servicelist("order.refund_finish");
            foreach ($obj_refund_lists as $order_refund_service_object){
                $is_refund_finished = $order_refund_service_object->order_refund_finish($sdf, 'succ', 'Back',$msg);
            }
            if ($is_refund_finished){
                // 发送同步日志.
                $order_refund_service_object->send_request($sdf);
                $this->end(true, app::get('b2c')->_('退款成功'));
            }else{
                $this->end(false, $msg);
            }
        }else{
            $this->end(false, $msg);
        }
    }
    
    
    
}
