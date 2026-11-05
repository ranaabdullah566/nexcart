<ul class="nav nav-pills mb-3 gap-2 flex-wrap nx-report-nav">
    <li class="nav-item"><a class="nav-link {{ ($active ?? '') === 'overview' ? 'active' : '' }}" href="{{ route('admin.reports.index') }}" data-no-modal><i class="bi bi-speedometer2 me-1"></i>{{ __('Overview') }}</a></li>
    @foreach (\App\Http\Controllers\Admin\ReportHubController::TYPES as $k => [$label, $icon])
        @can("reports.$k")
            <li class="nav-item"><a class="nav-link {{ ($active ?? '') === $k ? 'active' : '' }}" href="{{ route('admin.reports.show', $k) }}" data-no-modal><i class="bi bi-{{ $icon }} me-1"></i>{{ __($label) }}</a></li>
        @endcan
    @endforeach
</ul>
