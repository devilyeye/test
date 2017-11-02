<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class b2c_member_point_reducte
{
    /**
     * 公开构造方法
     * @params app object
     * @return null
     */
    public function __construct($app)
    {        
        $this->app = $app;
    }
    
    /**
     * 增加积分
     * @param string member id
     * @param int score 需要变化的积分值
     * @param string message 引用值
     * @param string useage 用途
     * @param int type 状态值
     * @param string rel_id - 对象id
     * @param int operator 操作员id
     * @param string reason 原因
     */
    public function change_point($member_id=0, $score, &$message, $usage, $type, $rel_id, $operator, $reason='pay')
    {
        $policy_method = $this->app->getConf("site.get_policy.method");
        $objPoint = $this->app->model('member_point');
		$is_save = true;
        
        if ($policy_method > 1)
        { 
            if (isset($score) && $score != 0)
            {
                // 使用的积分 
                $is_save = kernel::single('pointprofessional_point_common')->point_change_action($member_id,$score,$message,$usage,$type,$rel_id,$operator,$reason,false);
                
                //积分变动基本数组
                $arr_data = array(
                        'member_id' => $member_id,
                        'score_u' => $score,
                        'rel_id' => $rel_id,
                );
                //使用积分失败(这里积分查询和更新的两个接口都是成功的情况)
				if (!$is_save){
				    $reason = "pay_fail";
				}
				//积分做相应的修改 成功$reason是pay 失败$reason是pay_fail
				$obj_order_operations = kernel::servicelist('b2c.order_point_operaction');
				if($obj_order_operations){
				    foreach ($obj_order_operations as $obj_operation){
				        $obj_operation->gen_member_point($arr_data, $reason);
				    }
				}
				//使用积分失败
				if ($reason == "pay_fail"){
				    return false;
				}
            }
        }
		
		return true;
    }
}