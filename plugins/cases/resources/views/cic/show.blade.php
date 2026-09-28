@extends('layouts.app')

@section('title', 'APES CIC Case #'.$case->id)

@section('content')
    <div class="panel">
        <span class="service-label apes-cic">APES CIC</span>
        <h1>Case #{{ $case->id }} - {{ $case->title }}</h1>
        <p class="muted">{{ $case->details }}</p>

        <dl class="ticket-meta">
            <div>
                <dt>Owner</dt>
                <dd>{{ $case->user?->name ?? '—' }}@if($case->user)<br><small class="muted">{{ $case->user->email }}</small>@endif</dd>
            </div>
            <div>
                <dt>Assigned staff</dt>
                <dd>
                    @if($revealAssigneeIdentity)
                        {{ $case->assignedTo?->name ?? 'Unassigned' }}
                    @else
                        {{ $case->assigned_to ? 'Assigned' : 'Unassigned' }}
                    @endif
                </dd>
            </div>
            <div>
                <dt>Category</dt>
                <dd>{{ $categoryResolver->labelForCategory($case->sub_core_key, (string) $case->category) }}</dd>
            </div>
            @if($case->sub_category)
                <div>
                    <dt>Subcategory</dt>
                    <dd>{{ $categoryResolver->labelForSubcategory($case->sub_core_key, (string) $case->category, $case->sub_category) }}</dd>
                </div>
            @endif
            @if($case->affected_website_key)
                <div>
                    <dt>Related website</dt>
                    <dd>{{ $categoryResolver->labelForWebsite($case->sub_core_key, $case->affected_website_key) }}</dd>
                </div>
            @endif
            <div>
                <dt>Status</dt>
                <dd><span class="status">{{ $case->status }}</span></dd>
            </div>
            <div>
                <dt>Priority</dt>
                <dd>{{ $case->priority }} priority</dd>
            </div>
        </dl>

        @if($canUpdateCase || $canChangeAssignment)
            <form method="post" action="{{ route('apes-cic.cases.update', $case) }}" @if($canUpdateCase) data-case-update-form @endif>
                @csrf
                @method('patch')
                <div class="row">
                    @if($canUpdateCase)
                        <div>
                            <label for="category">Category</label>
                            <select id="category" name="category" data-category-parent>
                                @foreach($categoryGroups as $category)
                                    <option value="{{ $category['key'] }}" @selected($case->category === $category['key'])>
                                        {{ $category['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="sub_category">Subcategory</label>
                            <select id="sub_category" name="sub_category" data-category-child>
                                <option value="">Select subcategory</option>
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
                </div>
                @if($canUpdateCase)
                    <div data-website-field hidden>
                        <label for="affected_website_key">Related website or system</label>
                        <select id="affected_website_key" name="affected_website_key">
                            <option value="">Select website</option>
                            @foreach($websites as $website)
                                <option value="{{ $website['key'] }}" @selected($case->affected_website_key === $website['key'])>
                                    {{ $website['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if($canChangeAssignment)
                    <div class="row">
                        <div>
                            <label for="user_id">Owner</label>
                            <select id="user_id" name="user_id">
                                @foreach($ownerCandidates as $ownerCandidate)
                                    <option value="{{ $ownerCandidate->id }}" @selected((int) $case->user_id === (int) $ownerCandidate->id)>
                                        {{ $ownerCandidate->name }} ({{ $ownerCandidate->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="assigned_to">Assigned staff</label>
                            <select id="assigned_to" name="assigned_to">
                                <option value="">Unassigned</option>
                                @foreach($staffUsers as $staffUser)
                                    <option value="{{ $staffUser->id }}" @selected((int) $case->assigned_to === (int) $staffUser->id)>{{ $staffUser->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
                <button type="submit">Save case</button>
            </form>
        @endif

        @if($canCommentCase)
            <form method="post" action="{{ route('apes-cic.cases.updates.store', $case) }}" @if($allowsAttachments) enctype="multipart/form-data" @endif>
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
                @if($allowsAttachments)
                    <p class="muted">To add evidence files, use Save case after uploading on a separate update path, or attach when opening the case.</p>
                @endif
                <button type="submit">Add update</button>
            </form>
            @if($allowsAttachments)
                <form method="post" action="{{ route('apes-cic.cases.update', $case) }}" enctype="multipart/form-data">
                    @csrf
                    @method('patch')
                    <label for="case-screenshots">Add evidence screenshots</label>
                    <input id="case-screenshots" name="screenshots[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                    <label for="case-screencast">Add evidence screencast</label>
                    <input id="case-screencast" name="screencast" type="file" accept="video/mp4,video/webm">
                    <button type="submit">Upload evidence</button>
                </form>
            @endif
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

    @if($case->attachments->isNotEmpty())
        <div class="panel">
            <h2>Attachments</h2>
            <ul class="attachment-list">
                @foreach($case->attachments as $attachment)
                    <li>
                        <strong>{{ $attachment->kind }}</strong>
                        — {{ $attachment->original_name }}
                        <span class="muted">({{ number_format($attachment->size_bytes / 1024, 1) }} KB)</span>
                        <a href="{{ route('support.attachments.download', $attachment) }}">Open</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

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

    @if($canUpdateCase)
        @include('partials.category-cascade-script', [
            'groups' => $categoryGroups,
            'oldParent' => old('category', $case->category),
            'oldChild' => old('sub_category', $case->sub_category),
        ])
    @endif
@endsection
