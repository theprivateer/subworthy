<?php

namespace App\View\Composers;

use App\Models\ReadLater;
use Illuminate\View\View;

class ReadLaterComposer
{
    public function compose(View $view)
    {
        $count = ReadLater::query()
                        ->where('user_id', auth()->id())
                        ->count();

        $view->with('readLaterCount', $count);
    }
}
