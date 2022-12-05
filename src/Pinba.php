<?php

namespace PinbaPhp\Polyfill;

abstract class Pinba
{
    const PINBA_FLUSH_ONLY_STOPPED_TIMERS = 1;
    const PINBA_FLUSH_RESET_DATA = 2;
    const PINBA_ONLY_RUNNING_TIMERS = 4;
    const PINBA_AUTO_FLUSH = 8;
    const PINBA_ONLY_STOPPED_TIMERS = 1;

    protected static $message_proto = array(
        1 => array("hostname", Prtbfr::TYPE_STRING), // bytes for pinba2
        2 => array("server_name", Prtbfr::TYPE_STRING), // bytes for pinba2
        3 => array("script_name", Prtbfr::TYPE_STRING), // bytes for pinba2
        4 => array("request_count", Prtbfr::TYPE_UINT32),
        5 => array("document_size", Prtbfr::TYPE_UINT32),
        6 => array("memory_peak", Prtbfr::TYPE_UINT32),
        7 => array("request_time", Prtbfr::TYPE_FLOAT),
        8 => array("ru_utime", Prtbfr::TYPE_FLOAT),
        9 => array("ru_stime", Prtbfr::TYPE_FLOAT),
        10 => array("timer_hit_count", Prtbfr::TYPE_UINT32, Prtbfr::ELEMENT_REPEATED),
        11 => array("timer_value", Prtbfr::TYPE_FLOAT, Prtbfr::ELEMENT_REPEATED),
        12 => array("timer_tag_count", Prtbfr::TYPE_UINT32, Prtbfr::ELEMENT_REPEATED),
        13 => array("timer_tag_name", Prtbfr::TYPE_UINT32, Prtbfr::ELEMENT_REPEATED),
        14 => array("timer_tag_value", Prtbfr::TYPE_UINT32, Prtbfr::ELEMENT_REPEATED),
        15 => array("dictionary", Prtbfr::TYPE_STRING, Prtbfr::ELEMENT_REPEATED), // bytes for pinba2
        16 => array("status", Prtbfr::TYPE_UINT32, Prtbfr::ELEMENT_OPTIONAL),
        17 => array("memory_footprint", Prtbfr::TYPE_UINT32, Prtbfr::ELEMENT_OPTIONAL),
        18 => array("requests", Prtbfr::TYPE_REQUEST, Prtbfr::ELEMENT_REPEATED),
        19 => array("schema", Prtbfr::TYPE_STRING, Prtbfr::ELEMENT_OPTIONAL), // bytes for pinba2
        20 => array("tag_name", Prtbfr::TYPE_UINT32, Prtbfr::ELEMENT_REPEATED),
        21 => array("tag_value", Prtbfr::TYPE_UINT32, Prtbfr::ELEMENT_REPEATED),
        22 => array("timer_ru_utime", Prtbfr::TYPE_FLOAT, Prtbfr::ELEMENT_REPEATED),
        23 => array("timer_ru_stime", Prtbfr::TYPE_FLOAT, Prtbfr::ELEMENT_REPEATED),
    );

    protected $timers = array();
    protected $script_name = null;
    protected $server_name = null;
    protected $hostname = null;
    protected $schema = '';
    protected $request_time = null;
    protected $tags = array();

    protected static $options = array();

    /**
     * Sadly it is not possible to set in php code values for 'pinba.enabled', at least when the pinba extension is
     * not on board. When using `php -d pinba.enabled=1` or values in php.ini, `ini_get` will also not work, whereas
     * `get_cfg_var` will.
     * We try to make life easy for the users of the polyfill as well as for the test code by allowing usage of values
     * set both in php.ini and in php code
     * @param string $option
     * @return string|false
     * @see PinbaFunctions::ini_set()
     */
    public static function ini_get($option)
    {
        if (array_key_exists($option, self::$options)) {
            return self::$options[$option];
        }

        $val = ini_get($option);
        if ($val === false) {
            $val = get_cfg_var($option);
        }

        return $val;
    }

    /**
     * Allow to set config values specific to pinba, without messing with php.ini stuff.
     * @param string $option
     * @param string|int|float|bool|null $value
     * @return string|null
     */
    public static function ini_set($option, $value)
    {
        if (array_key_exists($option, self::$options)) {
            $oldValue = self::$options[$option];
        } else {
            // we do not return false, as that would indicate a failure ;-)
            $oldValue = null;
        }
        self::$options[$option] = (string)$value;
        return $oldValue;
    }
}
