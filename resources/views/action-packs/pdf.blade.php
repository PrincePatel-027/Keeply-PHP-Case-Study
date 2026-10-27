<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 34px; }
        body { color: #1e2b34; font-family: "DejaVu Sans", sans-serif; font-size: 10px; line-height: 1.5; }
        h1 { margin: 0 0 4px; font-size: 22px; }
        h2 { margin: 0 0 10px; padding-bottom: 5px; border-bottom: 1px solid #9ca7ad; font-size: 13px; }
        p { margin: 0 0 9px; }
        .header { margin-bottom: 20px; padding-bottom: 14px; border-bottom: 3px double #1e2b34; }
        .meta { color: #586973; font-size: 8px; }
        .section { margin: 0 0 20px; page-break-inside: avoid; }
        .facts { width: 100%; border-collapse: collapse; }
        .facts td { padding: 6px; border: 1px solid #c6cccf; vertical-align: top; }
        .facts td:first-child { width: 28%; color: #586973; font-weight: bold; }
        .position { display: inline-block; width: 45%; margin-right: 3%; padding: 10px; border: 1px solid #9ca7ad; vertical-align: top; }
        .position strong { display: block; margin-bottom: 5px; text-transform: uppercase; }
        .chronology { margin: 0; padding-left: 18px; }
        .chronology li { margin-bottom: 8px; }
        .letter { padding: 16px; border: 1px solid #1e2b34; white-space: pre-wrap; }
        .disclaimer { padding-top: 10px; border-top: 1px solid #1e2b34; color: #586973; font-size: 8px; }
    </style>
</head>
<body>
    <header class="header">
        <div class="meta">KEEPLY ACTION PACK · VERSION {{ $snapshot['pack_version'] }}</div>
        <h1>{{ $snapshot['facts']['case']['title'] }}</h1>
        <p>{{ $snapshot['facts']['item']['name'] }} · Assessment v{{ $snapshot['facts']['assessment']['version'] }}</p>
        <div class="meta">Generated {{ Carbon\CarbonImmutable::parse($snapshot['generated_at'])->format('j M Y, g:i A') }}</div>
    </header>

    <section class="section">
        <h2>Owner and product</h2>
        <table class="facts">
            <tr><td>Owner</td><td>{{ $snapshot['facts']['owner']['name'] }} · {{ $snapshot['facts']['owner']['email'] }}</td></tr>
            <tr><td>Product</td><td>{{ $snapshot['facts']['item']['name'] }} · {{ collect([$snapshot['facts']['item']['brand'], $snapshot['facts']['item']['model']])->filter()->join(' · ') ?: 'Brand and model not recorded' }}</td></tr>
            <tr><td>Serial number</td><td>{{ $snapshot['facts']['item']['serial_number'] ?: 'Not recorded' }}</td></tr>
            <tr><td>Seller</td><td>{{ $snapshot['facts']['item']['seller_name'] ?: 'Not recorded' }}</td></tr>
            <tr><td>Purchase date</td><td>{{ $snapshot['facts']['item']['purchase_date'] ? Carbon\CarbonImmutable::parse($snapshot['facts']['item']['purchase_date'])->format('j M Y') : 'Not recorded' }}</td></tr>
        </table>
    </section>

    <section class="section">
        <h2>Recorded position</h2>
        @foreach ($snapshot['facts']['assessment']['snapshot']['coverage'] as $position)
            <div class="position">
                <strong>{{ Str::headline($position['kind']) }} · {{ Str::headline($position['state']) }}</strong>
                <p>{{ $position['explanation'] }}</p>
            </div>
        @endforeach
    </section>

    <section class="section">
        <h2>Issue and requested outcome</h2>
        <p>{{ $snapshot['facts']['case']['problem_summary'] }}</p>
        <p><strong>Problem date:</strong> {{ $snapshot['facts']['case']['failure_date'] ? Carbon\CarbonImmutable::parse($snapshot['facts']['case']['failure_date'])->format('j M Y') : 'Not recorded' }}</p>
        <p><strong>Requested outcome:</strong> {{ Str::headline($snapshot['facts']['case']['desired_outcome']) }}</p>
    </section>

    <section class="section">
        <h2>Dated chronology</h2>
        @if ($snapshot['events'] === [])
            <p>No timeline events were recorded.</p>
        @else
            <ol class="chronology">
                @foreach ($snapshot['events'] as $event)
                    <li>
                        <strong>{{ Carbon\CarbonImmutable::parse($event['occurred_at'])->format('j M Y, g:i A') }} · {{ Str::headline($event['type']) }}</strong><br>
                        {{ $event['summary'] }}
                        @if ($event['reference'])<br><span class="meta">Reference: {{ $event['reference'] }}</span>@endif
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    <section class="section">
        <h2>Selected evidence manifest</h2>
        @if ($snapshot['evidence'] === [])
            <p>No private documents were selected.</p>
        @else
            <ul>
                @foreach ($snapshot['evidence'] as $document)
                    <li>{{ $document['original_name'] }} · {{ Str::headline($document['purpose']) }} · SHA-256 {{ $document['sha256'] }}</li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="section">
        <h2>Evidence still missing or optional</h2>
        <ul>
            @foreach ($snapshot['facts']['checklist'] as $evidence)
                @if ($evidence['state'] !== 'present')
                    <li><strong>{{ $evidence['label'] }} · {{ Str::headline($evidence['state']) }}</strong><br>{{ $evidence['explanation'] }}</li>
                @endif
            @endforeach
        </ul>
    </section>

    <section class="section">
        <h2>Factual request letter</h2>
        <div class="letter">{{ $snapshot['letter_body'] }}</div>
    </section>

    <footer class="disclaimer">
        <strong>Factual record, not a legal determination.</strong>
        This Action Pack organises information entered in Keeply. It does not interpret warranty terms, guarantee eligibility, submit a request, or promise an outcome.
    </footer>
</body>
</html>
