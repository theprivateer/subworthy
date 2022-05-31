<?php

namespace App\Formatters;

interface FormatterContract
{
    public function render($raw): string;
}
