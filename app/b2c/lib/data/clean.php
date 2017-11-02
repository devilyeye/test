<?php 
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 *
 */
class b2c_data_clean
{
    
    /*
     * 清楚初始化数据
     */
    public function clean()
    {
        app::get('b2c')->model('goods')->delete( array() );
        app::get('b2c')->model('goods_cat')->delete( array() );
        app::get('b2c')->model('goods_cat')->cat2json();
        app::get('b2c')->model('goods_type')->delete( array() );
        app::get('b2c')->model('brand')->delete( array() );
        app::get('b2c')->model('brand')->brand2json();
        $obj_gift_mdl_ref = kernel::service("desktop_finder.gift_mdl_ref");
        if(isset($obj_gift_mdl_ref) && is_object($obj_gift_mdl_ref)){
            app::get('gift')->model('ref')->delete( array() );
        }
        kernel::single("base_initial", "b2c")->init();
    }
    #End Func
}