<div class="mb-4 article-content" id="{{ $post->uuid }}">
    @if($fullArticle)
        <div class="py-4">
            <h3 class="h4 fw-bold mb-3">
                <a href="{{ route('link', [$user, $post]) }}" target="_blank" class="text-dark text-decoration-none">
                    {!! $post->title !!}
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-up-right" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5z"/>
                        <path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0v-5z"/>
                    </svg>
                </a>
            </h3>

            @if($showReadLaterButton && ! $showRemove)
                <div class="mb-3">
                    @if($readingLater)
                        <button class="btn btn-primary btn-sm" wire:click.prevent="removeReadLater">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bookmark-check-fill" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M2 15.5V2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13.5a.5.5 0 0 1-.74.439L8 13.069l-5.26 2.87A.5.5 0 0 1 2 15.5zm8.854-9.646a.5.5 0 0 0-.708-.708L7.5 7.793 6.354 6.646a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .708 0l3-3z"/>
                            </svg>
                            Read Later
                        </button>
                    @else
                        <button class="btn btn-outline-dark btn-sm" wire:click.prevent="readLater">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bookmark-plus" viewBox="0 0 16 16">
                                <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13.5a.5.5 0 0 1-.777.416L8 13.101l-5.223 2.815A.5.5 0 0 1 2 15.5V2zm2-1a1 1 0 0 0-1 1v12.566l4.723-2.482a.5.5 0 0 1 .554 0L13 14.566V2a1 1 0 0 0-1-1H4z"/>
                                <path d="M8 4a.5.5 0 0 1 .5.5V6H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V7H6a.5.5 0 0 1 0-1h1.5V4.5A.5.5 0 0 1 8 4z"/>
                            </svg>
                            Read Later
                        </button>
                    @endif
                </div>
            @endif

            @if($showRemove)
                <div class="d-flex align-items-center">
                    <form action="{{ route('readlater.delete', $post) }}" method="POST">
                        @csrf
                        @method('DELETE')

                        <button class="btn btn-primary btn-sm mb-3" type="submit">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bookmark-x-fill" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M2 15.5V2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13.5a.5.5 0 0 1-.74.439L8 13.069l-5.26 2.87A.5.5 0 0 1 2 15.5zM6.854 5.146a.5.5 0 1 0-.708.708L7.293 7 6.146 8.146a.5.5 0 1 0 .708.708L8 7.707l1.146 1.147a.5.5 0 1 0 .708-.708L8.707 7l1.147-1.146a.5.5 0 0 0-.708-.708L8 6.293 6.854 5.146z"/>
                            </svg>
                            Remove
                        </button>
                    </form>

                    <p class="ms-3 text-uppercase text-muted small">Available until {{ $post->created_at->addDays(30)->format('j M, Y') }}</p>
                </div>
            @endif

            <div class="article-longform">
                {!! $post->body !!}
            </div>

            @if($post->audio_url)
                <iframe src="{!! $post->audio_url !!}" width='100%' height='100' frameborder="0"></iframe>

                <div class="text-muted small text-center">
                    Audio not playing properly? You can download it <a href="{!! $post->audio_url !!}">here</a>
                </div>
            @endif
        </div>
    @else
        <div class="row pt-4">
            <div class="col-md-5">
                <h3 class="h5 fw-bold mb-3">
                    <a href="#{{ $post->uuid }}" class="text-dark text-decoration-none" wire:click.prevent="showFull">
                        {!! $post->title !!}
                    </a>
                </h3>

                @if($showReadLaterButton && ! $showRemove)
                    <div class="mb-3">
                    @if($readingLater)
                        <button class="btn btn-primary btn-sm" wire:click.prevent="removeReadLater">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bookmark-check-fill" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M2 15.5V2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13.5a.5.5 0 0 1-.74.439L8 13.069l-5.26 2.87A.5.5 0 0 1 2 15.5zm8.854-9.646a.5.5 0 0 0-.708-.708L7.5 7.793 6.354 6.646a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .708 0l3-3z"/>
                            </svg>
                            Read Later
                        </button>
                    @else
                        <button class="btn btn-outline-dark btn-sm" wire:click.prevent="readLater">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bookmark-plus" viewBox="0 0 16 16">
                                <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13.5a.5.5 0 0 1-.777.416L8 13.101l-5.223 2.815A.5.5 0 0 1 2 15.5V2zm2-1a1 1 0 0 0-1 1v12.566l4.723-2.482a.5.5 0 0 1 .554 0L13 14.566V2a1 1 0 0 0-1-1H4z"/>
                                <path d="M8 4a.5.5 0 0 1 .5.5V6H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V7H6a.5.5 0 0 1 0-1h1.5V4.5A.5.5 0 0 1 8 4z"/>
                            </svg>
                            Read Later
                        </button>
                    @endif
                    </div>
                @endif

                @if($showRemove)
                    <form action="{{ route('readlater.delete', $post) }}" method="POST">
                        @csrf
                        @method('DELETE')

                        <button class="btn btn-primary btn-sm mb-3" type="submit">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bookmark-x-fill" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M2 15.5V2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13.5a.5.5 0 0 1-.74.439L8 13.069l-5.26 2.87A.5.5 0 0 1 2 15.5zM6.854 5.146a.5.5 0 1 0-.708.708L7.293 7 6.146 8.146a.5.5 0 1 0 .708.708L8 7.707l1.146 1.147a.5.5 0 1 0 .708-.708L8.707 7l1.147-1.146a.5.5 0 0 0-.708-.708L8 6.293 6.854 5.146z"/>
                            </svg>
                            Remove
                        </button>
                    </form>

                    <p class="text-uppercase text-muted small">Available until {{ $post->created_at->addDays(30)->format('j M, Y') }}</p>

                @endif
            </div>

            <div class="col-md-7">
                {!! $post->preview !!}

                <p>
                    <a href="#{{ $post->uuid }}" class="fw-bold text-dark text-decoration-none" wire:click.prevent="showFull">
                        Read more...
                    </a>
                </p>
            </div>
        </div>
    @endif
</div>
