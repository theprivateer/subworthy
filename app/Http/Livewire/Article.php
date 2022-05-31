<?php

namespace App\Http\Livewire;

use App\Models\ReadLater;
use Livewire\Component;

class Article extends Component
{
    public $post;

    public $readLater;

    public $user;

    public $authUser;

    public $fullArticle = false;

    public $readingLater = false;

    public $showReadLaterButton = false;

    public $showRemove = false;

    public $showExpiry = false;

    public function mount()
    {
        if($this->authUser)
        {
            if($this->authUser->id == $this->user->id) {
                $this->showReadLaterButton = true;

                $readLaters = $this->authUser->readLaters->pluck('post_id')->all();

                if(in_array($this->post->id, $readLaters))
                {
                    $this->readingLater = true;
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.article');
    }

    public function showFull()
    {
        $this->fullArticle = true;

        $this->emit('postOpened', $this->post->uuid);
    }

    public function showPreview()
    {
        $this->fullArticle = false;

        $this->emit('postClosed', $this->post->uuid);
    }

    public function readLater()
    {
        ReadLater::create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $this->readingLater = true;
    }

    public function removeReadLater()
    {
        ReadLater::query()
            ->where('post_id', $this->post->id)
            ->where('user_id', $this->user->id)
            ->delete();

        $this->readingLater = false;
    }
}
