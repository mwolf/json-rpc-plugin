<?php
/**
 * Description of IJR_Base64
 *
 * @author  Andreas Gohr <andi@splitbrain.org>
 * @author Magnus Wolf <mwolf2706@googlemail.com>
 */
class IJR_Base64 {
    var $data;
    function IJR_Base64($data) {
        $this->data = $data;
    }
    function getXml() {
        return '<base64>'.base64_encode($this->data).'</base64>';
    }
}
?>
