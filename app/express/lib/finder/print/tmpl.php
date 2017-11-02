<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class express_finder_print_tmpl{

    var $addon_cols='prt_tmpl_type';
    var $column_edit = '操作';
    var $column_edit_width = "200";
    function column_edit($row){  
        $tmpl_type = $row[$this->col_prefix.'prt_tmpl_type'];
        //普通面单
        if($tmpl_type == 'normal'){
            $html = "<a target='_blank' href=index.php?app=express&ctl=admin_delivery_printer&act=edit_tmpl&p[0]=".$row['prt_tmpl_id']."&finder_id=".$_GET['_finder']['finder_id'].">".app::get('express')->_('编辑')."</a> ";
            $html.= "<a target='_blank' href=index.php?app=express&ctl=admin_delivery_printer&act=add_same&p[0]=".$row['prt_tmpl_id']."&finder_id=".$_GET['_finder']['finder_id'].">".app::get('express')->_('添加相似单据')."</a> ";
        }else{
            //电子面单
            $html = "<a target='_blank' href=index.php?app=express&ctl=admin_delivery_printer&act=edit_electron_tmpl&p[0]=".$row['prt_tmpl_id']."&finder_id=".$_GET['_finder']['finder_id'].">".app::get('express')->_('编辑')."</a> ";
            $html.= "<a target='_blank' href=index.php?app=express&ctl=admin_delivery_printer&act=copy_electron_tmpl&p[0]=".$row['prt_tmpl_id']."&finder_id=".$_GET['_finder']['finder_id'].">".app::get('express')->_('添加相似单据')."</a> ";
        }

        $html.= "<a target='download' href=index.php?app=express&ctl=admin_delivery_printer&act=download&p[0]=".$row['prt_tmpl_id'].">".app::get('express')->_('下载模板')."</a> ";
        return $html;
    }
}