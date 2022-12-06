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
    protected $request_count = 1;
    protected $memory_footprint = null;
    protected $memory_peak = null;
    protected $document_size = null;
    protected $status = null;
    protected $rusage = array();
    protected $tags = array();

    protected static $options = array();

    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
        return true;
    }

    public function setServername($server_name)
    {
        $this->server_name = $server_name;
        return true;
    }

    public function setScriptname($script_name)
    {
        $this->script_name = $script_name;
        return true;
    }

    public function setSchema($schema)
    {
        $this->schema = $schema;
        return true;
    }

    public function setRequestTime($request_time)
    {
        $this->request_time = $request_time;
        return true;
    }

    protected function _timer_get_info($timer, $time)
    {
        if (isset($this->timers[$timer]))
        {
            $timer = $this->timers[$timer];
            if ($timer["started"])
            {
                $timer["value"] = $time - $timer["value"];
            }
            /// @todo should we round the timer value?
            return $timer;
        }

        trigger_error("pinba_timer_get_info(): supplied resource is not a valid pinba timer resource", E_USER_WARNING);
        return false;
    }

    protected function _get_info()
    {
        $time = microtime(true);

        if ($this->hostname === null)
        {
            if (php_sapi_name() == 'cli')
            {
                $this->hostname = 'php';
            }
            else
            {
                $this->hostname = gethostname();
            }
        }
        if ($this->script_name === null && isset($_SERVER['SCRIPT_NAME']))
        {
            $this->script_name = $_SERVER['SCRIPT_NAME'];
        }
        if ($this->server_name === null && isset($_SERVER['SERVER_NAME']))
        {
            $this->server_name = $_SERVER['SERVER_NAME'];
        }

        if ($this->rusage) {
            $ruUtime = reset($this->rusage);
            $ruStime = end($this->rusage);
        } else {
            $ruUtime = 0;
            $ruStime = 0;
            if (function_exists('getrusage')) {
                $rUsage = getrusage();
                if (isset($rUsage['ru_utime.tv_usec'])) {
                    $ruUtime = $rUsage['ru_utime.tv_usec'] / 1000000;
                }
                if (isset($rUsage['ru_utime.tv_usec'])) {
                    $ruStime = $rUsage['ru_stime.tv_usec'] / 1000000;
                }
            }
        }

        $timers = array();
        foreach($this->timers as $id => $t)
        {
            $timers[] = $this->_timer_get_info($id, $time);
        }

        return array(
            'mem_peak_usage' => ($this->memory_peak !== null ? $this->memory_peak :  memory_get_peak_usage(true)),
            'req_time' => $time - $this->request_time,
            'ru_utime' => $ruUtime,
            'ru_stime' => $ruStime,
            'req_count' => ($this->request_count !== null ? $this->request_count : 1), /// @todo should we default to 0 ?
            'doc_size' => ($this->document_size !== null ? $this->document_size : 0),
            'schema' => $this->schema,
            'server_name' => ($this->server_name !== null ? $this->server_name : 'unknown'),
            'script_name' => ($this->script_name !== null ? $this->script_name : 'unknown'),
            'hostname' => ($this->hostname !== null ? $this->hostname : 'unknown'),
            'timers' => $timers,
            'tags' => $this->tags
        );
    }

    /**
     * Builds the php array structure to be sent to the pinba server.
     * NB: depending on the value of $flags, it will stop all running timers
     * @param array $struct the data returned by `_get_info`. NB: data from this object will be added to that!
     * @param string|null $script_name
     * @return array
     * @todo we could move injection of hostname, status, memory_footprint to the caller, to avoid data coming from 2 sources
     */
    protected function getPacketInfo($struct, $script_name = null)
    {
        // massage info into correct format for pinba server

        $struct["hostname"] = $this->hostname;
        if ($script_name != null)
        {
            $struct["script_name"] = $script_name;
        }
        foreach(array(
            "mem_peak_usage" => "memory_peak",
            "req_time" => "request_time",
            "req_count" => "request_count",
            "doc_size" => "document_size") as $old => $new)
        {
            $struct[$new] = $struct[$old];
        }

        // merge timers by tags
        $tags = array();
        foreach($struct["timers"] as $id => $timer)
        {
            $tag = md5(var_export($timer["tags"], true));
            if (isset($tags[$tag]))
            {
                $struct["timers"][$tags[$tag]]["value"] = $struct["timers"][$tags[$tag]]["value"] + $timer["value"];
                $struct["timers"][$tags[$tag]]["count"] = $struct["timers"][$tags[$tag]]["count"] + 1;
                unset($struct["timers"][$id]);
            }
            else
            {
                $tags[$tag] = $id;
                $struct["timers"][$id]["count"] = 1;
            }
        }
        // build tag dictionary and index timer tags
        $dict = array();
        foreach($struct["timers"] as $id => $timer)
        {
            foreach($timer['tags'] as $tag => $value)
            {
                if (($tagid = array_search($tag, $dict)) === false)
                {
                    $tagid = count($dict);
                    $dict[] = $tag;
                }
                if (($valueid = array_search($value, $dict)) === false)
                {
                    $valueid = count($dict);
                    $dict[] = $value;
                }
                $struct["timers"][$id]['tagids'][$tagid] = $valueid;
            }
        }
        $struct["timer_hit_count"] = array();
        $struct["timer_value"] = array();
        $struct["timer_tag_count"] = array();
        $struct["timer_tag_name"] = array();
        $struct["timer_tag_value"] = array();
        foreach($struct["timers"] as $timer)
        {
            $struct["timer_hit_count"][] = $timer["count"];
            $struct["timer_value"][] = $timer["value"];
            $struct["timer_tag_count"][] = count($timer["tags"]);
            foreach($timer["tagids"] as $key => $val)
            {
                $struct["timer_tag_name"][] = $key;
                $struct["timer_tag_value"][] = $val;
            }
        }
        $struct["dictionary"] = array();
        foreach($dict as $tag)
        {
            $struct["dictionary"][] = $tag;
        }

        /// @todo implement the missing fields below

        $struct["status"] = $this->status;
        $struct["memory_footprint"] = $this->memory_footprint;
        $struct["requests"] = array(); /// @todo

        $struct["tag_name"] = array();
        $struct["tag_value"] = array();
        foreach($struct["tags"] as $name => $value) {
            $struct["tag_name"][] = $name;
            $struct["tag_value"][] = $value;
        }

        $struct["timer_ru_utime"] = array(); /// @todo
        $struct["timer_ru_stime"] = array(); /// @todo

        return $struct;
    }

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
