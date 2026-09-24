@extends('layouts.app')

@section('title', 'Open roles | MyAPES Core')

@section('content')
    <div class="panel" data-recruitment-board>
        <span class="service-label service-apes-cic">APES CIC</span>
        <h1>Open roles</h1>
        <p class="muted">Staff, volunteering, and student opportunities with APES CIC.</p>
        <x-mascot-tip />
    </div>

    <div class="panel" aria-label="Filter roles by category">
        <div class="actions" role="tablist" aria-label="Role categories">
            <a
                href="{{ route('recruitment.index') }}"
                @class(['is-active' => $selectedCategory === null])
                @if($selectedCategory === null) aria-current="page" @endif
            >All</a>
            @foreach($categories as $category)
                <a
                    href="{{ route('recruitment.index', ['category' => $category]) }}"
                    @class(['is-active' => $selectedCategory === $category])
                    @if($selectedCategory === $category) aria-current="page" @endif
                >{{ $categoryLabels[$category] }}</a>
            @endforeach
        </div>
    </div>

    <div class="panel" id="list">
        <h2>
            @if($selectedCategory)
                {{ $categoryLabels[$selectedCategory] }} roles
            @else
                All open roles
            @endif
        </h2>

        @if($roles->isEmpty())
            <x-mascot-tip
                variant="empty"
                title="No open roles right now."
                body="{{ $selectedCategory ? 'Try another category, or check back soon.' : 'Check back soon for staff, volunteer, and student openings.' }}"
            />
        @else
            <ul class="stack-spaced" data-recruitment-role-list>
                @foreach($roles as $role)
                    <li class="panel panel-flat">
                        <span class="service-label service-apes-cic">{{ $categoryLabels[$role->category] }}</span>
                        <h3>
                            <a href="{{ route('recruitment.show', $role) }}">{{ $role->title }}</a>
                        </h3>
                        @if($role->summary)
                            <p>{{ $role->summary }}</p>
                        @endif
                        @if($role->location || $role->commitment)
                            <p class="muted">
                                @if($role->location){{ $role->location }}@endif
                                @if($role->location && $role->commitment) · @endif
                                @if($role->commitment){{ $role->commitment }}@endif
                            </p>
                        @endif
                        <div class="actions">
                            <a href="{{ route('recruitment.show', $role) }}">View role</a>
                        </div>
                    </li>
                @endforeach
            </ul>
            {{ $roles->links() }}
        @endif
    </div>
@endsection
