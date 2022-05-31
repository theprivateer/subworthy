<?php

if ( ! function_exists('flash'))
{
    function flash($message)
    {
        session()->flash('flash', $message);
    }
}

if ( ! function_exists('timezone_list'))
{
    function timezone_list()
    {
        $timezone = array();
        $timestamp = time();

        foreach(timezone_identifiers_list(\DateTimeZone::ALL) as $key => $t) {
            date_default_timezone_set($t);
            $timezone[$key]['zone'] = $t;
            $timezone[$key]['GMT_difference'] =  date('P', $timestamp);
        }

        return collect($timezone)->sortBy('GMT_difference');
    }
}
