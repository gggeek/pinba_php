<?php
/**
 * Wrap the oo code into an emulation layer that implements the same API as the pinba extension
 *
 * @see http://pinba.org/wiki/Manual:PHP_extension
 * @author G. Giunta
 * @copyright (C) G. Giunta 2011 - 2022
 */

use PinbaPhp\Polyfill\PinbaFunctions as pinba;

// try to start time measurement as soon as we can
pinba::init();

if (!function_exists('pinba_timer_start')) {
    /**
     * Creates and starts new timer.
     *
     * @param array $tags an array of tags and their values in the form of "tag" => "value". Cannot contain numeric indexes for obvious reasons.
     * @param array $data optional array with user data, not sent to the server.
     * @param int $hit_count
     * @return resource|int Always returns new timer resource.
     */
    function pinba_timer_start($tags, $data = array(), $hit_count = 1)
    {
        return pinba::timer_start($tags, $data, $hit_count);
    }
}

if (!function_exists('pinba_timer_stop')) {
    /**
     * Stops the timer.
     *
     * @param resource|int $timer valid timer resource.
     * @return bool Returns true on success and false on failure (if the timer has already been stopped).
     */
    function pinba_timer_stop($timer)
    {
        return pinba::timer_stop($timer);
    }
}

if (!function_exists('pinba_timer_add')) {
    /**
     * Creates new timer. This timer is already stopped and have specified time value.
     *
     * @param array $tags an array of tags and their values in the form of "tag" => "value". Cannot contain numeric indexes for obvious reasons.
     * @param int $value timer value for new timer.
     * @param array $data optional array with user data, not sent to the server.
     * @return resource|int Always returns new timer resource.
     */
    function pinba_timer_add($tags, $value, $data = array())
    {
        return pinba::timer_add($tags, $value, $data);
    }
}

if (!function_exists('pinba_timer_delete')) {
    /**
     * Deletes the timer.
     *
     * @param resource|int $timer valid timer resource.
     * @return bool Returns true on success and false on failure.
     */
    function pinba_timer_delete($timer)
    {
        return pinba::timer_delete($timer);
    }
}

if (!function_exists('pinba_timer_tags_merge')) {
    /**
     * Merges $tags array with the timer tags replacing existing elements.
     *
     * @param resource|int $timer - valid timer resource
     * @param array $tags - an array of tags.
     * @return bool
     */
    function pinba_timer_tags_merge($timer, $tags)
    {
        return pinba::timer_tags_merge($timer, $tags);
    }
}

if (!function_exists('pinba_timer_tags_replace')) {
    /**
     * Replaces timer tags with the passed $tags array.
     *
     * @param resource|int $timer - valid timer resource
     * @param array $tags - an array of tags.
     * @return bool
     */
    function pinba_timer_tags_replace($timer, $tags)
    {
        return pinba::timer_tags_replace($timer, $tags);
    }
}

if (!function_exists('pinba_timer_data_merge')) {
    /**
     * Merges $data array with the timer user data replacing existing elements.
     *
     * @param resource|int $timer valid timer resource
     * @param array $data an array of user data.
     * @return bool Returns true on success and false on failure.
     */
    function pinba_timer_data_merge($timer, $data)
    {
        return pinba::timer_data_merge($timer, $data);
    }
}

if (!function_exists('pinba_timer_data_replace')) {
    /**
     * Replaces timer user data with the passed $data array.
     * Use NULL value to reset user data in the timer.
     *
     * @param resource|int $timer valid timer resource
     * @param array $data an array of user data.
     * @return bool Returns true on success and false on failure.
      */
    function pinba_timer_data_replace($timer, $data)
    {
        return pinba::timer_data_replace($timer, $data);
    }
}

if (!function_exists('pinba_timer_get_info')) {
    /**
     * Returns timer data.
     *
     * @param resource|int $timer - valid timer resource.
     * @return array Output example:
     *    array(4) {
     *        ["value"]=> float(0.0213)
     *        ["tags"]=> array(1) {
     *            ["foo"]=> string(3) "bar"
     *        }
     *        ["started"]=> bool(true)
     *        ["data"]=> NULL
     *    }
     */
    function pinba_timer_get_info($timer)
    {
        return pinba::timer_get_info($timer);
    }
}

if (!function_exists('pinba_timers_stop')) {
    /**
     * Stops all running timers.
     *
     * @return bool
     */
    function pinba_timers_stop()
    {
        return pinba::timers_stop();
    }
}

