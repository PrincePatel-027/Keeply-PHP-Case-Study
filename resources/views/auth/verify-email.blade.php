<x-layout title="Verify email">
    <x-auth-shell
        eyebrow="Verify email"
        title="Label this cabinet as yours."
        copy="{{ $localVerificationUrl ? 'This local build does not deliver email yet. Verify this account here to continue.' : 'Use the link sent to your email before entering your private workspace.' }}"
        step="Account file 02"
    >
        <div class="address-slip">
            <span>{{ $localVerificationUrl ? 'Signed in as' : 'Verification sent to' }}</span>
            <strong>{{ auth()->user()->email }}</strong>
        </div>

        @if ($localVerificationUrl)
            <a class="button button-full" href="{{ $localVerificationUrl }}">Verify and continue</a>
        @else
            @if (session('status') === 'verification-link-sent')
                <p class="notice" role="status">A fresh verification link has been sent.</p>
            @endif

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button class="button button-full" type="submit">Send another verification link</button>
            </form>
        @endif

        <x-slot:footer>
            {{ $localVerificationUrl ? 'This shortcut is available only during local development.' : 'You can safely close this page and return from the email link.' }}
        </x-slot:footer>
    </x-auth-shell>
</x-layout>
