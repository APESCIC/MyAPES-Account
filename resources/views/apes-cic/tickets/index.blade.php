@extends('layouts.app')

@section('title', $ticketService->serviceName.' Tickets')

@section('content')
    <div class="panel">
        <span class="service-label {{ $ticketService->presentationClass }}">{{ $ticketService->serviceName }}</span>
        <h1>{{ $ticketService->heading }}</h1>
        <p class="muted">{{ $ticketService->supportingCopy }}</p>
        <x-mascot-tip />
    </div>
    @if($canCreateTicket)
        <div class="panel">
            <h2>Create ticket</h2>
            <form
                method="post"
                action="{{ route($ticketService->routePrefix.'.store') }}"
                @if($usesHierarchicalCategories) enctype="multipart/form-data" @endif
                data-ticket-create-form
            >
            @csrf
            <div class="row">
                <div>
                    <label for="service_area">Service area</label>
                    <select id="service_area" name="service_area" data-category-parent required>
                        @if($usesHierarchicalCategories)
                            @foreach($serviceAreaGroups as $area)
                                <option
                                    value="{{ $area['key'] }}"
                                    @selected(old('service_area') === $area['key'])
                                >{{ $area['label'] }}</option>
                            @endforeach
                        @else
                            @foreach($serviceAreas as $serviceArea)
                                <option value="{{ $serviceArea }}" @selected(old('service_area') === $serviceArea)>{{ $serviceArea }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                @if($usesHierarchicalCategories)
                    <div>
                        <label for="sub_category">Subcategory</label>
                        <select id="sub_category" name="sub_category" data-category-child required>
                            <option value="">Select subcategory</option>
                        </select>
                    </div>
                @endif
                <div>
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <option value="low" @selected(old('priority') === 'low')>low</option>
                        <option value="medium" @selected(old('priority', 'medium') === 'medium')>medium</option>
                        <option value="high" @selected(old('priority') === 'high')>high</option>
                        <option value="urgent" @selected(old('priority') === 'urgent')>urgent</option>
                    </select>
                </div>
            </div>
            @if($usesHierarchicalCategories)
                <div data-website-field hidden>
                    <label for="affected_website_key">Affected website</label>
                    <select id="affected_website_key" name="affected_website_key">
                        <option value="">Select website</option>
                        @foreach($websites as $website)
                            <option value="{{ $website['key'] }}" @selected(old('affected_website_key') === $website['key'])>
                                {{ $website['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <label for="subject">Subject</label>
            <input id="subject" name="subject" value="{{ old('subject') }}" required>
            <label for="description">Description</label>
            <textarea id="description" name="description" required>{{ old('description') }}</textarea>
            @if($usesHierarchicalCategories)
                <div data-attachment-fields hidden>
                    <label for="screenshots">Screenshots (optional)</label>
                    <input id="screenshots" name="screenshots[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                    <label for="screencast">Screencast (optional)</label>
                    <input id="screencast" name="screencast" type="file" accept="video/mp4,video/webm">
                </div>
            @endif
                <button type="submit">Create ticket</button>
            </form>
        </div>
    @endif
    <div class="panel">
        <h2>Tickets</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Subject</th>
                    <th>Area</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Owner</th>
                    <th>Assigned</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($tickets as $ticket)
                <tr>
                    <td>#{{ $ticket->id }}</td>
                    <td>{{ $ticket->subject }}</td>
                    <td>
                        @if($usesHierarchicalCategories)
                            {{ $categoryResolver->labelForArea($ticket->sub_core_key, $ticket->service_area) }}
                            @if($ticket->sub_category)
                                <br><small class="muted">{{ $categoryResolver->labelForSubcategory($ticket->sub_core_key, $ticket->service_area, $ticket->sub_category) }}</small>
                            @endif
                        @else
                            {{ $ticket->service_area }}
                        @endif
                    </td>
                    <td><span class="status">{{ $ticket->status }}</span></td>
                    <td>{{ $ticket->priority }}</td>
                    <td>{{ $ticket->user?->name ?? '—' }}</td>
                    <td>{{ $ticket->assignedTo?->name ?? 'Unassigned' }}</td>
                    <td><a href="{{ route($ticketService->routePrefix.'.show', $ticket) }}">Open</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $tickets->links() }}
    </div>

    @if($usesHierarchicalCategories && $canCreateTicket)
        @include('partials.category-cascade-script', [
            'groups' => $serviceAreaGroups,
            'oldParent' => old('service_area'),
            'oldChild' => old('sub_category'),
        ])
    @endif
@endsection