if (!function_exists('pinba_timers_get')) {
    /**
     * Get all timers info.
     *
     * @param int $flag - can be set to PINBA_ONLY_STOPPED_TIMERS
     * @return array
     */
    function pinba_timers_get($flag = 0)
    {
        return pinba::timers_get($flag);
    }
}

if (!function_exists('pinba_get_info')) {
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
     * }
     */
    function pinba_get_info()
    {
        return pinba::get_info();
    }
}

if (!function_exists('pinba_schema_set')) {
    /**
     * Set request schema (HTTP/HTTPS/whatever).
     *
     * @param string $schema
     * @return bool
     */
    function pinba_schema_set($schema)
    {
        return pinba::schema_set($schema);
    }
}

if (!function_exists('pinba_script_name_set')) {
    /**
     * Set custom script name instead of $_SERVER['SCRIPT_NAME'] used by default.
     * Useful for those using front controllers, when all requests are served by one PHP script.
     *
     * @param string $script_name
     * @return bool
     */
    function pinba_script_name_set($script_name)
    {
        return pinba::script_name_set($script_name);
    }
}

if (!function_exists('pinba_server_name_set')) {
    /**
     * Set custom server name instead of $_SERVER['SERVER_NAME'] used by default.
     *
     * @param string $server_name
     * @return bool
     */
    function pinba_server_name_set($server_name)
    {
        return pinba::server_name_set($server_name);
    }
}

if (!function_exists('pinba_request_time_set')) {
    /**
     * Set custom Set custom request time.
     *
     * @param float $request_time
     * @return bool
     */
    function pinba_request_time_set($request_time)
    {
        return pinba::request_time_set($request_time);
    }
}

if (!function_exists('pinba_tag_set')) {
    /**
     * @param string $tag
     * @param string $value
     * @return bool
     */
    function pinba_tag_set($tag, $value)
    {
        return pinba::tag_set($tag, $value);
    }
}

if (!function_exists('pinba_tag_get')) {
    /**
     * @param string $tag
     * @return string
     */
    function pinba_tag_get($tag)
    {
        return pinba::tag_get($tag);
    }
}

if (!function_exists('pinba_tag_delete')) {
    /**
     * @param string $tag
     * @return bool
     */
    function pinba_tag_delete($tag)
    {
        return pinba::tag_delete($tag);
    }
}

if (!function_exists('pinba_tags_get')) {
    /**
     * @return array
     */
    function pinba_tags_get()
    {
        return pinba::tags_get();
    }
}

if (!function_exists('pinba_hostname_set')) {
    /**
     * Set custom hostname instead of the result of gethostname() used by default.
     *
     * @param string $hostname
     * @return bool
     */
    function pinba_hostname_set($hostname)
    {
        return pinba::hostname_set($hostname);
    }
}

if (!function_exists('pinba_flush')) {
    /**
     * Useful when you need to send request data to the server immediately (for long running scripts).
     * You can use optional argument script_name to set custom script name.
     *
     * @param string $script_name
     * @param int $flags Possible values (it's a bitmask, so you can add the constants):
     *                   PINBA_FLUSH_ONLY_STOPPED_TIMERS - flush only stopped timers (by default all existing timers are stopped and flushed)
     *                   PINBA_FLUSH_RESET_DATA - reset common request
     */
    function pinba_flush($script_name = null, $flags = 0)
    {
        pinba::flush($script_name, $flags);
    }
}

if (!defined('PINBA_FLUSH_ONLY_STOPPED_TIMERS')) {
    define('PINBA_FLUSH_ONLY_STOPPED_TIMERS', Pinba::PINBA_FLUSH_ONLY_STOPPED_TIMERS);
}

if (!defined('PINBA_FLUSH_RESET_DATA')) {
    define('PINBA_FLUSH_RESET_DATA', Pinba::PINBA_FLUSH_RESET_DATA);
}

if (!defined('PINBA_ONLY_RUNNING_TIMERS')) {
    define('PINBA_ONLY_RUNNING_TIMERS', Pinba::PINBA_ONLY_RUNNING_TIMERS);
}

if (!defined('PINBA_AUTO_FLUSH')) {
    define('PINBA_AUTO_FLUSH', Pinba::PINBA_AUTO_FLUSH);
}

if (!defined('PINBA_ONLY_STOPPED_TIMERS')) {
    define('PINBA_ONLY_STOPPED_TIMERS', Pinba::PINBA_ONLY_STOPPED_TIMERS);
}

if (!class_exists('PinbaClient')) {
    class_alias('PinbaPhp\Polyfill\PinbaClient', 'PinbaClient');
}
