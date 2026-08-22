@extends('layouts.app')

@section('title', 'APES CIC Cases')

@section('content')
    <div class="panel">
        <span class="service-label apes-cic">APES CIC</span>
        <h1>Cases</h1>
        <p class="muted">Formal casework including data access, privacy requests, complaints and escalated enquiries. Use tickets for general support.</p>
        <x-mascot-tip />
    </div>
    @if($canCreateCase)
        <div class="panel">
            <h2>Open a case</h2>
            <form method="post" action="{{ route('apes-cic.cases.store') }}" enctype="multipart/form-data" data-case-create-form>
            @csrf
            <div class="row">
                <div>
                    <label for="category">Category</label>
                    <select id="category" name="category" data-category-parent required>
                        @foreach($categoryGroups as $category)
                            <option value="{{ $category['key'] }}" @selected(old('category') === $category['key'])>
                                {{ $category['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sub_category">Subcategory</label>
                    <select id="sub_category" name="sub_category" data-category-child required>
                        <option value="">Select subcategory</option>
                    </select>
                </div>
                <div>
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        @foreach($priorities as $priority)
                            <option value="{{ $priority }}" @selected(old('priority', 'medium') === $priority)>{{ $priority }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div data-website-field hidden>
                <label for="affected_website_key">Related website or system</label>
                <select id="affected_website_key" name="affected_website_key">
                    <option value="">Select website</option>
                    @foreach($websites as $website)
                        <option value="{{ $website['key'] }}" @selected(old('affected_website_key') === $website['key'])>
                            {{ $website['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <label for="title">Title</label>
            <input id="title" name="title" value="{{ old('title') }}" required>
            <label for="details">Details</label>
            <textarea id="details" name="details">{{ old('details') }}</textarea>
            <div data-attachment-fields hidden>
                <label for="screenshots">Evidence screenshots (optional)</label>
                <input id="screenshots" name="screenshots[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                <label for="screencast">Evidence screencast (optional)</label>
                <input id="screencast" name="screencast" type="file" accept="video/mp4,video/webm">
            </div>
                <button type="submit">Open case</button>
            </form>
        </div>
    @endif
    <div class="panel">
        <h2>Your available cases</h2>
        @if($cases->isEmpty())
            <x-mascot-tip
                variant="empty"
                title="No cases are available to you yet."
                body="When a case is shared with you, or you open one, it will appear here."
            />
        @else
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Owner</th>
                        <th>Assigned</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($cases as $case)
                    <tr>
                        <td>#{{ $case->id }}</td>
                        <td>{{ $case->title }}</td>
                        <td>
                            {{ $categoryResolver->labelForCategory($case->sub_core_key, (string) $case->category) }}
                            @if($case->sub_category)
                                <br><small class="muted">{{ $categoryResolver->labelForSubcategory($case->sub_core_key, (string) $case->category, $case->sub_category) }}</small>
                            @endif
                        </td>
                        <td><span class="status">{{ $case->status }}</span></td>
                        <td>{{ $case->priority }}</td>
                        <td>{{ $case->user?->name ?? '—' }}</td>
                        <td>
                            @if($revealAssigneeIdentity)
                                {{ $case->assignedTo?->name ?? 'Unassigned' }}
                            @else
                                {{ $case->assigned_to ? 'Assigned' : 'Unassigned' }}
                            @endif
                        </td>
                        <td><a href="{{ route('apes-cic.cases.show', $case) }}">Open</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $cases->links() }}
        @endif
    </div>

    @if($canCreateCase)
        @include('partials.category-cascade-script', [
            'groups' => $categoryGroups,
            'oldParent' => old('category'),
            'oldChild' => old('sub_category'),
        ])
    @endif
@endsection
