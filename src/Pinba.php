<?php
/**
 * A class implementing the pinba extension functionality, in pure php
 *
 * @see http://pinba.org/wiki/Manual:PHP_extension
 * @author G. Giunta
 * @copyright (C) G. Giunta 2011 - 2022
 */

namespace PinbaPhp\Polyfill;

class Pinba
{
    protected static $timers = array();
    protected static $script_name = null;
    protected static $server_name = null;
    protected static $hostname = null;
    protected static $start = null;
    protected static $shutdown_registered = false;
    protected static $message_proto = array(
        1 => array("hostname", Prtbfr::TYPE_STRING),
        2 => array("server_name", Prtbfr::TYPE_STRING),
        3 => array("script_name", Prtbfr::TYPE_STRING),
        4 => array("request_count", Prtbfr::TYPE_UINT32),
        5 => array("document_size", Prtbfr::TYPE_UINT32),
        6 => array("memory_peak", Prtbfr::TYPE_UINT32),
        7 => array("request_time", Prtbfr::TYPE_UINT32),
        8 => array("ru_utime", Prtbfr::TYPE_UINT32),
        9 => array("ru_stime", Prtbfr::TYPE_UINT32),
        10 => array("timer_hit_count", Prtbfr::TYPE_UINT32, Prtbfr::ELEMENT_REPEATED),
        11 => array("timer_value", Prtbfr::TYPE_UINT32, Prtbfr::ELEMENT_REPEATED),
        12 => array("timer_tag_count", Prtbfr::TYPE_UINT32, Prtbfr::ELEMENT_REPEATED),
        13 => array("timer_tag_name", Prtbfr::TYPE_UINT32, Prtbfr::ELEMENT_REPEATED),
        14 => array("timer_tag_value", Prtbfr::TYPE_UINT32, Prtbfr::ELEMENT_REPEATED),
        15 => array("dictionary", Prtbfr::TYPE_STRING, Prtbfr::ELEMENT_REPEATED),
        16 => array("status", Prtbfr::TYPE_UINT32, Prtbfr::ELEMENT_OPTIONAL)
    );

    /**
     * Creates and starts new timer.
     *
     * @param array $tags an array of tags and their values in the form of "tag" => "value". Cannot contain numeric indexes for obvious reasons.
     * @param array $data optional array with user data, not sent to the server.
     * @return resource Always returns new timer resource.
     */
    public static function timer_start($tags, $data=null)
    {
        $time = microtime(true);
        $timer = count(self::$timers);
        self::$timers[$timer] = array(
            "value" => $time,
            "tags" => $tags,
            "started" => true,
            "data" => $data
        );
        return $timer;
    }

    /**
     * Stops the timer.
     *
     * @param resource $timer valid timer resource.
     * @return bool Returns true on success and false on failure (if the timer has already been stopped).
     */
    public static function timer_stop($timer)
    {
        $time = microtime(true);
        if (isset(self::$timers[$timer]))
        {
            if (self::$timers[$timer]["started"])
            {
                if (function_exists('getrusage'))
                {
                    /// @todo measure resource usage
                }
                self::$timers[$timer]["started"] = false;
                self::$timers[$timer]["value"] = $time - self::$timers[$timer]["value"];
            }
        }
        return false;
    }

    /**
     * Deletes the timer.
     *
     * Available since: 0.0.6
     *
     * @param resource $timer valid timer resource.
     * @return bool Returns true on success and false on failure.
     */
    public static function timer_delete($timer)
    {
        if (isset(self::$timers[$timer]))
        {
            unset(self::$timers[$timer]);
            return true;
        }
        return false;
    }

    /**
     * Merges $tags array with the timer tags replacing existing elements.
     *
     * @param resource $timer - valid timer resource
     * @param array $tags - an array of tags.
     * @return bool
     */
    public static function timer_tags_merge($timer, $tags)
    {
        if (isset(self::$timers[$timer]))
        {
            self::$timers[$timer]["tags"] = array_merge(self::$timers[$timer]["tags"], $tags);
            return true;
        }
        return false;
    }

    /**
     * Replaces timer tags with the passed $tags array.
     *
     * @param resource $timer - valid timer resource
     * @param array $tags - an array of tags.
     * @return bool
     */
    public static function timer_tags_replace($timer, $tags)
    {
        if (isset(self::$timers[$timer]))
        {
            self::$timers[$timer]["tags"] = $tags;
            return true;
        }
        return false;
    }

    /**
     * Merges $data array with the timer user data replacing existing elements.
     *
     * @param resource $timer valid timer resource
     * @param array $data an array of user data.
     * @return bool Returns true on success and false on failure.
     */
    public static function timer_data_merge($timer, $data)
    {
        if (isset(self::$timers[$timer]))
        {
            self::$timers[$timer]["data"] = array_merge(self::$timers[$timer]["data"], $data);
            return true;
        }
        return false;
    }

    /**
     * Replaces timer user data with the passed $data array.
     * Use NULL value to reset user data in the timer.
     *
     * @param resource $timer valid timer resource
     * @param array $data an array of user data.
     * @return bool Returns true on success and false on failure.
     */
    public static function timer_data_replace($timer, $data)
    {
        if (isset(self::$timers[$timer]))
        {
            self::$timers[$timer]["data"] = $data;
            return true;
        }
        return false;
    }

