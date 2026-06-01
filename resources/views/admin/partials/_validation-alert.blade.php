@if ($errors->any())
    <div class="alert alert-danger shadow-soft" role="alert">
        <div class="d-flex gap-2">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
            <div>
                <strong>Could not save — please fix the following:</strong>
                <ul class="mb-0 mt-2 ps-3">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
