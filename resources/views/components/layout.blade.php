@props(['title' => null])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f4efe2">
    <title>{{ $title ? $title.' · Keeply' : 'Keeply · Your ownership cabinet' }}</title>
    <link rel="stylesheet" href="{{ asset('css/keeply.css') }}">
    <script src="{{ asset('js/keeply.js') }}" defer></script>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to content</a>

    <header class="site-header">
        <a class="brand" href="{{ route('home') }}" aria-label="Keeply home">
            <span class="brand-name">Keeply</span>
            <span class="brand-note">Ownership cabinet</span>
        </a>

        <nav class="nav" aria-label="Account navigation">
            @auth
                @if (auth()->user()->hasVerifiedEmail())
                    <a class="nav-link" href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>Today</a>
                    <a class="nav-link" href="{{ route('items.index') }}" @if (request()->routeIs('items.*')) aria-current="page" @endif>Products</a>
                    <a class="nav-link" href="{{ route('cases.index') }}" @if (request()->routeIs('cases.*', 'action-packs.*', 'reminders.*')) aria-current="page" @endif>Cases</a>
                    <a class="nav-link" href="{{ route('account.show') }}" @if (request()->routeIs('account.*')) aria-current="page" @endif>Account</a>
                @endif
                <span class="nav-identity">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="link-button" type="submit">Log out</button>
                </form>
            @else
                <a class="nav-link" href="{{ route('login') }}">Log in</a>
                <a class="button button-small" href="{{ route('register') }}">Open your cabinet</a>
            @endauth
        </nav>
    </header>

    <main id="main-content" class="page">
        {{ $slot }}
    </main>

    <footer class="site-footer">
        <span>Keep what matters.</span>
        <span>Private by default.</span>
    </footer>
</body>
</html>
