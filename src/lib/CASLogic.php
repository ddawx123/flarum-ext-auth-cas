<?php
/**
 * phpCAS客户端实现过程封装
 * @package CAS
 * @subpackage Central Authentication
 * @author David Ding
 * @copyright 2012-2017 DingStudio All Rights Reserved
 */

class mCAS {
    /**
     * @var CAS Server
     */
    public static $cas_server_addr = 'cas.dingstudio.cn';
    public static $cas_server_port = '443';
    public static $cas_server_path = 'cas';

    /**
     * CAS互联登录协议
     * @return string
     */
    public function CASLogin() {
        include(dirname(__FILE__).'/CAS-1.3.5/CAS.php');
        //phpCAS::setDebug();
        phpCAS::client(CAS_VERSION_2_0, self::$cas_server_addr, self::$cas_server_port, self::$cas_server_path);
        phpCAS::setNoCasServerValidation();
        phpCAS::handleLogoutRequests();
        if (phpCAS::isAuthenticated()) {
            return phpCAS::getUser();
        }
        else {
            phpCAS::forceAuthentication();
        }
    }

    /**
     * CAS同步退出协议
     * @return null
     */
    public function CASLogout() {
        include(dirname(__FILE__).'/CAS-1.3.5/CAS.php');
        phpCAS::client(CAS_VERSION_2_0, self::$cas_server_addr, self::$cas_server_port, self::$cas_server_path);
        phpCAS::logout( array( 'url' => $_SERVER['HTTP_REFERER']));
        session_destroy();
        session_write_close();
    }
}