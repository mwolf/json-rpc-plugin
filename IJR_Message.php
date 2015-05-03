<?php
/**
 *  * @version 1.61
 * @author  Simon Willison
 * @date    11th July 2003
 * @link    http://scripts.incutio.com/xmlrpc/
 * @link    http://scripts.incutio.com/xmlrpc/manual.php
 * @license Artistic License http://www.opensource.org/licenses/artistic-license.php
 *
 * Modified for DokuWiki
 * @author  Andreas Gohr <andi@splitbrain.org>
 * @author Magnus Wolf <mwolf2706@googlemail.com>
 */

require_once('./IJR_Error.php');

class IJR_Message {
    var $message;
    var $messageType;  // methodCall / methodResponse / fault
    var $faultCode;
    var $faultString;
    var $methodName;
    var $params;
    // Current variable stacks
    var $_arraystructs = array();   // The stack used to keep track of the current array/struct
    var $_arraystructstypes = array(); // Stack keeping track of if things are structs or array
    var $_currentStructName = array();  // A stack as well
    var $_param;
    var $_value;
    var $_currentTag;
    var $_currentTagContents;
    // The XML parser
    var $_parser;
    function IJR_Message ($message) {
        $this->message = $message;
    }
    function parse() {
        $this->message = preg_replace('/<\?xml(.*)?\?'.'>/', '', $this->message);
        $this->message = str_replace('&lt;', '&#60;', $this->message);
        $this->message = str_replace('&gt;', '&#62;', $this->message);
        $this->message = str_replace('&amp;', '&#38;', $this->message);
        $this->message = str_replace('&apos;', '&#39;', $this->message);
        $this->message = str_replace('&quot;', '&#34;', $this->message);
        if (trim($this->message) == '') {
            return false;
        }
        $this->_parser = xml_parser_create();
        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);
        xml_set_object($this->_parser, $this);
        xml_set_element_handler($this->_parser, 'tag_open', 'tag_close');
        xml_set_character_data_handler($this->_parser, 'cdata');
        if (!xml_parse($this->_parser, $this->message)) {
            return false;
        }
        xml_parser_free($this->_parser);
        if ($this->messageType == 'fault') {
            $this->faultCode = $this->params[0]['faultCode'];
            $this->faultString = $this->params[0]['faultString'];
        }
        return true;
    }
    function tag_open($parser, $tag, $attr) {
        $this->currentTag = $tag;
        $this->_currentTagContents = '';
        switch($tag) {
            case 'methodCall':
            case 'methodResponse':
            case 'fault':
                $this->messageType = $tag;
                break;
            case 'data':    // data is to all intents and puposes more interesting than array
                $this->_arraystructstypes[] = 'array';
                $this->_arraystructs[] = array();
                break;
            case 'struct':
                $this->_arraystructstypes[] = 'struct';
                $this->_arraystructs[] = array();
                break;
        }
    }
    function cdata($parser, $cdata) {
        $this->_currentTagContents .= $cdata;
    }
    function tag_close($parser, $tag) {
        $valueFlag = false;
        switch($tag) {
            case 'int':
            case 'i4':
                $value = (int)trim($this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag = true;
                break;
            case 'double':
                $value = (double)trim($this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag = true;
                break;
            case 'string':
                $value = (string)$this->_currentTagContents;
                $this->_currentTagContents = '';
                $valueFlag = true;
                break;
            case 'dateTime.iso8601':
                $value = new IJR_Date(trim($this->_currentTagContents));
                $this->_currentTagContents = '';
                $valueFlag = true;
                break;
            case 'value':
                if (trim($this->_currentTagContents) != '') {
                    $value = (string)$this->_currentTagContents;
                    $this->_currentTagContents = '';
                    $valueFlag = true;
                }
                break;
            case 'boolean':
                $value = (boolean)trim($this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag = true;
                break;
            case 'base64':
                $value = base64_decode($this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag = true;
                break;
            case 'data':
            case 'struct':
                $value = array_pop($this->_arraystructs);
                array_pop($this->_arraystructstypes);
                $valueFlag = true;
                break;
            case 'member':
                array_pop($this->_currentStructName);
                break;
            case 'name':
                $this->_currentStructName[] = trim($this->_currentTagContents);
                $this->_currentTagContents = '';
                break;
            case 'methodName':
                $this->methodName = trim($this->_currentTagContents);
                $this->_currentTagContents = '';
                break;
        }
        if ($valueFlag) {
            if (count($this->_arraystructs) > 0) {
                if ($this->_arraystructstypes[count($this->_arraystructstypes)-1] == 'struct') {
                    $this->_arraystructs[count($this->_arraystructs)-1][$this->_currentStructName[count($this->_currentStructName)-1]] = $value;
                } else {
                    $this->_arraystructs[count($this->_arraystructs)-1][] = $value;
                }
            } else {
                $this->params[] = $value;
            }
        }
    }
}