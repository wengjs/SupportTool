<?php

namespace Wjs\Support\Ldap;

class SearchFilterBuilder
{

    protected $operator = null;

    protected $conditions = array();

    public static function equal($attribute, $value)
    {
        return $attribute.'='.$value;
    }

    public static function custom($attribute, $operator, $value)
    {
        return $attribute.$operator.$value;
    }

    public static function notEqual($attribute, $value)
    {
        return '!('.static::equal($attribute, $value).')';
    }

    public static function notCustom($attribute, $operator, $value)
    {
        return '!('.static::custom($attribute, $operator, $value).')';
    }

    public function __construct($raw_conditions = null)
    {
        if ( ! empty($raw_conditions)) {
            $this->conditions[] = $raw_conditions;
        }
    }

    public function reset()
    {
        $this->operator = null;
        $this->conditions = array();
        return $this;
    }

    public function toString()
    {
        if (1 < count($this->conditions)) {
            $string = $this->operator.'('.join(')(', $this->conditions).')';
        } else {
            $string = array_pop($this->conditions);
        }

        $this->conditions = array($string);

        return $string;
    }

    public function toQueryString()
    {
        return '('.$this->toString().')';
    }

    public function organize($operator, $condition)
    {
        if ( ! empty($this->operator) and $operator !== $this->operator) {
            $this->conditions = array($this->toString());
        }

        $this->conditions[] = $condition;

        if (1 < count($this->conditions)) {
            $this->operator = $operator;
        }
    }

}
