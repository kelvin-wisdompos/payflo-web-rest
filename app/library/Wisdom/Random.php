<?php

namespace Wisdom;

class Random
{
    public static function string($option = [])
    {
        $lib = $option['lib'] ?? 'QWERTYUIOPASDFGHJKLZXCVBNM0123456789qwertyuiopasdfghjklzxcvbnm';
        $len = $option['len'] ?? 5;
        $str = '';
        for ($i=0;$i < (int) $len; $i++) {
            $str .= substr($lib, rand(0, strlen($lib) - 2), 1);
        }
        return $str;
    }
    
    public static function number($option = [])
    {
        return self::string(array_merge($option, ['lib'=>'0123456798']));
    }
}
