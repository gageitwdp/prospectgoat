@php
    $shareImagePath = 'pg-site-image.png';
    $shareImage = url('/' . rawurlencode($shareImagePath));
    $faviconPath = 'thegoat-favicon.png';
    $favicon = url('/' . rawurlencode($faviconPath));
    $faviconIco = url('/favicon.ico');
    $shareUrl = $shareUrl ?? url()->current();
    $shareTitle = $shareTitle ?? config('app.name', 'ProspectGoat');
    $shareDescription = $shareDescription ?? 'ProspectGoat for inquiries, events, and mortgage planning.';
@endphp

<meta name="description" content="{{ $shareDescription }}">
<link rel="canonical" href="{{ $shareUrl }}">
<link rel="icon" type="image/x-icon" href="{{ $faviconIco }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $favicon }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ config('app.name', 'ProspectGoat') }}">
<meta property="og:title" content="{{ $shareTitle }}">
<meta property="og:description" content="{{ $shareDescription }}">
<meta property="og:url" content="{{ $shareUrl }}">
<meta property="og:image" content="{{ $shareImage }}">
<meta property="og:image:secure_url" content="{{ $shareImage }}">
<meta property="og:image:alt" content="ProspectGoat Full Logo">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $shareTitle }}">
<meta name="twitter:description" content="{{ $shareDescription }}">
<meta name="twitter:image" content="{{ $shareImage }}">
<meta name="twitter:image:alt" content="ProspectGoat Full Logo">

<script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => config('app.name', 'ProspectGoat'),
        'url' => url('/'),
        'logo' => $shareImage,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
