<?php
/**
 * A class implementing the pinba extension functionality, in pure php - function API
 *
 * @see http://pinba.org/wiki/Manual:PHP_extension
 * @see https://github.com/tony2001/pinba_engine/wiki/PHP-extension#functions
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
            "value" => microtime(true),
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

    /**
     * Stops all running timers.
     *
     * @return bool
     */
    public static function timers_stop()
    {
        $time = microtime(true);
/// @todo check: does this work as intended?
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
        return self::instance()->_get_info();
    }

    /**
     * Set request schema (HTTP/HTTPS/whatever).
     *
     * @param string $schema
     * @return bool
     */
    public static function schema_set($schema)
    {
        return self::instance()->setSchema($schema);
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
        return self::instance()->setScriptname($script_name);
    }

    /**
     * Set custom server name instead of $_SERVER['SERVER_NAME'] used by default.
     *
     * @param string $server_name
     * @return bool
     */
    public static function server_name_set($server_name)
    {
        return self::instance()->setServername($server_name);
    }

    /**
     * Set custom Set custom request time.
     *
     * @param float $request_time
     * @return bool
     */
    public static function request_time_set($request_time)
    {
        return self::instance()->setRequestTime($request_time);
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
        return self::instance()->setHostname($hostname);
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

                /// q:should we stop timers even if the socket can not be opened?
                if (!($flags & PINBA_FLUSH_ONLY_STOPPED_TIMERS)) {
                    self::timers_stop();
                }
                $info = $i->_get_info();
                if ($flags & PINBA_FLUSH_ONLY_STOPPED_TIMERS) {
                    foreach($info['timers'] as $id => $timer) {
                        if ($timer['started']) {
                            unset($info['timers'][$id]);
                        }
                    }
                }

                $struct = $i->getPacketInfo($info, $script_name);
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

    public static function reset()
    {
        $i = self::instance();
        $i->timers = array();
        $i->tags = array();
        $i->request_time = microtime(true);
        $i->document_size = null;
        $i->memory_peak = null;
        $i->request_count = 1;
        $i->rusage = array();
    }

    // *** End of Pinba API ***

    /// Make this class a singleton: private constructor
    protected function __construct()
    {
    }

    /**
     * Make this class a singleton: factory
     * @return PinbaFunctions we can not instantiate a Pinba object, as it has been declared abstract
     */
    protected static function instance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * A function not in the pinba extension api, needed to calculate total req. time and to insure we flush at end of
     * script execution. To be called as close as possible to the beginning of the main script.
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
            $i->setRequestTime($time);
        }
        if (!self::$shutdown_registered)
        {
            self::$shutdown_registered = true;
            register_shutdown_function(array($i, 'flush'));
        }
    }
}