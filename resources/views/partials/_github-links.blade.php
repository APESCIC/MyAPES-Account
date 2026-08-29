@php
    $variant = $variant ?? 'footer';
    $links = [
        [
            'label' => 'GitHub',
            'url' => config('myapes.github.repository_url'),
            'icon' => 'github',
        ],
        [
            'label' => 'Open an issue',
            'url' => config('myapes.github.new_issue_url'),
            'icon' => 'circle-plus',
        ],
        [
            'label' => 'Discussions',
            'url' => config('myapes.github.discussions_url'),
            'icon' => 'messages-square',
        ],
    ];
@endphp

@if ($variant === 'sidebar')
    <ul class="sidebar-support__links">
        @foreach ($links as $link)
            <li>
                <a
                    class="sidebar-support__pill"
                    href="{{ $link['url'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <span class="sidebar-support__pill-icon">
                        <i data-lucide="{{ $link['icon'] }}" aria-hidden="true"></i>
                    </span>
                    <span class="sidebar-support__pill-label">{{ $link['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
@else
    <ul class="github-links github-links--{{ $variant }}">
        @foreach ($links as $link)
            <li>
                <a
                    href="{{ $link['url'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                >{{ $link['label'] }}</a>
            </li>
        @endforeach
    </ul>
@endif
