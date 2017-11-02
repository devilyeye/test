<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

define('QUEUE_SCHEDULE', 'system_queue_adapter_mysql');
define('DEFAULT_PUBLISH_QUEUE', 'normal');
#define('QUEUE_CONSUMER', 'fork');

$bindings = array(
    'crontab:b2c_tasks_cleancartobject' => array('queue_name'=>'slow'),
    'crontab:site_tasks_createsitemaps' => array('queue_name'=>'slow'),
    'crontab:ectools_tasks_statistic_day' => array('queue_name'=>'slow'),
    'crontab:ectools_tasks_statistic_hour' => array('queue_name'=>'slow'),
    'crontab:base_tasks_cleankvstore' => array('queue_name'=>'slow'),
    'crontab:operatorlog_tasks_cleanlogs' => array('queue_name'=>'slow'),
    'crontab:apiactionlog_tasks_cleanexpiredapilog' => array('queue_name'=>'slow'),
    #'crontab:archive_tasks_partition' => array('queue_name'=>'slow'),
    
    # 'crontab:b2c_tasks_archive' => array('queue_name'=>'slow'),

    'b2c_tasks_matrix_sendorders' => array('queue_name'=>'quick'),
    'b2c_tasks_matrix_sendpayments' => array('queue_name'=>'quick','wait_time'=>'5'),
    'b2c_tasks_sendemail' => array('queue_name'=>'quick'),
    'b2c_tasks_sendsms' => array('queue_name'=>'quick'),
    'b2c_tasks_sendmsg' => array('queue_name'=>'quick'),
    'desktop_tasks_runimport' => array('queue_name'=>'normal'),
    'desktop_tasks_turntosdf' => array('queue_name'=>'normal'),
    'emailbus_tasks_sendemail' => array('queue_name'=>'slow'),
    'image_tasks_imagerebuild' => array('queue_name'=>'normal'),
    'recommended_tasks_update' => array('queue_name'=>'slow'),
    'importexport_tasks_runexport'=>array('queue_name'=>'slow'),
    'importexport_tasks_runimport'=>array('queue_name'=>'slow'),
    'b2c_tasks_sendmessenger'=>array('queue_name'=>'quick'),
    # 'b2c_tasks_order_finish'=>array('queue_name'=>'normal'),

    // 订单归档相关

    'aftersales_tasks_archive_returnProduct' => array('queue_name'=>'slow'),

    'other' => array('queue_name'=>'other'),
);

$queues = array(
    'slow' => array(
        'title' => 'slow queue',
        'thread' => 3),
    'quick' => array(
        'title' => 'quick queue',
        'thread' => 5),
    'normal' => array(
        'title' => 'normal queue',
        'thread' => 3));
