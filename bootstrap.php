<?php
/**
 * Wrap the oo code into an emulation layer that implements the same API as the pinba extension
 *
 * @see http://pinba.org/wiki/Manual:PHP_extension
 * @author G. Giunta
 * @copyright (C) G. Giunta 2011 - 2022
 */

use PinbaPHP\Polyfill\Pinba as pinba;

// try to start time measurement as soon as we can
pinba::init();

if (!function_exists('pinba_timer_start')) {
    /**
     * Creates and starts new timer.
     *
     * @param array $tags an array of tags and their values in the form of "tag" => "value". Cannot contain numeric indexes for obvious reasons.
     * @param array $data optional array with user data, not sent to the server.
     * @return resource Always returns new timer resource.
     */
    function pinba_timer_start($tags, $data = array())
    {
        return pinba::timer_start($tags, $data);
    }
}

if (!function_exists('pinba_timer_stop')) {
    /**
     * Stops the timer.
     *
     * @param resource $timer valid timer resource.
     * @return bool Returns true on success and false on failure (if the timer has already been stopped).
     */
    function pinba_timer_stop($timer)
    {
        return pinba::timer_stop($timer);
    }
}

if (!function_exists('pinba_timer_delete')) {
    /**
     * Deletes the timer.
     *
     * Available since: 0.0.6
     *
     * @param resource $timer valid timer resource.
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
     * @param resource $timer - valid timer resource
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
     * @param resource $timer - valid timer resource
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
     * @param resource $timer valid timer resource
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
     * @param resource $timer valid timer resource
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

if (!function_exists('pinba_get_info')) {
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
    function pinba_get_info()
    {
        return pinba::get_info();
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
     */
    function pinba_flush($script_name = null)
    {
        return pinba::flush($script_name);
    }
}
