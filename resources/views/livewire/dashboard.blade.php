<div>
    <h2 class="h5 mb-3">System Health</h2>

    @if(isset($healthData['status']))
        <div class="mb-3 p-3 border rounded {{ $healthData['status'] === 'healthy' ? 'bg-success bg-opacity-10 border-success' : 'bg-warning bg-opacity-10 border-warning' }}">
            <strong>{{ $healthData['status'] === 'healthy' ? 'All Systems Operational' : 'Degraded' }}</strong>
        </div>

        <div class="row g-2 mb-4">
            @foreach($healthData['checks'] ?? [] as $name => $check)
                <div class="col-sm-6">
                    <div class="card">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between">
                                <span class="fw-medium text-capitalize">{{ $name }}</span>
                                <span class="badge {{ $check['status'] === 'ok' ? 'bg-success' : 'bg-danger' }}">{{ $check['status'] }}</span>
                            </div>
                            <small class="text-muted">{{ $check['message'] }}</small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <button wire:click="refresh" class="btn btn-sm btn-outline-secondary">Refresh</button>
</div>
