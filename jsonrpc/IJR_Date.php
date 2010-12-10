<?php
/**
 * Description of IJR_Date
 *
 * @author  Andreas Gohr <andi@splitbrain.org>
 * @author Magnus Wolf <mwolf2706@googlemail.com>
 */
class IJR_Date {
    var $year;
    var $month;
    var $day;
    var $hour;
    var $minute;
    var $second;
    function IJR_Date($time) {
        // $time can be a PHP timestamp or an ISO one
        if (is_numeric($time)) {
            $this->parseTimestamp($time);
        } else {
            $this->parseIso($time);
        }
    }
    function parseTimestamp($timestamp) {
        $this->year = gmdate('Y', $timestamp);
        $this->month = gmdate('m', $timestamp);
        $this->day = gmdate('d', $timestamp);
        $this->hour = gmdate('H', $timestamp);
        $this->minute = gmdate('i', $timestamp);
        $this->second = gmdate('s', $timestamp);
    }
    function parseIso($iso) {
        $this->year = substr($iso, 0, 4);
        $this->month = substr($iso, 5, 2);
        $this->day = substr($iso, 8, 2);
        $this->hour = substr($iso, 11, 2);
        $this->minute = substr($iso, 14, 2);
        $this->second = substr($iso, 17, 2);
    }
    function getIso() {
        return $this->year.'-'.$this->month.'-'.$this->day.'T'.$this->hour.':'.$this->minute.':'.$this->second;
    }
    function getXml() {
        return '<dateTime.iso8601>'.$this->getIso().'</dateTime.iso8601>';
    }
    function getTimestamp() {
        return gmmktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
    }
}
?>
