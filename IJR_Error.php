<?php
/**
 * Description of IJR_Error
 *
 * @author  Andreas Gohr <andi@splitbrain.org>
 * @author Magnus Wolf <mwolf2706@googlemail.com>
 */
class IJR_Error {
    var $code;
    var $message;
    function IJR_Error($code, $message) {
        $this->code = $code;
        $this->message = $message;
    }

    function getJson(){
        $result['code'] = $this->code;
        $result['message'] = $this->message;
        return $result;
    }
}
?>
