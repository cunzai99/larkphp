<?php
/**
 * 框架配制文件
 * 路由配制文件
 *
 * @category    Lark
 * @package     Lark_core
 * @author      cunzai99 <cunzai99@gmail.com>
 * @version     $Id: router.php v1.2.0 2013-04-01 cunzai99 $
 * @link        http://www.larkphp.com
 * @license
 * @copyright
 */

$default_app        = defined('DEFAULT_APP')        ? DEFAULT_APP        : 'demo';
$default_controller = defined('DEFAULT_CONTROLLER') ? DEFAULT_CONTROLLER : 'index';
$default_action     = defined('DEFAULT_ACTION')     ? DEFAULT_ACTION     : 'index';

$router_config = array(
        'rewrite' => true,
        'base' => array(
                'basedomain'     => __HTTP_HOST__,
                'baseport'       => '',   //80可为空
                'baseapp'        => $default_app,
                'basecontroller' => $default_controller,
                'baseaction'     => $default_action,
                'domain'         => '[domain:]',
                'port'           => '[port:]',
                'app'            => '[app:]',
                'controller'     => '[controller:]',
                'action'         => '[action:]',
            ),
        'router' => array(
                'defaultrule' => array(
                                    '[domain:]/[app:]/[controller:]/[action:]',
                                    '[domain:]/[app:]/[controller:]',
                                    '[domain:]/[app:]'
                                ),

                'rule' => array(
                                    // '[domain:]/index.php?app=[app:]&controller=[controller:]&action=[action:]&',
                                    // '[domain:]/[app:/][controller:].html',
                                    // '[domain:]/[app:].html',
                                    // '[domain:]/[app:]-[controller:]-[action:].html'
                                )
        )

);
