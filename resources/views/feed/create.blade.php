<x-app-layout>
    <div class="container my-4">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="mb-4">
                    @if( ! count($feedLinks))

                        <div class="pb-4">

                            <p class="h5 text-center py-4 text-muted">Sorry, we couldn't find any feeds at that address</p>
                            <div class="row justify-content-center">
                                <div class="col-md-9">

                                    <img src="{{ asset('img/undraw_signal_searching_bhpc.svg') }}" alt="" class="img-fluid">
                                </div>
                            </div>
                        </div>

                        @if($errors->any())
                            <div class="alert alert-danger">
                                @foreach($errors->all() as $error)
                                    {{ $error }}
                                @endforeach
                            </div>
                        @endif

                        <form action="{{ route('feed.create') }}" method="POST">
                            @csrf

                            <div class="d-flex mb-3">
                                <div class="flex-grow-1 form-floating me-2">
                                    <input type="text" placeholder="https://example.com" class="form-control @error('url') is-invalid @enderror" id="url" name="url">
                                    <label for="url">Try again...</label>
                                </div>

                                <button type="submit" class="btn btn-primary">Add</button>
                            </div>


                            @error('url')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror

                        </form>
                    @else
                        <h1 class="h3 fw-bold mb-4 text-center">Select a Feed to Add</h1>

                        <form action="{{ route('feed.create') }}" method="POST">
                            @csrf

                            @if( ! isset($feedLinks))
                                <div class="mb-3">
                                    <label for="url" class="form-label">URL</label>
                                    <input type="text" class="form-control @error('url') is-invalid @enderror" id="url" name="url" value="{{ old('url') }}">

                                    @error('url')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror

                                </div>

                            @else

                                @foreach($feedLinks as $index => $link)
                                    <div class="mb-3">
                                        <input type="radio" class="btn-check" id="url_{{ $index }}" name="url" value="{{ $link['href'] }}" autocomplete="off">
                                        <label class="btn btn-outline-primary d-block" for="url_{{ $index }}">{!! $link['title'] ? $link['title'] . ' &mdash;' : ''  !!} {{ $link['href'] }}</label>
                                    </div>
                                @endforeach
                            @endif

                            <button type="submit" class="btn btn-primary mb-3">Add Selected Feed</button>

                        </form>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
