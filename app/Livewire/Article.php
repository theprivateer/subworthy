<?php

namespace App\Livewire;

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
        // Issues are publicly accessible. $user is always the issue owner; $authUser is the
        // currently logged-in visitor (or false). Only show the read-later button when the
        // viewer is looking at their own issue.
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

        $this->dispatch('postOpened', id: 'post_' . $this->post->id);
    }

    public function showPreview()
    {
        $this->fullArticle = false;

        $this->dispatch('postClosed', id: 'post_' . $this->post->uuid);
    }

    public function readLater()
    {
        // Verify the viewer is the issue owner — prevents creating read-laters for other users.
        if (!auth()->check() || auth()->id() !== $this->user->id) {
            abort(403);
        }

        ReadLater::create([
            'post_id' => $this->post->id,
            'user_id' => auth()->id(),
        ]);

        $this->readingLater = true;
    }

    public function removeReadLater()
    {
        if (!auth()->check() || auth()->id() !== $this->user->id) {
            abort(403);
        }

        ReadLater::query()
            ->where('post_id', $this->post->id)
            ->where('user_id', auth()->id())
            ->delete();

        $this->readingLater = false;
    }
}
