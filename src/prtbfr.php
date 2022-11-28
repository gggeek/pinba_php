<?php
/**
 * Not to rely on external libraries, we implement as little as possible of Protocol Buffers
 * Encoding code courtesy of Protobuf for PHP lib by Iván -DrSlump- Montes: http://github.com/drslump/Protobuf-PHP
 *
 * @author G. Giunta
 * @copyright (C) G. Giunta 2011 - 2022
 *
 * @todo find a better name for this helper class...
 */

namespace PinbaPhp\Polyfill;

use Exception;

class Prtbfr
{
    const WIRETYPE_VARINT = 0;
    const WIRETYPE_FIXED64 = 1;
    const WIRETYPE_LENGTH_DELIMITED = 2;
    const WIRETYPE_START_GROUP = 3; // deprecated
    const WIRETYPE_END_GROUP = 4; // deprecated
    const WIRETYPE_FIXED32 = 5;

    const ELEMENT_REQUIRED = 'required';
    const ELEMENT_OPTIONAL = 'optional';
    const ELEMENT_REPEATED = 'repeated';

    const TYPE_DOUBLE   = 1;
    const TYPE_FLOAT    = 2;
    const TYPE_INT64    = 3;
    const TYPE_UINT64   = 4;
    const TYPE_INT32    = 5;
    const TYPE_FIXED64  = 6;
    const TYPE_FIXED32  = 7;
    const TYPE_BOOL     = 8;
    const TYPE_STRING   = 9;
    const TYPE_GROUP    = 10;
    const TYPE_MESSAGE  = 11;
    const TYPE_BYTES    = 12;
    const TYPE_UINT32   = 13;
    const TYPE_ENUM     = 14;
    const TYPE_SFIXED32 = 15;
    const TYPE_SFIXED64 = 16;
    const TYPE_SINT32   = 17;
    const TYPE_SINT64   = 18;
    const TYPE_UNKNOWN  = -1;

    const LITTLE_ENDIAN = 1;
    const BIG_ENDIAN = 2;
    protected static $_endianness = null;

    /**
    * Encodes a php array to protocol buffers format according to proto definition
    * Example proto definition:
    * array(
    *  1 => array("hostname", prtbfr::TYPE_STRING),
    *  10 => array("timer_hit_count", prtbfr::TYPE_INT, prtbfr::REPEATED)
    *  )
    *
    * @todo support encoding objects, not only arrays
    */
    public static function encode($struct, $proto)
    {
        $result = '';
        ksort($proto, SORT_NUMERIC);
        foreach ($proto as $pos => $def)
        {
            $field = $def[0];
            $type = $def[1];
            $cardinality = isset($def[2]) ? $def[2] : self::ELEMENT_REQUIRED;
            switch($cardinality)
            {
                case self::ELEMENT_OPTIONAL:
                    if (isset($struct[$field]) && $struct[$field] !== null)
                    {
                        $result .= self::encode_value($struct[$field], $type, $pos);
                    }
                    break;
                case self::ELEMENT_REPEATED:
                    foreach($struct[$field] as $value)
                    {
                        $result .= self::encode_value($value, $type, $pos);
                    }
                    break;
                default:
                    $result .= self::encode_value($struct[$field], $type, $pos);
            }
        }
        return $result;
    }

