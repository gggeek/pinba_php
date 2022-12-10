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
    // Note: we initialize this to '' instead of NULL because we found out during testing the pinba server, when it does
    // receive a packet with no schema member set, reuses the last 'schema' value received in a previous packet.
    // This way we force the packets sent to always have the 'schema' field set, by default to a zero-length string.
    // That in turn makes the pinba engine store the value '<empty>' in the db.
    // This behaviour makes testing more deterministic, and honestly looks more like a bugfix than anything else
    protected $schema = '';
    protected $request_time = null;
    protected $request_count = 0;
    protected $memory_footprint = null;
    protected $memory_peak = null;
    protected $document_size = null;
    protected $status = null;
    protected $rusage = array();
    protected $tags = array();

    protected static $options = array();

    /// Make this class "abstract", in a way that subclasses can still instantiate it, but no one else can
    protected function __construct() {
    }

    // *** public API (exposed by PinbaClient) ***

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

    // *** methods for subclass use ***

    protected function deleteTimers($flags)
    {
        foreach ($this->timers as &$timer) {
            if (($flags & self::ONLY_STOPPED_TIMERS) && $timer["started"]) {
                continue;
            }
            $timer['deleted'] = true;
        }
    }

    protected function stopTimers($time)
    {
        foreach ($this->timers as &$timer) {
            if ($timer["started"] && !$timer['deleted']) {
                $timer["started"] = false;
                $timer["value"] = $time - $timer["value"];
            }
        }
        return true;
    }

    /**
     * NB: works for deleted timers too
     * @param int $timer
     * @param float $time
     * @param bool $removeHitCount
     * @return false|array
     */
    protected function getTimerInfo($timer, $time, $removeHitCount = true)
    {
        if (isset($this->timers[$timer])) {
            $timer = $this->timers[$timer];
            if ($timer["started"]) {
                $timer["value"] = $time - $timer["value"];
            }
            unset($timer['deleted']);
            if ($removeHitCount) {
                unset($timer['hit_count']);
            }
            return $timer;
        }

        trigger_error("pinba_timer_get_info(): supplied resource is not a valid pinba timer resource", E_USER_WARNING);
        return false;
    }

    /**
     * Checks that tags are fit for usage
     * @param array $tags
     * @return bool
     */
    protected static function verifyTags($tags)
    {
        if (!$tags) {
            trigger_error("tags array cannot be empty", E_USER_WARNING);
            return false;
        }
        foreach ($tags as $key => $val) {
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

    protected function getInfo($removeHitCount = true, $flags = 0)
    {
        $time = microtime(true);

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
        foreach ($this->timers as $id => $t) {
            if ($t['deleted'] || (($flags & self::ONLY_STOPPED_TIMERS) && $t['started'])) {
                continue;
            }
            $timers[] = $this->getTimerInfo($id, $time, $removeHitCount);;
        }

        $hostname = $this->hostname;
        if ($hostname === null) {
            if (php_sapi_name() == 'cli') {
                $hostname = 'php';
            } else {
                $hostname = gethostname();
            }
        }
        $script_name = $this->script_name;
        if ($script_name === null && isset($_SERVER['SCRIPT_NAME'])) {
            $script_name = $_SERVER['SCRIPT_NAME'];
        }
        $server_name = $this->server_name;
        if ($server_name === null && isset($_SERVER['SERVER_NAME'])) {
            $server_name = $_SERVER['SERVER_NAME'];
        }
        $document_size = $this->document_size;
        /// @todo parse the results of `headers_list()` looking for `Content-Length`
        ///       Note: that might not work for most scenarios, including simple ones such as using php-fpm and no
        ///       frontend controllers at all, even if there is a `flush` call executed before we reach here
        //if ($document_size === null) {
        //}

        return array(
            /// @todo in the extension, memory_get_peak_usage is not used when this is called from a PinbaClient
            'mem_peak_usage' => ($this->memory_peak !== null ? $this->memory_peak :  memory_get_peak_usage(true)),
            'req_time' => $time - $this->request_time,
            'ru_utime' => $ruUtime,
            'ru_stime' => $ruStime,
            'req_count' => ($this->request_count !== null ? $this->request_count : 0),
            'doc_size' => ($document_size !== null ? $document_size : 0),
            'schema' => $this->schema,
            'server_name' => ($server_name !== null ? $server_name : 'unknown'),
            'script_name' => ($script_name !== null ? $script_name : 'unknown'),
            'hostname' => ($hostname !== null ? $hostname : 'unknown'),
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
            $struct = $this->getInfo(false);
        }

        // massage info into correct format for pinba server

        $status = $this->status;
        if ($status === null) {
            if (($code = http_response_code()) !== false) {
                $status = $code;
            }
        }

        $struct["status"] = $status;
        $struct["memory_footprint"] = $this->memory_footprint;

        foreach (array(
            "mem_peak_usage" => "memory_peak",
            "req_time" => "request_time",
            "req_count" => "request_count",
            "doc_size" => "document_size") as $old => $new) {
            $struct[$new] = $struct[$old];
        }

        // merge timers by tags (replacing them within $struct)
        $timersByTags = array();
        foreach ($struct["timers"] as $id => $timer) {
            $ttags = $timer["tags"];
            ksort($ttags);
            $tagHash = md5(var_export($ttags, true));
            if (isset($timersByTags[$tagHash])) {
                $originalId = $timersByTags[$tagHash];
                $struct["timers"][$originalId]["value"] = $struct["timers"][$originalId]["value"] + $timer["value"];
                $struct["timers"][$originalId]["hit_count"] = $struct["timers"][$originalId]["hit_count"] + $timer["hit_count"];
                unset($struct["timers"][$id]);
            } else {
                $timersByTags[$tagHash] = $id;
            }
        }

        // build tag dictionary and add to timers and tags the dictionary ids
        $dict = array();
        foreach ($struct['timers'] as $id => $timer) {
            foreach ($timer['tags'] as $tag => $value) {
                if (($tagId = array_search($tag, $dict)) === false) {
                    $tagId = count($dict);
                    $dict[] = $tag;
                }
                if (($valueid = array_search($value, $dict)) === false) {
                    $valueid = count($dict);
                    $dict[] = $value;
                }
                $struct['timers'][$id]['tagids'][$tagId] = $valueid;
            }
        }
        $tagIds = array();
        foreach ($struct["tags"] as $tag => $value) {
            if (($tagId = array_search($tag, $dict)) === false) {
                $tagId = count($dict);
                $dict[] = $tag;
            }
            if (($valueid = array_search($value, $dict)) === false) {
                $valueid = count($dict);
                $dict[] = $value;
            }
            $tagIds[$tagId] = $valueid;
        }

        $struct["timer_hit_count"] = array();
        $struct["timer_value"] = array();
        $struct["timer_tag_count"] = array();
        $struct["timer_tag_name"] = array();
        $struct["timer_tag_value"] = array();
        foreach ($struct["timers"] as $timer) {
            $struct["timer_hit_count"][] = $timer["hit_count"];
            $struct["timer_value"][] = $timer["value"];
            $struct["timer_tag_count"][] = count($timer["tagids"]);
            foreach ($timer["tagids"] as $key => $val) {
                $struct["timer_tag_name"][] = $key;
                $struct["timer_tag_value"][] = $val;
            }
        }

        $struct["dictionary"] = array();
        foreach ($dict as $word) {
            $struct["dictionary"][] = $word;
        }

        $struct["tag_name"] = array();
        $struct["tag_value"] = array();
        foreach ($tagIds as $name => $value) {
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
            if (count($parts = explode(':', $server)) == 2) {
                // IPv4 with port
                $port = (int)$parts[1];
                $server = $parts[0];
            }
        }

        /// @todo should we log a more specific warning in case of failures to open the udp socket? f.e. the pinba
        ///       extension on invalid hostname triggers:
        ///       PHP Warning:  Unknown: failed to resolve Pinba server hostname 'xxx': Name or service not known in Unknown on line 0
        $fp = fsockopen("udp://$server", $port, $errno, $errstr);
        if ($fp) {
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
     * @see Pinba::ini_set()
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
