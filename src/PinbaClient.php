<?php
/**
 * A class implementing the pinba extension functionality, in pure php - object API
 *
 * @see http://pinba.org/wiki/Manual:PHP_extension
 * @see https://github.com/tony2001/pinba_engine/wiki/PHP-extension#PinbaClient_class
 * @author G. Giunta
 * @copyright (C) G. Giunta 2022
 */

namespace PinbaPhp\Polyfill;

class PinbaClient extends Pinba
{
    protected $servers = array();
    protected $flags;
    protected $dataSent = false;

    /**
     * @param string[] $servers
     * @param int $flags Possible flags:
     *                   PINBA_FLUSH_ONLY_STOPPED_TIMERS - flush only stopped timers
     *                   PINBA_FLUSH_RESET_DATA - reset request data
     *                   PINBA_AUTO_FLUSH - send data automatically when the object is destroyed
     */
    public function __construct($servers, $flags = 0)
    {
        if (!$servers) {
            // we log a warning. Native ext segfaults in this case
            trigger_error("PinbaClient::__construct() expects parameter 1 to be a non empty array", E_USER_WARNING);
        }
        $this->servers = $servers;
        $this->flags = $flags;
    }

    public function __destruct()
    {
        if ($this->flags & pinba::AUTO_FLUSH && ! $this->dataSent) {
            $this->send();
        }
    }

    public function setRequestCount($request_count)
    {
        $this->request_count = $request_count;
        return true;
    }

    public function setMemoryFootprint($memory_footprint)
    {
        $this->memory_footprint = $memory_footprint;
        return true;
    }

    public function setMemoryPeak($memory_peak)
    {
        $this->memory_peak = $memory_peak;
        return true;
    }

    public function setDocumentSize($document_size)
    {
        $this->document_size = $document_size;
        return true;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return true;
    }

    public function setRusage($rusage)
    {
        if (count($rusage) !== 2) {
            trigger_error("rusage array must contain exactly 2 elements", E_USER_WARNING);
            return false;
        }

        $this->rusage = $rusage;
        return true;
    }

    public function setTag($name, $value)
    {
        $this->tags[$name] = (string)$value;
    }

    public function setTimer($tags, $value, $rusage = array(), $hit_count = 1)
    {
        return $this->upsertTimer(false, $tags, $value, $rusage, $hit_count);
    }

    public function addTimer($tags, $value, $rusage = array(), $hit_count = 1)
    {
        return $this->upsertTimer(true, $tags, $value, $rusage, $hit_count);
    }

    protected function upsertTimer($add, $tags, $value, $rusage = array(), $hit_count = 1)
    {
        if (!is_array($tags)) {
            trigger_error("setTimer() expects parameter 1 to be array, " . gettype($tags) . " given", E_USER_WARNING);
            return false;
        }
        if (!self::verifyTags($tags))
        {
            return false;
        }
        if ($hit_count <= 0) {
            trigger_error("hit_count must be greater than 0 ($hit_count was passed)", E_USER_WARNING);
            return false;
        }
        if ($value < 0) {
            trigger_error("negative time value passed ($value), changing it to 0", E_USER_WARNING);
            $value = 0;
        }

        $tagsHash = $tags;
        ksort($tagsHash);
        $tagsHash = md5(var_export($tagsHash, true));

        if ($add && isset($this->timers[$tagsHash])) {
            // no need to update 'tags' (same value) nor 'started' (client timers are always stopped)
            /// @todo what about 'deleted' ?
            $this->timers[$tagsHash]['value'] = $this->timers[$tagsHash]['value'] + $value;
            $this->timers[$tagsHash]['hit_count'] = $this->timers[$tagsHash]['hit_count'] + $hit_count;
        } else {
            $this->timers[$tagsHash] = array(
                "value" => $value,
                "tags" => $tags,
                "started" => false,
                "data" => null,
                "hit_count" => $hit_count,
                "deleted" => false
            );
        }
    }

    /**
     * @param null|int $flags - optional flags, bitmask. Override object flags if specified. NB: 0 != null
     * @return bool
     */
    public function send($flags = null)
    {
        if (!count($this->servers)) {
            return false;
        }

        $message = $this->getData($flags);

        $out = true;
        foreach($this->servers as $server) {
            $out = $out & self::_send($server, $message);
        }

        $this->dataSent = true;

        return $out;
    }

    /**
     * Returns raw packet data. This is basically a copy of send(), but instead of sending it just returns the data.
     * @param null|int $flags - optional flags, bitmask. Override object flags if specified. NB: 0 != null
     * @return string
     */
    public function getData($flags = null)
    {
        if ($flags === null) {
            $flags = $this->flags;
        }

        if (!($flags & self::FLUSH_ONLY_STOPPED_TIMERS)) {
            $this->stopTimers(microtime(true));
        }

        $info = $this->getInfo(false, $flags);

        return $this->getPacket($info);
    }
}
