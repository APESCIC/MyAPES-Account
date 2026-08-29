@php
    $variant = $variant ?? 'footer';
    $links = [
        [
            'label' => 'GitHub',
            'url' => config('myapes.github.repository_url'),
        ],
        [
            'label' => 'Open an issue',
            'url' => config('myapes.github.new_issue_url'),
        ],
        [
            'label' => 'Discussions',
            'url' => config('myapes.github.discussions_url'),
        ],
    ];
@endphp

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
