<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keeply follow-up reminder</title>
</head>
<body style="margin:0;background:#f4eedf;color:#17264d;font-family:Arial,sans-serif;line-height:1.6;">
    <div style="max-width:620px;margin:0 auto;padding:32px 20px;">
        <div style="background:#fffaf0;border:1px solid #d8ccb2;padding:28px;">
            <p style="margin:0 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:.12em;">Keeply follow-up</p>
            <h1 style="margin:0 0 20px;font-size:26px;line-height:1.2;">{{ $reminder->resolutionCase->title }}</h1>
            <p><strong>Product:</strong> {{ $reminder->resolutionCase->item->name }}</p>
            <p><strong>Due:</strong> {{ $reminder->due_at->format('j F Y, g:i A') }}</p>
            <p>{{ $reminder->caseEvent->summary }}</p>
            <p style="margin:24px 0 0;">
                <a href="{{ route('cases.show', $reminder->resolutionCase) }}" style="display:inline-block;background:#1d4ed8;color:#ffffff;padding:11px 17px;text-decoration:none;font-weight:bold;">Open this case in Keeply</a>
            </p>
        </div>
        <p style="font-size:12px;color:#596273;">This email reflects a follow-up date recorded in your Keeply case. It does not contact the business or submit a claim for you.</p>
    </div>
</body>
</html>
