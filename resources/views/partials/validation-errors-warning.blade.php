@if($errors->any())
<div class="alert alert-danger">
    @if(isset($message))
    {{ $message}}
    @else
    There were errors on your submission
    @endif
</div>
@endif