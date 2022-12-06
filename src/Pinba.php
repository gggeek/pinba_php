<?php

namespace PinbaPhp\Polyfill;

class Pinba
{
    const FLUSH_ONLY_STOPPED_TIMERS = 1;
    const FLUSH_RESET_DATA = 2;
    const ONLY_RUNNING_TIMERS = 4;
    const AUTO_FLUSH = 8;
    const ONLY_STOPPED_TIMERS = 1;

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

    /// Make this class "abstract", in a way that subclasses can instantiate it
    protected function __construct() {
    }

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
        if ($request_time < 0) {
            trigger_error("negative time value passed ($request_time), changing it to 0", E_USER_WARNING);
            $request_time = 0;
        }

        $this->request_time = $request_time;
        return true;
    }

    protected function stopTimers($time)
    {
        foreach ($this->timers as &$timer)
        {
            if ($timer["started"])
            {
                $timer["started"] = false;
                $timer["value"] = $time - $timer["value"];
            }
        }
        return true;
    }

    protected function getTimerInfo($timer, $time)
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

    /**
     * Checks that tags are fit for usage
     * @param array $tags
     * @return bool
     * @todo add calls to this
     */
    protected static function verifyTags($tags)
    {
        if (!$tags) {
            trigger_error("tags array cannot be empty", E_USER_WARNING);
            return false;
        }
        foreach($tags as $key => $val) {
            if (is_object($val) || is_array($val) || is_resource($val)) {
                trigger_error("tags cannot have non-scalar values", E_USER_WARNING);
                return false;
            }
            if (is_int($key)) {
                trigger_error("tags can only have string names (i.e. tags array cannot contain numeric indexes)", E_USER_WARNING);
                return false;
            }
        }
        return true;
    }

    protected function getInfo()
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
            $timers[] = $this->getTimerInfo($id, $time);
        }

        return array(
            /// @todo in the extension, memory_get_peak_usage is not used when this is called froma PinbaClient
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
     * Builds the php array structure to be sent to the pinba server, and encodes it as protobuffer.
     * @param null|array $struct Allows injecting the data returned by `getInfo`, after having modified it. NB: data
     *                   from this object will be added to that!
     * @return string
     */
    protected function getPacket($struct = null)
    {
        // allow injection of custom starting data
        if ($struct === null) {
            $struct = $this->getInfo();
        }

        // massage info into correct format for pinba server

        $struct["hostname"] = $this->hostname;
        $struct["status"] = $this->status;
        $struct["memory_footprint"] = $this->memory_footprint;

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
            $tags = $timer["tags"];
            ksort($tags);
            $tag = md5(var_export($tags, true));
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

        $struct["tag_name"] = array();
        $struct["tag_value"] = array();
        foreach($struct["tags"] as $name => $value) {
            $struct["tag_name"][] = $name;
            $struct["tag_value"][] = $value;
        }

        /// @todo implement the missing fields below

        $struct["requests"] = array(); /// @todo
        $struct["timer_ru_utime"] = array(); /// @todo
        $struct["timer_ru_stime"] = array(); /// @todo

        return Prtbfr::encode($struct, self::$message_proto);
    }

    /**
     * @param string $server see https://github.com/tony2001/pinba_engine/wiki/PHP-extension#pinbaserver for the supportde syntax
     * @param string $message
     * @return bool
     */
    protected static function _send($server, $message)
    {
        $port = 30002;
        if (preg_match('/^\\[(.+)\\]:([0-9]+)$/', $server, $matches)) {
            $server = $matches[1];
            $port = (int)$matches[2];
        } else {
            if (count($parts = explode(':', $server)) == 2)
            {
                // IPv4 with port
                $port = (int)$parts[1];
                $server = $parts[0];
            }
        }

        /// @todo should we log a more specific warning in case of failures to open the udp socket? f.e. the pinba
        ///       extension on invalid hostname triggers:
        ///       PHP Warning:  Unknown: failed to resolve Pinba server hostname 'xxx': Name or service not known in Unknown on line 0
        $fp = fsockopen("udp://$server", $port, $errno, $errstr);
        if ($fp)
        {
            $msgLen = strlen($message);
            $len = fwrite($fp, $message, $msgLen);
            fclose($fp);

            if ($len < $msgLen) {
                trigger_error("failed to send data to Pinba server", E_USER_WARNING);
            }

            return $msgLen == $len;
        }

        return false;
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
