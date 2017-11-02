<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class penker_finder_bind{
    var $column_control = '操作';
    
    public function __construct($app) {
        $this->app = $app;
    }
    
    function column_control($row){
        if($row['status'] == 1){
            return '<a href="index.php?app=penker&ctl=admin_penker&act=bind" target="blank">'.app::get('penker')->_('解绑').'</a>';
        }else{
            //return '<p style = "color:#DCDCDC">'.app::get('penker')->_('解绑').'</p>';
        }
        
    }
    
}
