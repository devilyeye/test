<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
$db['sms_send_log']=array (
  'columns' => 
  array (
    'sms_id' => 
    array (
        'type' => 'number',
        'required' => true,
        'pkey' => true,
        'label' => 'id',
        'editable' => false,
        'extra' => 'auto_increment',
        'comment' => app::get('b2c')->_('sms_id'),
    ),
    'phone' =>
    array (
      'type' => 'longtext',
      'label' => app::get('b2c')->_('手机号码'),
      'editable' => false,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'is_title' => true,
      'comment' => app::get('b2c')->_('手机号码'),
    ),
    'status' =>
    array (
      'type' =>
      array (
        0 => app::get('b2c')->_('失败'),
        1 => app::get('b2c')->_('成功'),
        2 => '等待发送',
        3 => '发送中',
      ),
      'default' => '2',
      'required' => true,
      'label' => app::get('b2c')->_('发送状态'),
      'order' => 30,
      'width' => 40,
      'editable' => true,
      'filtertype' => 'yes',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'msg' =>
    array (
      'type' => 'varchar(50)',
      'label' => app::get('b2c')->_('结果'),
      'editable' => true,
      'filtertype' => 'normal',
      'filterdefault' => 'true',
      'in_list' => true,
      'is_title'=>true,
      'default_in_list' => false,
      'default' => ' ',
      'comment' => app::get('b2c')->_('结果'),
    ),
    'message' =>
    array (
      'type' => 'varchar(250)',
      'label' => app::get('b2c')->_('发送内容'),
      'editable' => true,
      'filtertype' => 'normal',
      'filterdefault' => 'true',
      'in_list' => true,
      'is_title'=>true,
      'default_in_list' => false,
      'comment' => app::get('b2c')->_('发送内容'),
    ),
    'send_time' =>
    array (
      'label' => app::get('b2c')->_('发送时间'),
      'width' => 75,
      'type' => 'time',
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'comment' => app::get('b2c')->_('发送时间'),
    ),
  ),
  
  'comment' => app::get('b2c')->_('短信发送记录表'),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