    /**
     * Returns timer data.
     *
     * @param resource $timer - valid timer resource.
     * @return array Output example:
     *    array(4) {
     *    ["value"]=>
     *    float(0.0213)
     *    ["tags"]=>
     *    array(1) {
     *    ["foo"]=>
     *    string(3) "bar"
     *    }
     *    ["started"]=>
     *    bool(true)
     *    ["data"]=>
     *    NULL
     *    }
     * @todo what to return if timer is not valid?
     */
    public static function timer_get_info($timer)
    {
        $time = microtime(true);
        return static::_timer_get_info($timer, $time);
    }

    protected static function _timer_get_info($timer, $time)
    {
        if (isset(self::$timers[$timer]))
        {
            $timer = self::$timers[$timer];
            if ($timer["started"])
            {
                $timer["value"] = $time - $timer["value"];
            }
            /// @todo round the timer value?
            return $timer;
        }
        return array();
    }

    /**
     * Stops all running timers.
     *
     * @return bool
     *
     * @todo when shall we return false?
     */
    public static function timers_stop()
    {
        $time = microtime(true);
        foreach (self::$timers as &$timer)
        {
            if ($timer["started"])
            {
                $timer["started"] = false;
                $timer["value"] = $time - $timer["value"];
            }
        }
        return false;
    }

    /**
     * Returns all request data (including timers user data).
     *
     * @return array Example:
     * array(9) {
     *    ["mem_peak_usage"]=>
     *    int(786432)
     *    ["req_time"]=>
     *    float(0.001529)
     *    ["ru_utime"]=>
     *    float(0)
     *    ["ru_stime"]=>
     *    float(0)
     *    ["req_count"]=>
     *    int(1)
     *    ["doc_size"]=>
     *    int(0)
     *    ["server_name"]=>
     *    string(7) "unknown"
     *    ["script_name"]=>
     *    string(1) "-"
     *    ["timers"]=>
     *    array(1) {
     *        [0]=>
     *        array(4) {
     *            ["value"]=>
     *            float(4.5E-5)
     *            ["tags"]=>
     *            array(1) {
     *                ["foo"]=>
     *                string(3) "bar"
     *            }
     *            ["started"]=>
     *            bool(true)
     *            ["data"]=>
     *            NULL
     *        }
     *    }
     * }
     */
    public static function get_info()
    {
        $time = microtime(true);
        /// @todo can we get more info, such as resource usage?
        $results = array(
            "mem_peak_usage" => memory_get_peak_usage(true),
            "req_time" => $time - self::$start,
            "ru_utime" => 0,
            "ru_stime" => 0,
            "req_count" => 0,
            "doc_size" => 0,
            "server_name" => (self::$server_name != null ? self::$server_name : 'unknown'),
            "script_name" => (self::$script_name != null ? self::$script_name : 'unknown'),
            'timers' => array()
        );
        foreach(self::$timers as $i => $t)
        {
            $results['timers'][] = self::_timer_get_info($i, $time);
        }
        return $results;
    }

    /**
     * Set custom script name instead of $_SERVER['SCRIPT_NAME'] used by default.
     * Useful for those using front controllers, when all requests are served by one PHP script.
     *
     * @param string $script_name
     * @return bool
     */
    public static function script_name_set($script_name)
    {
        self::$script_name = $script_name;
    }

    /**
     * Set custom hostname instead of the result of gethostname() used by default.
     *
     * @param string $hostname
     * @return bool
     */
    public static function hostname_set($hostname)
    {
        self::$hostname = $hostname;
    }

    /**
     * Useful when you need to send request data to the server immediately (for long running scripts).
     * You can use optional argument script_name to set custom script name.
     *
     * @param string $script_name
     *
     * @todo add IPv6 support (see http://pinba.org/wiki/Manual:PHP_extension)
     */
    public static function flush($script_name=null)
    {
        if (ini_get('pinba.enabled'))
        {
            $struct = static::get_packet_info($script_name);
            $message = Prtbfr::encode($struct, self::$message_proto);

            $server = ini_get('pinba.server');
            $port = 30002;
            if (count($parts = explode(':', $server)) > 1)
            {
                (int)$port = $parts[1];
                $server = $parts[0];
            }
            $fp = fsockopen("udp://$server", $port, $errno, $errstr);
            if ($fp)
            {
                fwrite($fp, $message);
                fclose($fp);
            }
        }
    }

    /**
    * Builds the php array structure to be sent to the pinba server.
    */
    protected static function get_packet_info($script_name=null)
    {
        $struct = static::get_info();
        // massage info into correct format for pinba server
        $struct["status"] = 0; /// @todo
        $struct["hostname"] = self::$hostname;
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
        $struct["timer_hit_count"] = array();
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
        return $struct;
    }

    /**
     * A function not in the pinba extension api, needed to calculate total req. time.
     */
    public static function init($time=null)
    {
        if (self::$start == null || $time != null)
        {
            if ($time == null)
            {
                $time = microtime(true);
            }
            self::$start = $time;
        }
        if (self::$hostname == null)
        {
            self::$hostname = gethostname();
        }
        if (self::$script_name == null && isset($_SERVER['SCRIPT_NAME']))
        {
            self::$script_name = $_SERVER['SCRIPT_NAME'];
        }
        if (self::$server_name == null && isset($_SERVER['SERVER_NAME']))
        {
            self::$server_name = $_SERVER['SERVER_NAME'];
        }
        if (!self::$shutdown_registered)
        {
            self::$shutdown_registered = true;
            register_shutdown_function('\PinbaPhp\Polyfill\Pinba::flush');
        }
    }
}
