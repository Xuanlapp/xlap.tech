@php
    $scrollTo = $scrollTo ?? 'body';
@endphp

<x-offorest.pagination :paginator="$paginator" :scroll-to="$scrollTo" />
