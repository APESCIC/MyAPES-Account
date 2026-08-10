@extends('layouts.app')

@section('title', 'APES CIC Case #'.$case->id)

@section('content')
    <div class="panel">
        <span class="service-label apes-cic">APES CIC</span>
        <h1>Case #{{ $case->id }} - {{ $case->title }}</h1>
        <p class="muted">{{ $case->details }}</p>
        <p>
            <span class="status">{{ $case->status }}</span>
            {{ str_replace('_', ' ', $case->category) }} · {{ $case->priority }} priority
        </p>

        @if($canUpdateCase || $canChangeAssignment)
            <form method="post" action="{{ route('apes-cic.cases.update', $case) }}">
                @csrf
                @method('patch')
                <div class="row">
                    @if($canUpdateCase)
                        <div>
                            <label for="category">Category</label>
                            <select id="category" name="category">
                                @foreach($categories as $category)
                                    <option value="{{ $category }}" @selected($case->category === $category)>{{ str_replace('_', ' ', $category) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority">
                                @foreach($priorities as $priority)
                                    <option value="{{ $priority }}" @selected($case->priority === $priority)>{{ $priority }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                @foreach($statuses as $status)
                                    @continue((in_array($status, ['resolved', 'closed'], true) || in_array($case->status, ['resolved', 'closed'], true)) && ! $canCloseCase && $case->status !== $status)
                                    <option value="{{ $status }}" @selected($case->status === $status)>{{ str_replace('_', ' ', $status) }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if($canChangeAssignment)
                        <div>
                            <label for="assigned_to">Assigned staff</label>
                            <select id="assigned_to" name="assigned_to">
                                <option value="">Unassigned</option>
                                @foreach($staffUsers as $staffUser)
                                    <option value="{{ $staffUser->id }}" @selected((int) $case->assigned_to === (int) $staffUser->id)>{{ $staffUser->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
                <button type="submit">Save case</button>
            </form>
        @endif

        @if($canCommentCase)
            <form method="post" action="{{ route('apes-cic.cases.updates.store', $case) }}">
                @csrf
                <label for="body">Add update</label>
                <textarea id="body" name="body"></textarea>
                @if($canChooseVisibility)
                    <label for="visibility">Visibility</label>
                    <select id="visibility" name="visibility">
                        <option value="public">Public</option>
                        <option value="internal">Internal staff only</option>
                    </select>
                @endif
                <button type="submit">Add update</button>
            </form>
        @elseif($case->status === 'closed')
            <p class="muted">Reopen this case before adding another update.</p>
        @endif

        @can('delete', $case)
            <form method="post" action="{{ route('apes-cic.cases.destroy', $case) }}" onsubmit="return confirm('Delete this case?')">
                @csrf
                @method('delete')
                <button type="submit" class="danger-btn">Delete case</button>
            </form>
        @endcan
        <div class="actions"><a href="{{ route('apes-cic.cases.index') }}">Back</a></div>
    </div>
    <div class="panel">
        <h2>Activity</h2>
        @forelse($updates as $update)
            <div class="item-divider">
                <strong>{{ $update->user?->name ?? 'Former user' }}</strong>
                <span class="muted">{{ $update->created_at }}</span>
                @if($update->visibility === 'internal')<span class="status">internal</span>@endif
                <div>{{ $update->body }}</div>
            </div>
        @empty
            <p class="muted">No updates have been added.</p>
        @endforelse
    </div>
@endsection
