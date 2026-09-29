{{-- SEO head tags (#273). Title/description/keywords come from @section yields. --}}
@php
    $seoTitle = trim($__env->yieldContent('title'));
    $seoDescription = trim($__env->yieldContent('meta_description'));
    $seoKeywords = trim($__env->yieldContent('meta_keywords'));
    $seoNoindex = $__env->hasSection('seo_noindex')
        ? filter_var(trim($__env->yieldContent('seo_noindex')), FILTER_VALIDATE_BOOLEAN)
        : null;
    $seo = \App\Support\SeoMeta::fromRequest(request(), [
        'title' => $seoTitle,
        'description' => $seoDescription,
        'keywords' => $seoKeywords,
        'noindex' => $seoNoindex,
    ]);
@endphp
<title>{{ $seo->title }}</title>
<meta name="application-name" content="{{ $seo->siteName }}">
<meta name="apple-mobile-web-app-title" content="{{ $seo->siteName }}">
<meta name="description" content="{{ $seo->description }}">
@if($seo->keywords !== '')
    <meta name="keywords" content="{{ $seo->keywords }}">
@endif
@if($seo->noindex)
    <meta name="robots" content="noindex, nofollow">
@else
    <meta name="robots" content="index, follow">
@endif
<link rel="canonical" href="{{ $seo->canonical }}">
<meta property="og:type" content="{{ $seo->ogType }}">
<meta property="og:site_name" content="{{ $seo->siteName }}">
<meta property="og:title" content="{{ $seo->title }}">
<meta property="og:description" content="{{ $seo->description }}">
<meta property="og:url" content="{{ $seo->ogUrl }}">
<meta property="og:locale" content="{{ $seo->ogLocale }}">
<meta property="og:image" content="{{ $seo->ogImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo->title }}">
<meta name="twitter:description" content="{{ $seo->description }}">
<meta name="twitter:image" content="{{ $seo->ogImage }}">
