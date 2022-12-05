<?php
/**
 * A class implementing the pinba extension functionality, in pure php
 *
 * @see http://pinba.org/wiki/Manual:PHP_extension
 * @author G. Giunta
 * @copyright (C) G. Giunta 2011 - 2022
 */

namespace PinbaPhp\Polyfill;

class PinbaFunctions extends Pinba
{
    protected static $instance;
    protected static $shutdown_registered = false;

    /**
     * Creates and starts new timer.
     *
     * @param array $tags an array of tags and their values in the form of "tag" => "value". Cannot contain numeric indexes for obvious reasons.
     * @param array $data optional array with user data, not sent to the server.
     * @return int Always returns new timer resource.
     *
     * @todo support $hit_count
     */
    public static function timer_start($tags, $data = null, $hit_count = 1)
    {
        $time = microtime(true);

        if (!is_array($tags)) {
            trigger_error("pinba_timer_start() expects parameter 1 to be array, " . gettype($tags) . " given", E_USER_WARNING);
            return null;
        }
        foreach($tags as $key => $val) {
            if (! is_string($key)) {
                trigger_error(' pinba_timer_start(): tags can only have string names (i.e. tags array cannot contain numeric indexes)', E_USER_WARNING);
                return null;
            }
        }
        if ($data !== null && !is_array($data)) {
            trigger_error("pinba_timer_start() expects parameter 2 to be array, " . gettype($data) . " given", E_USER_WARNING);
            return null;
        }

        $i = self::instance();
        $timer = count($i->timers);
        $i->timers[$timer] = array(
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
     * @param int $timer valid timer resource.
     * @return bool Returns true on success and false on failure (if the timer has already been stopped).
     */
    public static function timer_stop($timer)
    {
        if (!is_int($timer)) {
            trigger_error("pinba_timer_stop() expects parameter 1 to be int, " . gettype($timer) . " given", E_USER_WARNING);
            return false;
        }
        $time = microtime(true);
        $i = self::instance();
        if (isset($i->timers[$timer]))
        {
            if ($i->timers[$timer]["started"])
            {
                $i->timers[$timer]["started"] = false;
                $i->timers[$timer]["value"] = $time - $i->timers[$timer]["value"];
                return true;
            }
        }
        return false;
    }

    /**
     * Creates new timer. This timer is already stopped and has specified time value.
     *
     * @param array $tags an array of tags and their values in the form of "tag" => "value". Cannot contain numeric indexes for obvious reasons.
     * @param int $value timer value for new timer.
     * @param array $data optional array with user data, not sent to the server.
     * @return int Always returns new timer resource.
     */
    public static function timer_add($tags, $value, $data = null)
    {
        if (!is_array($tags)) {
            trigger_error("pinba_timer_add() expects parameter 1 to be array, " . gettype($tags) . " given", E_USER_WARNING);
            return null;
        }
        foreach($tags as $key => $val) {
            if (! is_string($key)) {
                trigger_error(' pinba_timer_add(): tags can only have string names (i.e. tags array cannot contain numeric indexes)', E_USER_WARNING);
                return null;
            }
        }
        if ($data !== null && !is_array($data)) {
            trigger_error("pinba_timer_add() expects parameter 3 to be array, " . gettype($data) . " given", E_USER_WARNING);
            return null;
        }

        $i = self::instance();
        $timer = count($i->timers);
        $i->timers[$timer] = array(
            "value" => $value,
            "tags" => $tags,
            "started" => false,
            "data" => $data
        );
        return $timer;
    }

    /**
     * Deletes the timer.
     *
     * @param int $timer valid timer resource.
     * @return bool Returns true on success and false on failure.
     */
    public static function timer_delete($timer)
    {
        $i = self::instance();
        if (isset($i->timers[$timer]))
        {
            unset($i->timers[$timer]);
            return true;
        }
        return false;
    }

    /**
     * Merges $tags array with the timer tags replacing existing elements.
     *
     * @param int $timer - valid timer resource
     * @param array $tags - an array of tags.
     * @return bool
     */
    public static function timer_tags_merge($timer, $tags)
    {
        /// @todo should we check for type of $tags?

        $i = self::instance();
        if (isset($i->timers[$timer]))
        {
            $i->timers[$timer]["tags"] = array_merge($i->timers[$timer]["tags"], $tags);
            return true;
        }
        return false;
    }

    /**
     * Replaces timer tags with the passed $tags array.
     *
     * @param int $timer - valid timer resource
     * @param array $tags - an array of tags.
     * @return bool
     */
    public static function timer_tags_replace($timer, $tags)
    {
        /// @todo should we check for type of $tags?

        $i = self::instance();
        if (isset($i->timers[$timer]))
        {
            $i->timers[$timer]["tags"] = $tags;
            return true;
        }
        return false;
    }

    /**
     * Merges $data array with the timer user data replacing existing elements.
     *
     * @param int $timer valid timer resource
     * @param array $data an array of user data.
     * @return bool Returns true on success and false on failure.
     */
    public static function timer_data_merge($timer, $data)
    {
        /// @todo should we check for type of $data?

        $i = self::instance();
        if (isset($i->timers[$timer]))
        {
            $i->timers[$timer]["data"] = array_merge($i->timers[$timer]["data"], $data);
            return true;
        }
        return false;
    }

    /**
     * Replaces timer user data with the passed $data array.
     * Use NULL value to reset user data in the timer.
     *
     * @param int $timer valid timer resource
     * @param array $data an array of user data.
     * @return bool Returns true on success and false on failure.
     */
    public static function timer_data_replace($timer, $data)
    {
        /// @todo should we check for type of $data?

        $i = self::instance();
        if (isset($i->timers[$timer]))
        {
            $i->timers[$timer]["data"] = $data;
            return true;
        }
        return false;
    }

    /**
     * Returns timer data.
     *
     * @param int $timer - valid timer resource.
     * @return array|false Output example:
     * array(4) {
     *     ["value"]=> float(0.0213)
     *     ["tags"]=> array(1) {
     *         ["foo"]=> string(3) "bar"
     *     }
     *     ["started"]=> bool(true)
     *     ["data"]=> NULL
     * }
     */
    public static function timer_get_info($timer)
    {
        $time = microtime(true);
        return self::instance()->_timer_get_info($timer, $time);
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

    /**
     * Stops all running timers.
     *
     * @return bool
     */
    public static function timers_stop()
    {
        $time = microtime(true);
/// @todo check
        foreach (self::instance()->timers as &$timer)
        {
            if ($timer["started"])
            {
                $timer["started"] = false;
                $timer["value"] = $time - $timer["value"];
            }
        }
        return true;
    }

    /**
     * Get all timers info.
     *
     * @param int $flag
     * @return array
     */
    public static function timers_get($flag = 0)
    {
        $time = microtime(true);
        $out = array();
        $i = self::instance();
        foreach($i->timers as $id => $timer) {
            if (!($flag & self::PINBA_ONLY_STOPPED_TIMERS) || $timer['started'] === false) {
                $out[] = $i->_timer_get_info($id, $time);
            }
        }
        return $out;
    }

    /**
     * Returns all request data (including timers user data).
     *
     * @return array Example:
     * array(9) {
     *     ["mem_peak_usage"]=> int(786432)
     *     ["req_time"]=> float(0.001529)
     *     ["ru_utime"]=> float(0)
     *     ["ru_stime"]=> float(0)
     *     ["req_count"]=> int(1)
     *     ["doc_size"]=> int(0)
     *     ["server_name"]=> string(7) "unknown"
     *     ["script_name"]=> string(1) "-"
     *     ["hostname"]=> string(3) "php"
     *     ["timers"]=> array(1) {
     *         [0]=> array(4) {
     *             ["value"]=> float(4.5E-5)
     *             ["tags"]=> array(1) {
     *                 ["foo"]=> string(3) "bar"
     *             }
     *             ["started"]=> bool(true)
     *             ["data"]=> NULL
     *         }
     *     }
     *     ["tags"] => array(0) {
     *     }
     * }
     */
    public static function get_info()
    {
        $i = self::instance();
        if ($i->hostname === null)
        {
            if (php_sapi_name() == 'cli')
            {
                $i->hostname = 'php';
            }
            else
            {
                $i->hostname = gethostname();
            }
        }
        if ($i->script_name === null && isset($_SERVER['SCRIPT_NAME']))
        {
            $i->script_name = $_SERVER['SCRIPT_NAME'];
        }
        if ($i->server_name === null && isset($_SERVER['SERVER_NAME']))
        {
            $i->server_name = $_SERVER['SERVER_NAME'];
        }

        /// @todo can we push timing measurement further close to end of execution?

        $time = microtime(true);
        $timers = array();
        foreach($i->timers as $id => $t)
        {
            $timers[] = $i->_timer_get_info($id, $time);
        }

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

        return array(
            'mem_peak_usage' => memory_get_peak_usage(true),
            'req_time' => $time - $i->request_time,
            'ru_utime' => $ruUtime,
            'ru_stime' => $ruStime,
            'req_count' => 1, /// @todo should we default to 0 ?
            'doc_size' => 0,
            'schema' => $i->schema,
            'server_name' => ($i->server_name != null ? $i->server_name : 'unknown'),
            'script_name' => ($i->script_name != null ? $i->script_name : 'unknown'),
            'hostname' => ($i->hostname != null ? $i->hostname : 'unknown'),
            'timers' => $timers,
            'tags' => $i->tags
        );
    }

    /**
     * Set request schema (HTTP/HTTPS/whatever).
     *
     * @param string $schema
     * @return bool
     */
    public static function schema_set($schema)
    {
        self::instance()->schema = $schema;
        return true;
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
        self::instance()->script_name = $script_name;
        return true;
    }

    /**
     * Set custom server name instead of $_SERVER['SERVER_NAME'] used by default.
     *
     * @param string $server_name
     * @return bool
     */
    public static function server_name_set($server_name)
    {
        self::instance()->server_name = $server_name;
        return true;
    }

    /**
     * Set custom Set custom request time.
     *
     * @param float $request_time
     * @return bool
     */
    public static function request_time_set($request_time)
    {
        self::instance()->request_time = $request_time;
        return true;
    }

    /**
     * @param string $tag
     * @param string $value
     * @return bool
     */
    public static function tag_set($tag, $value)
    {
        self::instance()->tags[$tag] = $value;
        return true;
    }

    /**
     * @param string $tag
     * @return string
     */
    public static function tag_get($tag)
    {
        /// @todo raise a warning if tag does not exists?

        return isset(self::instance()->tags[$tag]) ? self::instance()->tags[$tag] : null;
    }

    /**
     * @param string $tag
     * @return bool
     */
    public static function tag_delete($tag)
    {
        if (array_key_exists($tag, self::instance()->tags))
        {
            unset(self::instance()->tags[$tag]);
        }
        return false;
    }

    /**
     * @return array
     */
    public static function tags_get()
    {
        return self::instance()->tags;
    }

    /**
     * Set custom hostname instead of the result of gethostname() used by default.
     *
     * @param string $hostname
     * @return bool
     */
    public static function hostname_set($hostname)
    {
        self::instance()->hostname = $hostname;
        return true;
    }

    /**
     * Useful when you need to send request data to the server immediately (for long running scripts).
     * You can use optional argument script_name to set custom script name.
     *
     * @param string $script_name
     * @param int $flags Possible values (it's a bitmask, so you can add the constants):
     *                   PINBA_FLUSH_ONLY_STOPPED_TIMERS - flush only stopped timers (by default all existing timers are stopped and flushed)
     *                   PINBA_FLUSH_RESET_DATA - reset common request
     * @return bool false if extension is disabled, or if there are network issues
     *
     * @todo add IPv6 support for `pinba.server` (see http://pinba.org/wiki/Manual:PHP_extension)
     */
    public static function flush($script_name = null, $flags = 0)
    {
        if (self::ini_get('pinba.enabled'))
        {
            $server = self::ini_get('pinba.server');
            $port = 30002;
            if (count($parts = explode(':', $server)) > 1)
            {
                (int)$port = $parts[1];
                $server = $parts[0];
            }

            /// @todo should we log a more specific warning in case of failures to open the udp socket? f.e. the pinba
            ///       extension on invalid hostname triggers:
            ///       PHP Warning:  Unknown: failed to resolve Pinba server hostname 'xxx': Name or service not known in Unknown on line 0
            $fp = fsockopen("udp://$server", $port, $errno, $errstr);
            if ($fp)
            {
                $i = self::instance();
                $struct = $i->get_packet_info($script_name, $flags);
                $message = Prtbfr::encode($struct, self::$message_proto);

                $msgLen = strlen($message);
                $len = fwrite($fp, $message, $msgLen);
                fclose($fp);

                if ($flags & self::PINBA_FLUSH_RESET_DATA) {
                    self::reset();
                }

                return $msgLen == $len;
            }
        }

        return false;
    }

    /**
     * Builds the php array structure to be sent to the pinba server.
     * NB: depending on the value of $flags, it will stop all running timers
     */
    protected function get_packet_info($script_name = null, $flags = 0)
    {
        $struct = static::get_info();

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
        if (!($flags & self::PINBA_FLUSH_ONLY_STOPPED_TIMERS)) {
            $time = microtime(true);
        }
        // merge timers by tags
        $tags = array();
        foreach($struct["timers"] as $id => $timer)
        {
            if ($flags & self::PINBA_FLUSH_ONLY_STOPPED_TIMERS) {
                if ($timer['started']) {
                    unset($struct["timers"][$id]);
                    continue;
                }
            } else {
                // stop all running timers
/// @todo what if this is called _not_ on the global instance ? reset that one instead
                if ($timer['started']) {
                    $this->timers[$id]["started"] = false;
                    $this->timers[$id]["value"] = $time - $this->timers[$timer]["value"];
                }
            }

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

        /// @todo implement the following missing fields

        $struct["status"] = 0; /// @todo
        // $struct["memory_footprint"] = ...;
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

    public static function reset()
    {
        $i = self::instance();
        $i->timers = array();
        $i->tags = array();
        $i->request_time = microtime(true);
        /// @todo the C code resets as well doc_size, mem_peak_usage, req_count, ru_*,
    }

    // *** End of Pinba API ***

    /// make this class a singleton: private constructor
    protected function __construct()
    {
    }

    /**
     * Make this class a singleton: factory
     * @return PinbaFunctions
     */
    protected static function instance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * A function not in the pinba extension api, needed to calculate total req. time.
     */
    public static function init($time=null)
    {
        $i = self::instance();
        if ($i->request_time == null || $time != null)
        {
            if ($time == null)
            {
                $time = microtime(true);
            }
            $i->request_time = $time;
        }
        if (!self::$shutdown_registered)
        {
            self::$shutdown_registered = true;
            register_shutdown_function(array($i, 'flush'));
        }
    }
}
