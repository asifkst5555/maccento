{{ $heading }}

@if(!empty($intro))
{{ $intro }}

@endif
@foreach($bodyLines as $line)
{{ $line }}

@endforeach
@if(!empty($ctaLabel) && !empty($ctaUrl))
{{ $ctaLabel }}: {{ $ctaUrl }}

@endif
{{ $footerNote ?: "Best regards,\nAlessio Battista\nMaccento Real Estate Media" }}
