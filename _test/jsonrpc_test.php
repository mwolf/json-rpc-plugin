<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of jsonrpc_test
 *
 * @author mwolf
 */
require_once ('../jsonrpc.php');

class jsonrpc_test extends UnitTestCase{

    function testChecAuthAllowAll()
    {
        global $conf;
        global $USERINFO;

        $conf['plugin']['jsonrpc']['allow_all'] = 1;
        $this->assertTrue(checkAuth(), "Failure checkAuth, allow_all");
    }
    
    function testAuthCheckAllowUser()
    {
        $conf['plugin']['jsonrpc']['allow_all'] = 0;
        $conf['plugin']['jsonrpc']['allowed'] = 'testuser';
        $_SERVER['REMOTE_USER'] = 'testuser';

        $this->assertTrue(checkAuth(), "Failer checkAuth, allow testuser");
    }

    function testAuthCheckNotAllowed()
    {
        $conf['plugin']['jsonrpc']['allow_all'] = 0;
        $conf['plugin']['jsonrpc']['allowed'] = '';

        $_SERVER['REMOTE_USER'] = 'testuser';

        $this->assertFalse(checkAuth(), "Failer checkAuth, notallow testuser");
    }
    
}

?>
