<?php
/**
 * Description of IJR_Server
 *
 * @author  Andreas Gohr <andi@splitbrain.org>
 * @author Magnus Wolf <mwolf2706@googlemail.com>
 */
class IJR_Server {

    var $data;
    var $callbacks = array();
    var $message;
    var $capabilities;
    var $method;
    var $id;


    function IJR_Server($callbacks = false, $data = false)
    {
        $this->setCapabilities();
        if ($callbacks) {
            $this->callbacks = $callbacks;
        }
        $this->setCallbacks();
        $this->serve($data);
    }


    private function verifyData($data)
    {
        if($data == NULL){
            $this->error(-32700, 'decode error. no correct json string');
        }

        if(!$data['method']){
        	$this->error(-32600, 'server error. invalid json-rpc. not conforming to spec. Request must be a method');
        }

        if(!$data['jsonrpc'] || $data['jsonrpc']!='2.0'){
            $this->error(-32600, 'server error. invalid json-rpc. wrong jsonrpc-spec 2.0 is available');
        }
    }

    function serve($data = false)
    {
        if (!$data) {
            global $HTTP_RAW_POST_DATA;
            if (!$HTTP_RAW_POST_DATA) {
               die('JSON-RPC server accepts POST requests only.');
            }
            $data = $HTTP_RAW_POST_DATA;
        }

        $data = json_decode($data, true);
        $this->verifyData($data);
        $this->id = $data['id'];
        $parameter = array();

        foreach($data['params'] as $param){
            foreach($param as $key => $value){
        	$parameter[] = $value;
            }
        }

        $res = $this->call($data['method']['methodName'], $parameter);
        if (is_a($res, 'IJR_Error')) {
            $this->error($res);
        }

        // Send it
        $result['jsonrpc']='2.0';
        $result['result']=$res;
        $result['error']='';
        $result['id']=$this->id;
        $result = json_encode($result);
        $this->output($result);
    }


    private function callClassMethod($method, $args)
    {
        $method = substr($method, 5);
        if (!method_exists($this, $method)) {
            return new IJR_Error(-32601, 'server error. requested class method "'.$method.'" does not exist.');
        }
        return call_user_func_array(array(&$this,$method),$args);
    }


    private function callPlugin($pluginname, $callback, $method, $args)
    {
        require_once(DOKU_INC.'inc/pluginutils.php');
        list($pluginname, $callback) = explode(':', substr($method, 7), 2);
        if(!plugin_isdisabled($pluginname))
        {
            $plugin = plugin_load('action', $pluginname);
            return call_user_func_array(array($plugin, $callback), $args);
        }
        else
        {
            return new IJR_Error(-99999, 'server error');
        }
    }


    private function callFunction($method, $args)
    {
        if (!function_exists($method))
        {
            return new IJR_Error(-32601, 'server error. requested function "'.$method.'" does not exist.');
        }
        return call_user_func_array($method,$args);
    }


    protected function call($methodname, $args)
    {
        if (!$this->hasMethod($methodname))
        {
            return new IJR_Error(-32601, 'server error. requested method '.$methodname.' does not exist.');
        }
        $method = $this->callbacks[$methodname];
        // Perform the callback and send the response
# Adjusted for DokuWiki to use call_user_func_array
        // args need to be an array
        $args = (array) $args;
        if (substr($method, 0, 5) == 'this:') 
        {
            $result = $this->callClassMethod($method, $args);
        }
        elseif (substr($method, 0, 7) == 'plugin:')
        {
            return $this->callPlugin($pluginname, $callback, $method, $args);
        } 
        else
        {
            $result = $this->callFunction($method, $args);
        }
        return $result;
    }


    function error($error, $message = false)
    {
        if ($message && !is_object($error)) {
            $error = new IJR_Error($error, $message);
        }
        $result['jsonrpc']='2.0';
        $result['result']='';
        $result['error']=$error->getJson();
        $result['id']=$this->id;
        $result = json_encode($result);
        $this->output($result);
    }


    function output($json)
    {
        $length = strlen($json);
        header('Connection: close');
        header('Content-Length: '.$length);
        header('Content-Type: application/json');
        header('Date: '.date('r'));
        echo $json;
        exit;
    }


    function hasMethod($method) {
        return in_array($method, array_keys($this->callbacks));
    }


    function setCapabilities() {
        // Initialises capabilities array
        $this->capabilities = array(
            'xmlrpc' => array(
                'specUrl' => 'http://www.xmlrpc.com/spec',
                'specVersion' => 1
            ),
            'faults_interop' => array(
                'specUrl' => 'http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php',
                'specVersion' => 20010516
            ),
            'system.multicall' => array(
                'specUrl' => 'http://www.xmlrpc.com/discuss/msgReader$1208',
                'specVersion' => 1
            ),
        );
    }


    function getCapabilities() {
        return $this->capabilities;
    }


    function setCallbacks() {
        $this->callbacks['system.getCapabilities'] = 'this:getCapabilities';
        $this->callbacks['system.listMethods'] = 'this:listMethods';
        $this->callbacks['system.multicall'] = 'this:multiCall';
    }


    function listMethods() {
        // Returns a list of methods - uses array_reverse to ensure user defined
        // methods are listed before server defined methods
        return array_reverse(array_keys($this->callbacks));
    }


    function multiCall($methodcalls) {
        // See http://www.xmlrpc.com/discuss/msgReader$1208
        $return = array();
        foreach ($methodcalls as $call) {
            $method = $call['methodName'];
            $params = $call['params'];
            if ($method == 'system.multicall') {
                $result = new IJR_Error(-32600, 'Recursive calls to system.multicall are forbidden');
            } else {
                $result = $this->call($method, $params);
            }
            if (is_a($result, 'IJR_Error')) {
                $return[] = array(
                    'faultCode' => $result->code,
                    'faultString' => $result->message
                );
            } else {
                $return[] = array($result);
            }
        }
        return $return;
    }
}
?>
