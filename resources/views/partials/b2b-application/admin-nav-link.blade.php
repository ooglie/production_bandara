@php
    $currentAdmin = auth()->user();
    $showB2BApplications = $currentAdmin instanceof \App\Models\User
        && \App\Support\B2BApplicationAccess::adminCan($currentAdmin, 'view');
@endphp
@if ($showB2BApplications)
    <a href="{{ route('admin.b2b-applications.index') }}" class="{{ config('b2b_application_corrective.ui.admin_nav_link', '') }}">
        <span>B2B applications</span>
    </a>
@endif
