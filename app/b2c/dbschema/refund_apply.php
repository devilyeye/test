<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
$db['refund_apply']=array (
    'columns' =>
        array (
            'apply_id' =>
                array (
                    'type' => 'number',
                    'required' => true,
                    'pkey' => true,
                    'extra' => 'auto_increment',
                    'editable' => false,
                ),
            'refund_apply_bn' =>
                array (
                    'type' => 'varchar(32)',
                    'required' => true,
                    'default' => '',
                    'label' => app::get('b2c')->_('退款申请单号'),
                    'width' => 150,
                    'editable' => false,
                    'default_in_list' => true,
                    'in_list' => true,
                    'is_title' => true,
                    'searchtype' => 'has',
                    'order' => 5,
                ),
            'order_id' =>
                array (
                    'type' => 'table:orders@b2c',
                    'required' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'label' => app::get('b2c')->_('订单号'),
                    'editable' => false,
                    'searchtype' => 'has',
                    'order' => 10,
                ),
            'member_id' =>
                array (
                    'type' => 'table:members@pam',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => false,
                    'label' => app::get('b2c')->_('会员用户ID'),
                ),
            'status' =>
                array (
                    'type' =>
                        array (
                            0 => '待处理',
                            1 => '已拒绝',
                            2 => '已退款',
                        ),
                    'default' => '0',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'label' => app::get('b2c')->_('处理状态'),
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'order' => 30,
                ),
            'money' =>
                array (
                    'type' => 'money',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'label' => app::get('b2c')->_('申请退款金额'),
                    'width' => '90',
                    'order' => 15,
                ),
            'refunded' =>
                array (
                    'type' => 'money',
                    'default' => '0',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'label' => app::get('b2c')->_('已退款金额'),
                    'width' => '90',
                    'order' => 20,
                ),
            'refunds_reason' =>
                array (
                    'type' => 
                        array(
                            1 => app::get('b2c')->_('不想要了'),
                            2 => app::get('b2c')->_('价格偏贵'),
                            3 => app::get('b2c')->_('缺货'),
                            4 => app::get('b2c')->_('等待时间过长'),
                            5 => app::get('b2c')->_('拍错了'),
                            6 => app::get('b2c')->_('订单信息填写错误'),
                            7 => app::get('b2c')->_('其它'),
                        ),
                    'default' => '1',
                    'required' => true,
                    'editable' => false,
                    'width' => 150,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                    'label' => app::get('b2c')->_('退款原因'),
                    'order' => 25,
                ),
            'memo' =>
                array (
                    'type' => 'text',
                    'editable' => false,
                    'in_list' => false,
                    'default_in_list' => false,
                    'label' => app::get('b2c')->_('备注'),
                ),
            'create_time' =>
                array (
                    'type' => 'time',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'label' => app::get('b2c')->_('申请时间'),
                    'width' => 130,
                    'filtertype' => 'time',
                    'filterdefault' => true,
                ),
            'last_modified' =>
                array (
                    'label' => app::get('b2c')->_('最后更新时间'),
                    'type' => 'last_modify',
                    'width' => 130,
                    'editable' => false,
                    'in_list' => true,
                ),
            'disabled' =>
                array (
                    'type' => 'bool',
                    'required' => true,
                    'default' => 'false',
                    'editable' => false,
                ),
        ),
    'index' =>
        array (
            'ind_refund_apply_bn' =>
                array (
                    'columns' =>
                        array (
                            0 => 'refund_apply_bn',
                        ),
                ),
            'ind_order_id' =>
                array (
                    'columns' =>
                        array (
                            0 => 'order_id',
                        ),
                ),
        ),
    'engine' => 'innodb',
    'comment' => app::get('b2c')->_('退款申请表'),
    'version' => '$Rev:  $',
);