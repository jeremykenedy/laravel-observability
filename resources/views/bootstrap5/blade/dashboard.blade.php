@extends(config('observability.layout', 'layouts.app'))

@section('template_title', 'System Health')

@section('content')
<x-observability::dashboard css="bootstrap5" :health-data="$healthData ?? []" :provider-data="$providerData ?? []" />
@endsection
