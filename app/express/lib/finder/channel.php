<?php
class express_finder_channel{

    var $addon_cols = "channel_id,status,channel_type,logistics_code,shop_id,bind_status";

    var $column_control = '操作';
    var $column_control_width = '60';
    var $column_control_order = COLUMN_IN_HEAD;
    function column_control($row){
        $channel_id = $row[$this->col_prefix.'channel_id'];
        $button = "<a href='javascript:void(0);' onclick=\"new Dialog('index.php?app=express&ctl=admin_channel&act=edit&p[0]={$channel_id}&finder_id={$_GET[_finder][finder_id]}',{width:620,height:260,title:'来源添加/编辑'}); \">编辑</a>";
        return $button;
    }

    var $column_channel_type = '来源类型';
    var $column_channel_type_width = '80';
    var $column_channel_type_order = COLUMN_IN_TAIL;
    function column_channel_type($row){
        $funcObj = kernel::single('express_waybill_func');
        $channel_type = $row[$this->col_prefix.'channel_type'];
        $channels = $funcObj->channels($channel_type);
        if($channels) {
            return $channels['name'];
        } else {
            return '未知';
        }
    }

    var $column_logistics = '物流公司';
    var $column_logistics_width = '80';
    var $column_logistics_order = COLUMN_IN_TAIL;
    function column_logistics($row){
        $channel_type = $row[$this->col_prefix.'channel_type'];
        if ($channel_type && class_exists('express_waybill_'.$channel_type)) {
            $wlbObj = kernel::single('express_waybill_'.$channel_type);
            $logistics_code = $row[$this->col_prefix.'logistics_code'];
            $logistics = $wlbObj->logistics($logistics_code);
        }

        if($logistics) {
            return $logistics['name'];
        } else {
            return '未知';
        }
    }

    var $column_waybillnum = '本地可用';
    var $column_waybillnum_width = '80';
    var $column_waybillnum_order = COLUMN_IN_TAIL;
    function column_waybillnum($row){
        $waybillObj = app::get('express')->model('waybill');
        $filter = array('status'=>0);
        $filter['channel_id'] = $row[$this->col_prefix.'channel_id'];
        $filter['logistics_code'] = $row[$this->col_prefix.'logistics_code'];
        $count = $waybillObj->count($filter);
        return "<span class=show_list channel_id=".$filter['channel_id']." billtype='active'><a >".$count."</a></span>";
    }

    /**
     * 作废物流单号.
     * @param  
     * @return  
     * @access  
     * @author sunjing@shopex.cn
     */
    var $column_recycle_waybill='本地作废';
    var $column_recycle_waybill_width = '80';
    function column_recycle_waybill($row)
    {
        $waybillObj = app::get('express')->model('waybill');
        $filter = array('status'=>2);
        $filter['channel_id'] = $row[$this->col_prefix.'channel_id'];
        $filter['logistics_code'] = $row[$this->col_prefix.'logistics_code'];
        $count = $waybillObj->count($filter);
        return "<span class='show_list' channel_id=".$filter['channel_id']." billtype='recycle' ><a >".$count."</a></span>";
    }

    /**
     * 作废物流单号.
     * @param  
     * @return  
     * @access  
     * @author sunjing@shopex.cn
     */
    var $column_use_waybill='本地已用';
    var $column_use_waybill_width = '80';
    function column_use_waybill($row)
    {
        $waybillObj = app::get('express')->model('waybill');
        $filter = array('status'=>1);
        $filter['channel_id'] = $row[$this->col_prefix.'channel_id'];
        $filter['logistics_code'] = $row[$this->col_prefix.'logistics_code'];
        $count = $waybillObj->count($filter);
        return "<span class='show_list' channel_id=".$filter['channel_id']." billtype='used' ><a >".$count."</a></span>";
    }

}
