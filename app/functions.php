<?php

if (! function_exists('flash')) {
    function flash(string $message)
    {
        session()->flash('flash', $message);
    }
}

if (! function_exists('timezone_list')) {
    function timezone_list()
    {
        $timezone = array();
        $timestamp = time();

        // date_default_timezone_set mutates global PHP state temporarily so date('P') returns
        // the UTC offset for each zone. The calling code must not be time-sensitive.
        foreach (timezone_identifiers_list(\DateTimeZone::ALL) as $key => $t) {
            date_default_timezone_set($t);
            $timezone[$key]['zone'] = $t;
            $timezone[$key]['GMT_difference'] =  date('P', $timestamp);
        }

        return collect($timezone)->sortBy('GMT_difference');
    }
}
