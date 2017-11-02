<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class b2c_member_point_add
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
     * @param int type 类型值
     * @param string rel_id - 对象id
     * @param int operator 操作员id
     * @param string reason 原因
     */
    public function change_point($member_id=0, $score, &$message, $usage, $type, $rel_id, $operator, $reason='pay')
    {
        $policy_method = $this->app->getConf("site.get_policy.method");
        $objPoint = $this->app->model('member_point');
            
        if ($policy_method > 1)
        {
            if (isset($score) && $score > 0)
            {   
                //做积分的处理
                $obj_order_operations = kernel::servicelist('b2c.order_point_operaction');
                if ($obj_order_operations)
                {
                    $arr_data = array(
                        'member_id' => $member_id,
                        'score_g' => $score,
                        'rel_id' => $rel_id,
                    );
                    foreach ($obj_order_operations as $obj_operation)
                    {
                        $obj_operation->gen_member_point($arr_data, $reason);
                    }
                }
				
				// 获得积分 
                kernel::single('pointprofessional_point_common')->point_change_action($member_id,$score,$message,$usage,$type,$rel_id,$operator,$reason);
            }
        }
    }
}