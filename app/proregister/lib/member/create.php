<?php 
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 *
 *
 * @package default
 * @author kxgsy163@163.com
 */
class proregister_member_create
{
    function __construct( &$app )
    {
        $this->app = $app;
    }
    /**
     * 注册后触发，如满足注册营销的配置条件，则给于相应的优惠：送券｜送积分
     *
     * @param int $member_id
     * @return void
     */
    public function registerActive( $member_id ) {
        $setting = $this->registerActiveCheck();
        if (!$setting){
            return true; //不符合条件 直接返回
        }
        
        //送优惠券
        if( $setting['getcoupon'] ) {  
            kernel::single('proregister_promotion_getcoupon')->promotion( $member_id,$setting['getcoupon'] );
        }
        
        //送积分  20170328有更改过如果符合注册送积分条件 在绑定crm的情况下取消了更新积分接口的请求 只做日志的记录 积分更新实际已在store.user.add加了字段 做了2合1操作
        if( $setting['getscore'] ) {  
            kernel::single('proregister_promotion_getscore')->promotion( $member_id,$setting['getscore'] );
        }
    }
    
    /*
     * 检查注册后是否符合条件做相应的送优惠券和送积分操作
     * 返回值 $setting符合条件 false不符合条件
     */
    public function registerActiveCheck(){
        $o = kernel::single("proregister_setting");
        $setting = $o->getSetting();
        $current_time = time();
        if( $current_time < $setting['stime'] ){
            return false;//小于开始时间
        }
        if( $current_time > $setting['etime'] ){
            return false;//大于结束时间
        }
        if(!$o->checkStatus()){
            return false;//活动未开启
        }
        return $setting;
    }
    
}