    protected static function encode_value($value, $type, $position)
    {
        $wiretype = self::wiretype($type);
        $header = self::varint_encode(($position << 3) | $wiretype);

        switch($type)
        {
            case self::TYPE_INT64:
            case self::TYPE_UINT64:
            case self::TYPE_INT32:
            case self::TYPE_UINT32:
            case self::TYPE_BOOL: // casting bools to integers is correct
                $value = (integer)$value;
                $value = self::varint_encode($value);
                break;
            case self::TYPE_SINT32: // ZigZag
            case self::TYPE_SINT64: // ZigZag
                $value = (integer)$value;
                $value = ($value >> 1) ^ (-($value & 1));
                $value = self::varint_encode($value);
                break;
            case self::TYPE_DOUBLE:
                $value = self::double_encode($value);
                break;
            case self::TYPE_FIXED64:
                $value = self::fixed64_encode($value);
                break;
            case self::TYPE_SFIXED64:
                $value = self::sfixed64_encode($value);
                break;
            case self::TYPE_FLOAT:
                $value = (float)$value;
                $value = self::float_encode($value);
                break;
            case self::TYPE_FIXED32:
                $value = self::fixed32_encode($value);
                break;
            case self::TYPE_SFIXED32:
                $value = self::sfixed32_encode($value);
                break;
            case self::TYPE_STRING:
            case self::TYPE_BYTES:
                $value = (string)$value;
                $value = self::varint_encode(strlen($value)) . $value;
                break;
            case self::TYPE_ENUM:
                $value = self::varint_encode($value);
                break;
            default:
                throw new Exception("Unknown field type $type");
        }

        return $header . $value;
    }

    protected static function varint_encode($value)
    {
        if ($value < 0) throw new Exception("$value is negative");

        if ($value < 128) {
            return chr($value);
        }

        $values = array();
        while ($value !== 0)
        {
            $values[] = 0x80 | ($value & 0x7f);
            $value = $value >> 7;
        }
        $values[count($values)-1] &= 0x7f;

        $bytes = implode('', array_map('chr', $values));
        return $bytes;
    }

    protected static function sfixed32_encode($value)
    {
        $bytes = pack('l*', $value);
        if (self::isBigEndian())
        {
            $bytes = strrev($bytes);
        }
        $this->write($bytes, 4);
    }

    protected static function fixed32_encode($value)
    {
        $bytes = pack('N*', $value);
        $this->write($bytes, 4);
    }

    protected static function sfixed64_encode($value)
    {
        $bytes = pack('V*', $value & 0xffffffff, $value / (0xffffffff+1));
        $this->write($bytes, 8);
    }

    protected static function fixed64_encode($value)
    {
        return self::sfixed64_encode($value);
    }

    protected static function float_encode($value)
    {
        $bytes = pack('f*', $value);
        if (self::isBigEndian())
        {
            $bytes = strrev($bytes);
        }
        $this->write($bytes, 4);
    }

    protected static function double_encode($value)
    {
        $bytes = pack('d*', $value);
        if (self::isBigEndian())
        {
            $bytes = strrev($bytes);
        }
        $this->write($bytes, 8);
    }

    protected static function wiretype($type, $wire=null)
    {
        switch ($type)
        {
            case self::TYPE_INT32:
            case self::TYPE_INT64:
            case self::TYPE_UINT32:
            case self::TYPE_UINT64:
            case self::TYPE_SINT32:
            case self::TYPE_SINT64:
            case self::TYPE_BOOL:
            case self::TYPE_ENUM:
                return self::WIRETYPE_VARINT;
            case self::TYPE_FIXED64:
            case self::TYPE_SFIXED64:
            case self::TYPE_DOUBLE:
                return self::WIRETYPE_FIXED64;
            case self::TYPE_STRING:
            case self::TYPE_BYTES:
            case self::TYPE_MESSAGE:
                return self::WIRETYPE_LENGTH_DELIMITED;
            case self::TYPE_FIXED32:
            case self::TYPE_SFIXED32:
            case self::TYPE_FLOAT:
                return self::WIRETYPE_FIXED32;
            default:
                // Unknown fields just return the reported wire type
                return $wire;
        }
    }

    protected static function isBigEndian()
    {
        if (self::$_endianness === NULL)
        {
            list(,$result) = unpack('L', pack('V', 1));
            if ($result === 1)
            {
                self::$_endianness = self::LITTLE_ENDIAN;
            }
            else
            {
                self::$_endianness = self::BIG_ENDIAN;
            }
        }
        return self::$_endianness === self::BIG_ENDIAN;
    }
}
