@if(session()->has('flash'))
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-xl-5 col-lg-6">
                <div class="alert alert-success mt-2">
                    {{ session()->get('flash') }}
                </div>
            </div>
        </div>
    </div>

@endif
