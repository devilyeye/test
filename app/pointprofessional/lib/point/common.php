<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class pointprofessional_point_common{
    /**
     * 公开构造方法
     * @params app object
     * @return null
     */
    public function __construct($app){        
        $this->app = $app;
        $this->app_b2c = app::get('b2c');
    }
    
    /* 
     * 处理积分修改相关事宜
     * @param int $member id 用户id 
     * @param int $point 增量积分值 正数
     * @param string $msg 信息提示
     * @param string $reason_type 积分操作类型 可参考b2c_mdl_member_point $aHistoryReason数组的key值
     * @param string type 操作类型 保留原有的type传入 也可参考b2c_mdl_member_point的type说明
     * @param string $rel_id 关联的对象id
     * @param string $operator 操作员id
     * @param string $remark 任务类型（任务临时表用到）
     * @param bool $api_action_type 默认true在更新接口失败的情况下不终止业务 false终止业务
     */
    public function point_change_action($member_id,$point,&$msg,$reason_type,$type=0,$rel_id,$operator,$remark='pay',$api_action_type=true){
        //开启事务
        $db = kernel::database();
        $transaction_status = $db->beginTransaction();
        
        //检查
        $check_rs = $this->prev_check($member_id,$point,$msg,$reason_type,$type,$rel_id);
        if(!$check_rs){
            return false;
        }
        
        $mdl_members = $this->app->model('members');
        $mdl_member_point = $this->app->model('member_point');
        
        $memo = $msg ? $msg : '';//备注
        $is_delay = false; //是否延时标记
        
        //直接拿本地数据 因为后续不做本地point字段不更新 只记录流水 
        $member_point = $mdl_members->getRow('point',array('member_id'=>$member_id));
        $real_total_point = $member_point["point"];
        
        if ($point<0){
            if ($remark == 'refund'){
                if ($real_total_point < abs($point)){
                    $point = 0 - $real_total_point;
                    $real_total_point = 0;
                    $db->rollback();$msg = app::get('b2c')->_("积分扣除超过会员已有积分");return false;
                }else{
                    $real_total_point = $real_total_point + $point;
                }
            }else{
                if ($real_total_point < abs($point)){
                    $db->rollback();$msg = app::get('b2c')->_("积分扣除超过会员已有积分");return false;
                }else{
                    $real_total_point = $real_total_point + $point;
                }
            }
        }else{
            $site_get_point_interval_time = $this->app_b2c->getConf('site.get_point_interval_time');
            if ($site_get_point_interval_time > 0){
                // 存入任务临时表
                $mdl_member_point_task = $this->app->model('member_point_task');
                $obj_pointprofessional_point_task_datas = kernel::servicelist('pointprofessional_point_task_data');
                if ($obj_pointprofessional_point_task_datas){
                    $arr_point_task = array();
                    foreach ($obj_pointprofessional_point_task_datas as $obj_service){
                        if ($obj_service->get_point_task_type() == $remark){
                            $time = time();
                            $arr_data = array(
                                    'member_id' => $member_id,
                                    'task_name' => app::get('b2c')->_('订单获得积分'),
                                    'point' => $point,
                                    'addtime' => $time,
                                    'enddate' => $time + $site_get_point_interval_time * 24 * 3600,
                                    'related_id' => $rel_id,
                                    'operator' => $operator,
                            );
                            $obj_service->generate_data($arr_data, $arr_point_task);
                            $mdl_member_point_task->insert($arr_point_task);
                            $is_delay = true;
                        }
                    }
                }
            }
            $real_total_point = $real_total_point + $point;
        }
        
        //不延时
        if (!$is_delay){
            if($point) $change_point = $point;
            $newValue = $real_total_point;
            $sdf_member = $mdl_members->dump($member_id,'*');
            $sdf_member['score']['total'] = $newValue;
            // 取到此会员等级对应的
            $mdl_member_lv = $this->app_b2c->model('member_lv');
            $rows = $mdl_member_lv->getList('*', array('member_lv_id'=>$sdf_member['member_lv']['member_group_id']));
            $default_expired = $this->app_b2c->getConf('site.point_expired_value');
            $time = time();
            if ($rows){
                $site_point_expired = $this->app_b2c->getConf('site.point_expired');
                $site_point_expried_method = $this->app_b2c->getConf('site.point_expried_method');
                if ($site_point_expired == 'true'){
                    //如果积分过期方式是按照日期，且过期的时间小于等于1990-01-01的时间戳,大于0。则积分过期时间为0
                    if( $site_point_expried_method == '1' && $rows[0]['expiretime'] <= 631123200 && $rows[0]['expiretime'] > 0 ){
                        $rows[0]['expiretime'] = 0;
                        $mdl_member_lv->update(array('expiretime'=>strtotime($default_expired)),array('member_lv_id'=>$sdf_member['member_lv']['member_group_id']));
                    }
                    //如果积分过期方式是按照长度，且过期的时间大于1990-01-01的时间戳。则积分过期时间为0
                    if( $site_point_expried_method == '2' && $rows[0]['expiretime'] > 631123200 ){
                        $rows[0]['expiretime'] = 0;
                        $mdl_member_lv->update(array('expiretime'=>$default_expired),array('member_lv_id'=>$sdf_member['member_lv']['member_group_id']));
                    }
                    switch ($site_point_expried_method){
                        case '1':
                            $expired_time = $rows[0]['expiretime'] ? $rows[0]['expiretime'] : strtotime($default_expired);
                            break;
                        case '2':
                            $expired_time = $time + ($rows[0]['expiretime'] ? $rows[0]['expiretime'] : $default_expired) * 24 * 3600;
                            break;
                        default:
                            $expired_time = $rows[0]['expiretime'] ? $rows[0]['expiretime'] : strtotime($default_expired);
                            break;
                    }
                }
            }
        
            $reasons = $mdl_member_point->getHistoryReason();
            $reason = $reasons[$reason_type];
            $remark = $pointInfo['modify_remark'];
            $sdf_point = array(
                    'member_id'=>$member_id,
                    'point'=>$newValue,
                    'change_point'=>$change_point,
                    'addtime'=>$time,
                    'expiretime'=>(isset($expired_time) && $expired_time) ? $expired_time : '0',
                    'reason'=>$reason['describe'],
                    'type'=>$type,
                    'related_id'=>($rel_id) ? $rel_id : 0,
                    'operator' => $operator,
                    'remark'=>$memo ? $memo : '',
            );
            $point_id = $mdl_member_point->insert($sdf_point);
        
            if($point_id){
                $nodes_obj = $this->app_b2c->model('shop');
                $nodes = $nodes_obj->count( array('node_type'=>'ecos.taocrm','status'=>'bind'));
                //20170328有更改过如果符合“注册赠送积分”条件 在绑定crm的情况下取消了更新积分接口的请求 只做日志的记录 积分更新实际已在store.user.add加了字段 做了2合1操作
                if ($nodes>0 && $reason_type!="register_score"){
                    // 绑定了crm 打更新积分流水接口
                    $rpc_result = kernel::single('b2c_member_point_contact_crm')->pointChange($point_id);
                    if(!$rpc_result && !$api_action_type){
                        $db->rollback();
                        $msg = app::get('b2c')->_("接口更新积分失败");
                        return false;
                    }
                }
                //绑定crm后积分的使用由crm来处理 
                if ($point<0 && $nodes <= 0){
                    //未绑定crm 得到所有有效的可用积分记录
                    $arr_point_historys = $mdl_member_point->get_usable_point($member_id);
                    if ($arr_point_historys){
                        $discount_point = abs($point);
                        foreach ($arr_point_historys as $arr_points){
                            // 已经消耗完的积分不在处理.
                            if ($arr_points['change_point'] == $arr_points['consume_point'])
                                continue;
    
                            if ($arr_points['change_point'] >= ($arr_points['consume_point'] + $discount_point)){
                                $arr_points['consume_point'] = $arr_points['consume_point'] + $discount_point;
                                $mdl_member_point->update($arr_points, array('id'=>$arr_points['id']));
                                break;
                            }else{
                                $real_change_point = $arr_points['change_point'] - $arr_points['consume_point'];
                                $arr_points['consume_point'] = $arr_points['change_point'];
                                $discount_point = $discount_point - $real_change_point;
                                $mdl_member_point->update($arr_points, array('id'=>$arr_points['id']));
                            }
                        }
                    }
                }
            
                //在绑定crm的情况下 
                //不更新当前的用户的积分和等级 打积分更新接口的时候不做本地数据更新 
                //这个步骤统一在/b2c/lib/apiv/apis/response/member/point.php update_point相应接口由crm推过来再做更新
                if($nodes<=0){
                    //没有绑定crm 更新当前的用户的积分和等级
                    if($this->app->getConf('site.level_switch')== 0){
                        $sdf_member['member_lv']['member_group_id'] = $mdl_member_point->member_lv_chk($member_id,$sdf_member['member_lv']['member_group_id'],$newValue);
                    }
                    $mdl_members->save($sdf_member);
                }
                
                $db->commit($transaction_status);
                
                $msg = app::get('b2c')->_("积分更新成功");
                return true;
            }else{
                $db->rollback();
                $msg = app::get('b2c')->_("积分更新失败");
                return false;
            }
        }else{
            //有延时
            $db->commit($transaction_status);
            $msg = app::get('b2c')->_("任务临时表积分更新记录保存成功");
            return true;
        }
    }
    
    /*
     * 处理积分修改前检查
     * 入参参考point_change_action方法
     */
    private function prev_check($member_id,$point,&$msg,$reason_type,$type,$rel_id){
        //是否开启积分判断
        if($this->app->getConf('site.get_policy.method') == 1){
            $msg = "选择了不使用积分";
            return false;
        }
        //判断积分值
        if(!is_numeric($point)||strpos($point,".")!==false){
            $msg = "请输入整数值";
            return false;
        }
        /** 检查是否并发-部分 **/
        $tmp = array();
        if ($rel_id){
            //积分流水表
            $mdl_member_point = $this->app_b2c->model('member_point');
            if ($point > 0 && $reason_type != 'operator_adjust'){
                $filter = array(
                        'member_id' => $member_id,
                        'related_id' => $rel_id,
                        'type'=>$type,
                );
                $tmp = $mdl_member_point->getList('*', $filter);
            }
            if ($point < 0 && $reason_type == 'order_pay_use'){
                $reasons = $mdl_member_point->getHistoryReason();
                $filter = array(
                        'member_id' => $member_id,
                        'related_id' => $rel_id,
                        'type'=>$type,
                        'reason'=>$reasons[$reason_type],
                );
                $tmp = $mdl_member_point->getList('*', $filter);
            }
        }
        if (!empty($tmp)){
            $msg = "已有此积分更新流水记录";
            return false;
        }
        //最后返回true通过
        return true;
    }
    
    //统一检查打获取积分值是否成功（包括绑定crm或不绑定crm的两种情况）
    public function check_used_point_get($member_id){
        $return_arr = array("rs"=>false); 
        $mdl_members = $this->app->model('members');
        //在绑定crm的情况下打查询接口获取point 不成功则返回失败提示api_get_point_fail（get_real_point方法里面有区分是否绑定crm）
        $real_total_point = $mdl_members->get_real_point($member_id,3);
        //这里统一判断（包括绑定或者不绑定crm的情况） 积分值是数字说明积分查询成功
        if(is_numeric($real_total_point)){
            $return_arr = array("rs"=>true,"real_point"=>$real_total_point);
        }
        //如果是api_get_point_fail说明在绑定crm的情况下查询积分失败。
//         if($real_total_point != "api_get_point_fail"){
//             $return_arr = array("rs"=>true,"real_point"=>$real_total_point);
//         }
        return $return_arr;
    }
  
}
