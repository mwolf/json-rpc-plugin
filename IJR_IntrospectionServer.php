<?php
/**
 * Description of IJR_InspectionServer
 *
 * @author  Andreas Gohr <andi@splitbrain.org>
 * @author Magnus Wolf <mwolf2706@googlemail.com>
 */
require_once('./IJR_Server.php');
require_once('./IJR_CallbackDefines.php');

class IJR_IntrospectionServer extends IJR_Server {
    private $signatures;
    private $help;
    private $callbackMethods;

    protected function IJR_IntrospectionServer() {
        $this->setCallbacks();
        $this->setCapabilities();
        $this->capabilities['introspection'] = array(
            'specUrl' => 'http://xmlrpc.usefulinc.com/doc/reserved.html',
            'specVersion' => 1
        );
        $callbackDef = new IJR_CallbackDefines();
        $this->callbackMethods = $callbackDef->getSystemMethods();

        foreach($this->callbackMethods as $key)
        {
            $this->addCallback($key['method'], $key['callback'], $key['args'], $key['help']);
        }
    }

    protected function addCallback($method, $callback, $args, $help) {
        $this->callbacks[$method] = $callback;
        $this->signatures[$method] = $args;
        $this->help[$method] = $help;
    }

    protected function call($methodname, $args) {
        if ($args && !is_array($args))
        {
            $args = array($args);
        }
        if (!$this->hasMethod($methodname))
        {
            return new IJR_Error(-32601, 'server error. requested method "'.$methodname.'" not specified.');
        }
        
        $method = $this->callbacks[$methodname];
        $signature = $this->signatures[$methodname];
        $returnType = array_shift($signature);

        if (count($args) < count($signature))
        {
            return new IJR_Error(-32602, 'server error. missing method parameters');
        }
        // Check the argument types
        $ok = true;
        $argsbackup = $args;

        for ($i = 0, $j = count($args); $i < $j; $i++) {

            $arg = array_shift($args);
            $type = array_shift($signature);

            switch ($type) {
                case 'int':
                case 'i4':
                    if (is_array($arg) || !is_int($arg)) {
                        $ok = false;
                    }
                    break;
                case 'base64':
                case 'string':
                    if (!is_string($arg)) {
                        $ok = false;
                    }
                    break;
                case 'boolean':
                    if ($arg !== false && $arg !== true) {
                        $ok = false;
                    }
                    break;
                case 'float':
                case 'double':
                    if (!is_float($arg)) {
                        $ok = false;
                    }
                    break;
                case 'date':
                case 'dateTime.iso8601':
                    if (!is_a($arg, 'IJR_Date')) {
                        $ok = false;
                    }
                    break;
            }
            if (!$ok) {
                return new IJR_Error(-32602, 'server error. invalid method parameters');
            }
        }
        return parent::call($methodname, $argsbackup);
    }

    private function methodSignature($method) {
        if (!$this->hasMethod($method)) {
            return new IJR_Error(-32601, 'server error. requested method "'.$method.'" not specified.');
        }
        // We should be returning an array of types
        $types = $this->signatures[$method];
        $return = array();
        foreach ($types as $type) {
            switch ($type) {
                case 'string':
                    $return[] = 'string';
                    break;
                case 'int':
                case 'i4':
                    $return[] = 42;
                    break;
                case 'double':
                    $return[] = 3.1415;
                    break;
                case 'dateTime.iso8601':
                    $return[] = new IJR_Date(time());
                    break;
                case 'boolean':
                    $return[] = true;
                    break;
                case 'base64':
                    $return[] = new IJR_Base64('base64');
                    break;
                case 'array':
                    $return[] = array('array');
                    break;
                case 'struct':
                    $return[] = array('struct' => 'struct');
                    break;
            }
        }
        return $return;
    }

    function methodHelp($method) {
        return $this->help[$method];
    }
}
?>
