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

    /**
     * @param string[] $servers
     * @param int $flags
     */
    public function __construct($servers, $flags = 0)
    {
        $this->servers = $servers;
        $this->flags = $flags;
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
        $this->rusage = $rusage;
        return true;
    }

    public function setTag($name, $value)
    {
/// @todo
    }

    public function setTimer($tags, $value, $rusage = array(), $hit_count = null)
    {
/// @todo
    }

    public function addTimer($tags, $value, $rusage = array(), $hit_count = null)
    {
/// @todo
    }

    public function send($flags = 0)
    {
/// @todo
    }

    public function getData($flags = 0)
    {
/// @todo
    }
}
