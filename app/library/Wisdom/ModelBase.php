<?php

namespace Wisdom;

class ModelBase extends \Phalcon\Mvc\Model
{

    /**
     * @param mixed $parameters
     * @return static
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

    protected static function di()
    {
        return \Phalcon\Di::getDefault();
    }

    protected static function getOrdinalKey()
    {
        $config = self::di()['config'];
        return $config->rest->ordinalkey;
    }

    protected static function getConfig()
    {
        $config = self::di()['config'];
        return $config;
    }

    protected static function adjustDbDate(string $date, string $timezone = null)
    {
        $tool = self::di()['tool'];
        return $tool->adjustDbDate($date, $timezone);
    }

    protected static function adjustAppDate(string $date, string $timezone = null)
    {
        $tool = self::di()['tool'];
        return $tool->adjustAppDate($date, $timezone);
    }

    protected static function arrayWalkRecursiveDelete(array &$array, callable $callback)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::arrayWalkRecursiveDelete($value, $callback);
            }
            if ($callback($value, $key)) {
                unset($array[$key]);
            }
        }
        return $array;
    }

    public static function getSourceStatic()
    {
        $self = new static();
        return $self->getSource();
    }

    public static function prepareArray($mixed, string $key = 'id', bool $ignoreNullAndEmptyArray = false)
    {
        if (!$mixed) {
            return null;
        }
        $result = [];
        $array  = [];
        if (is_array($mixed)) {
            $array = $mixed;
        } elseif (is_object($mixed)) {
            if (get_class($mixed) !== 'Phalcon\Mvc\Model\Resultset\Simple') {
                $array = $mixed->toArray();
            } else {
                foreach ($mixed as $mixedValue) {
                    array_push($array, $mixedValue->toArray());
                }
            }
        }
        if (isset($array[$key])) {
            $result = $array;
        } else {
            $ordinalKey          = self::getOrdinalKey();
            $result[$ordinalKey] = [];
            foreach ($array as $arrayValue) {
                if (!isset($arrayValue[$key])) {
                    continue;
                }
                array_push($result[$ordinalKey], $arrayValue[$key]);
                $result[$arrayValue[$key]] = $arrayValue;
            }
        }
        if ($ignoreNullAndEmptyArray) {
            $result = self::arrayWalkRecursiveDelete($result, function ($value, $key) {
                if (is_array($value)) {
                    return empty($value);
                }
                return $value === null;
            });
        }
        return $result;
    }

    public static function prepareMMArray($mixed, string $key, string $value, bool $ignoreNullAndEmptyArray = false)
    {
        $result = [];
        $array  = [];
        if (is_array($mixed)) {
            $array = $mixed;
        } elseif (is_object($mixed)) {
            if (get_class($mixed) === static::class) {
                return false;
            } else {
                foreach ($mixed as $mixedValue) {
                    array_push($array, $mixedValue->toArray());
                }
            }
        }
        if (isset($array[$key])) {
            return false;
        } else {
            foreach ($array as $arrayValue) {
                if (!isset($arrayValue[$key], $arrayValue[$value])) {
                    continue;
                }
                if (!isset($result[$arrayValue[$key]])) {
                    $result[$arrayValue[$key]] = [];
                }
                array_push($result[$arrayValue[$key]], $arrayValue[$value]);
            }
        }
        return $result;
    }

    public static function prepareMMArrayNew($mixed, string $key, bool $ignoreNullAndEmptyArray = false)
    {
        $result = [];
        $array  = [];
        if (is_array($mixed)) {
            $array = $mixed;
        } elseif (is_object($mixed)) {
            if (get_class($mixed) === static::class) {
                return false;
            } else {
                foreach ($mixed as $mixedValue) {
                    array_push($array, $mixedValue->toArray());
                }
            }
        }
        if (isset($array[$key])) {
            return false;
        } else {
            foreach ($array as $arrayValue) {
                if (!isset($arrayValue[$key])) {
                    continue;
                }
                if (!isset($result[$arrayValue[$key]])) {
                    $result[$arrayValue[$key]] = [];
                }
                array_push($result[$arrayValue[$key]], $arrayValue);
            }
        }
        return $result;
    }
}
