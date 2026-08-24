@extends('layouts.app')

@section('title', 'Change Log Hub | MyAPES Core')

@section('content')
<div class="change-log" data-change-log>
    <section class="change-log__hero" aria-labelledby="change-log-title">
        <p class="eyebrow">Release records</p>
        <h1 id="change-log-title">Change Log Hub</h1>
        <p>Track MyAPES Core releases, fixes, compliance work, accessibility improvements, and user-facing changes.</p>

        <div class="change-log__current">
            <div>
                <span>Current version</span>
                <strong>v{{ $currentRelease['version'] }}</strong>
            </div>
            <div>
                <span>{{ ucfirst($currentRelease['type']) }} · {{ ucfirst($currentRelease['channel']) }}</span>
                <strong>{{ $currentRelease['title'] }}</strong>
            </div>
            <a href="#release-v{{ str_replace('.', '-', $currentRelease['version']) }}">View current release</a>
        </div>
    </section>

    <section class="change-log__controls" aria-label="Find release records" data-change-log-controls hidden>
        <div class="change-log__search">
            <label for="change-log-search">Search release notes</label>
            <input
                id="change-log-search"
                type="search"
                inputmode="search"
                autocomplete="off"
                placeholder="Search versions, changes, affected areas…"
                data-change-log-search
            >
        </div>

        <fieldset class="change-log__filters">
            <legend>Filter releases</legend>
            @foreach([
                'all' => 'All releases',
                'current' => 'Current release',
                'beta' => 'Beta',
                'added' => 'Added',
                'changed' => 'Changed',
                'fixed' => 'Fixed',
                'removed' => 'Removed',
                'security' => 'Security',
                'compliance' => 'Compliance',
                'accessibility' => 'Accessibility',
                'public-facing' => 'Public-facing',
                'internal-only' => 'Internal-only',
            ] as $filter => $label)
                <button
                    type="button"
                    data-change-log-filter="{{ $filter }}"
                    aria-pressed="{{ $filter === 'all' ? 'true' : 'false' }}"
                >{{ $label }}</button>
            @endforeach
        </fieldset>

        <div class="change-log__actions">
            <button type="button" data-change-log-expand>Expand all releases</button>
            <button type="button" data-change-log-collapse>Collapse all releases</button>
        </div>

        <p class="change-log__status" role="status" aria-live="polite" data-change-log-status>
            Showing {{ count($releases) }} releases
        </p>
    </section>

    <section class="change-log__records" aria-label="Release history">
        @foreach($releases as $release)
            @php($isCurrent = $loop->first)
            <article
                id="release-v{{ str_replace('.', '-', $release['version']) }}"
                class="change-log__release"
                data-release-record
                data-version="{{ $release['version'] }}"
                data-current="{{ $isCurrent ? 'true' : 'false' }}"
                data-channel="{{ $release['channel'] }}"
                data-categories="{{ implode(' ', $release['categories']) }}"
                data-audiences="{{ implode(' ', $release['audiences']) }}"
            >
                <details data-release-details @if($isCurrent) open @endif>
                    <summary>
                        <span class="change-log__release-version">v{{ $release['version'] }}</span>
                        <span>{{ $release['date'] }}</span>
                        <span>{{ ucfirst($release['channel']) }}</span>
                        <span>{{ $release['title'] }}</span>
                    </summary>

                    <div class="change-log__release-body">
                        <div class="change-log__badges" aria-label="Release classifications">
                            <span>{{ ucfirst($release['type']) }}</span>
                            @foreach($release['categories'] as $category)
                                <span>{{ ucfirst($category) }}</span>
                            @endforeach
                            @foreach($release['audiences'] as $audience)
                                <span>{{ ucfirst(str_replace('-', ' ', $audience)) }}</span>
                            @endforeach
                        </div>

                        <section>
                            <h2>Summary</h2>
                            <p>{{ $release['summary'] }}</p>
                        </section>

                        <section>
                            <h2>Detailed changes</h2>
                            <ul>
                                @foreach($release['changes'] as $change)
                                    <li>{{ $change }}</li>
                                @endforeach
                            </ul>
                        </section>

                        <section>
                            <h2>Affected areas</h2>
                            <ul>
                                @foreach($release['affected_areas'] as $area)
                                    <li>{{ $area }}</li>
                                @endforeach
                            </ul>
                        </section>

                        <section>
                            <h2>Version decision</h2>
                            <p>{{ $release['version_rationale'] }}</p>
                        </section>

                        <section>
                            <h2>Validation</h2>
                            <ul>
                                @foreach($release['validation'] as $check)
                                    <li>{{ $check }}</li>
                                @endforeach
                            </ul>
                        </section>

                        <section>
                            <h2>Known limitations</h2>
                            <ul>
                                @foreach($release['known_limitations'] as $limitation)
                                    <li>{{ $limitation }}</li>
                                @endforeach
                            </ul>
                        </section>

                        <section>
                            <h2>Rollback notes</h2>
                            <p>{{ $release['rollback'] }}</p>
                        </section>

                        <section>
                            <h2>Source</h2>
                            <p>{{ $release['provenance'] }}</p>
                            <ul class="change-log__references">
                                @foreach($release['references'] as $reference)
                                    <li><a href="{{ $reference['url'] }}">{{ $reference['label'] }}</a></li>
                                @endforeach
                            </ul>
                        </section>
                    </div>
                </details>
            </article>
        @endforeach
    </section>

    <section class="change-log__empty" data-change-log-empty hidden>
        <h2>No releases found</h2>
        <p>Try a different search or choose All releases.</p>
    </section>
</div>
@endsection
