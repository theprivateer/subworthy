<?php

namespace App\View\Components;

use Illuminate\View\Component;

class SubworthyLogo extends Component
{
    public $size;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($size)
    {
        //
        $this->size = $size;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.subworthy-logo');
    }
}
