<?php

namespace App\Filters;

use App\Models\Filter;
use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class PostFilterService
{
    protected $filters;

    protected Post $post;

    public function __construct(Post $post, Collection $filters)
    {
        $this->filters = $filters;
        $this->post = $post;
    }

    public static function filter(Post $post, Collection $filters)
    {
        return (new static($post, $filters))->runFilters();
    }

    // Returns true if the post should be EXCLUDED from the issue, false if it should be kept.
    // Any single matching filter is enough to exclude — filters are OR'd, not AND'd.
    protected function runFilters()
    {
        if( ! $this->filters->count())
        {
            return false;
        }

        foreach($this->filters as $filter)
        {
            if($this->runFilter($filter))
            {
                return true;
            }
        }

        return false;
    }

    protected function runFilter(Filter $filter)
    {
        // Operator names like "does_not_contain" are stored as display strings (e.g.
        // "does not contain"). Parentheses are stripped first because some operators
        // are stored as "regex()" style. The leading underscore avoids collisions with
        // any public method names if this class is extended.
        $method = '_' . Str::of($filter->operator)->remove(['(', ')'])->camel();

        if( ! method_exists($this, $method))
        {
            return false;
        }

        return $this->$method($filter->pattern, $this->post->{$filter->field});
    }

    protected function _contains($pattern, $value)
    {
        return strpos(strtolower($value), strtolower($pattern)) !== false;
    }

    protected function _doesNotContain($pattern, $value)
    {
        return ! $this->_contains($pattern, $value);
    }

    protected function _equals($pattern, $value)
    {
        return strtolower($value) == strtolower($pattern);
    }

    protected function _doesNotEqual($pattern, $value)
    {
        return ! $this->_equals($pattern, $value);
    }

    protected function _regex($pattern, $value)
    {
        return (bool) preg_match_all($pattern, $value);
    }

    protected function _regexNoMatch($pattern, $value)
    {
        return ! $this->_regex($pattern, $value);
    }
}
