<x-app-layout back="true">
    <div class="container my-4">
        <div class="row justify-content-center mt-4">
            <div class="col-md-8 col-xl-5 col-lg-6">

                <h1 class="h3 fw-bold mb-4 text-center">Edit Subscription</h1>

                @include('partials.validation-errors-warning')

                <form method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="title" class="form-label">Custom Feed Title:</label>
                        <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $subscription->title) }}" placeholder="{{ $subscription->feed->title }}" >
                        <div class="form-text">Add a custom title to this feed</div>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8 col-xl-5 col-lg-6">
                <div class="mt-4">
                    <h3 class="h3 fw-bold mb-4 text-center">Filters</h3>

                    <p>Filter <em>out</em> posts from your daily issues:</p>

                    @foreach($subscription->filters as $filter)
                    <div class="card mb-3">

                            <div class="card-body">
                                <form action="{{ route('filter.edit', $filter) }}" method="POST">
                                    @csrf
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="field_{{ $filter->id }}" class="form-label visually-hidden">Field:</label>

                                        <select name="field_{{ $filter->id }}" id="field_{{ $filter->id }}" class="form-control @error('field_' . $filter->id) is-invalid @enderror" required>
                                            @foreach(['title' => 'Title',
                                                        'body' => 'Body'] as $field => $label)
                                                <option value="{{ $field }}" @if(old("field_{$filter->id}", $filter->field) == $field) selected @endif >{{ $label }}</option>
                                            @endforeach
                                        </select>

                                        @error('field_' . $filter->id)
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="modifier_{{ $filter->id }}" class="form-label visually-hidden">Operator:</label>

                                        <select name="modifier_{{ $filter->id }}" id="modifier_{{ $filter->id }}" class="form-control @error('modifier_' . $filter->id) is-invalid @enderror" required>
                                            @foreach(['contains', 'equals', 'regex', 'does not contain', 'does not equal', 'regex (no match)'] as $modifier)
                                                <option value="{{ $modifier }}" @if(old("modifier_{$filter->id}", $filter->modifier) == $modifier) selected @endif >{{ $modifier }}</option>
                                            @endforeach
                                        </select>

                                        @error('modifier_' . $filter->id)
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="pattern_{{ $filter->id }}" class="form-label visually-hidden">Pattern:</label>
                                    <input type="text" class="form-control @error('pattern_' . $filter->id) is-invalid @enderror" id="pattern_{{ $filter->id }}" name="pattern_{{ $filter->id }}" value="{{ old("pattern_{$filter->id}", $filter->pattern) }}" placeholder="" required>

                                    @error('pattern_' . $filter->id)
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-outline-dark float-start">Update</button>
                            </form>

                            <form action="{{ route('filter', $filter) }}" method="POST" id="deleteFilter{{ $filter->id }}" class="float-end">
                                @csrf
                                @method('DELETE')

                                <button class="btn btn-outline-dark" type="button" onclick="document.getElementById('deleteFilter{{ $filter->id }}').submit();">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                    </svg>
                                </button>
                            </form>
                    </div>

                    </div>
                    @endforeach

                    <div class="card">
                        <div class="card-header">
                            New Filter
                        </div>
                        <form action="{{ route('filter.create', $subscription) }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="field" class="form-label visually-hidden">Field:</label>

                                        <select name="field" id="field" class="form-control @error('field') is-invalid @enderror" required>
                                            <option disabled selected>Select field...</option>
                                            @foreach(['title' => 'Title',
                                                        'body' => 'Body'] as $field => $label)
                                                <option value="{{ $field }}" @if(old('field') == $field) selected @endif >{{ $label }}</option>
                                            @endforeach
                                        </select>

                                        @error('field')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="modifier" class="form-label visually-hidden">Operator:</label>

                                        <select name="modifier" id="modifier" class="form-control @error('modifier') is-invalid @enderror" required>
                                            <option disabled selected>Select operation...</option>
                                            @foreach(['contains', 'equals', 'regex', 'does not contain', 'does not equal', 'regex (no match)'] as $modifier)
                                                <option value="{{ $modifier }}" @if(old('modifier') == $modifier) selected @endif >{{ $modifier }}</option>
                                            @endforeach
                                        </select>

                                        @error('modifier')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="pattern" class="form-label visually-hidden">Pattern:</label>
                                    <input type="text" class="form-control @error('pattern') is-invalid @enderror" id="pattern" name="pattern" value="{{ old('pattern') }}" placeholder="" required>

                                    @error('pattern')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="card-footer">
                                <button class="btn btn-primary">Create Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center mt-4">
            <div class="col-md-8 col-xl-5 col-lg-6">
                <div class="mt-4 pt-4">
                    <div class="card">
                        <div class="card-header">
                            Unsubscribe
                        </div>

                        <form action="{{ route('subscription', $subscription) }}" method="POST" onsubmit="return confirm('Are you sure you want unsubscribe from this feed? This cannot be undone.');">
                            @csrf
                            @method('DELETE')

                            <div class="card-body">
                                Articles from this feed will be permanently removed from previous issues. If you re-subscribe to this feed, previous articles will not be visible. This cannot be undone.
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-outline-dark">Unsubscribe Now</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
