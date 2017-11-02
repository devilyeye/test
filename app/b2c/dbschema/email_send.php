<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

$db['email_send']=array (
  'columns' =>
  array (
    'email' =>
    array (
      'type' => 'varchar(100)',
      'label' => app::get('b2c')->_('email'),
      'pkey' => true,
      'editable' => true,
      'filtertype' => 'normal',
      'filterdefault' => 'true',
      'in_list' => true,
      'is_title'=>true,
      'default_in_list' => false,
      'default' => ' ',
      'comment' => app::get('b2c')->_('email'),
    ),
    'send_time' =>
    array (
      'label' => app::get('b2c')->_('申请时间'),
      'width' => 75,
      'type' => 'time',
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'comment' => app::get('b2c')->_('申请时间'),
    ),
  ),

  'comment' => app::get('b2c')->_('邮箱发送检查'),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
