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
{{ $footerNote ?: 'This message was sent by Maccento CRM. For assistance, reply to this email or contact info@maccento.ca.' }}
