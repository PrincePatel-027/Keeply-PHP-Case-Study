<x-layout title="Log in">
    <x-auth-shell
        eyebrow="Welcome back"
        title="Return to your cabinet."
        copy="Your products, evidence, and open actions are kept where you left them."
        step="Account access"
    >
        @if (session('status'))
            <p class="notice" role="status">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('login') }}" class="form-stack">
            @csrf

            <div class="field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email') <p id="email-error" class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <div class="label-row">
                    <label for="password">Password</label>
                    <a href="{{ route('password.request') }}">Forgot password?</a>
                </div>
                <input id="password" name="password" type="password" required autocomplete="current-password" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                @error('password') <p id="password-error" class="field-error">{{ $message }}</p> @enderror
            </div>

            <label class="check-row" for="remember">
                <input id="remember" name="remember" type="checkbox" value="1">
                <span>Keep me logged in on this device</span>
            </label>

            <button class="button button-full" type="submit">Open my cabinet</button>
        </form>

        <x-slot:footer>
            New to Keeply? <a href="{{ route('register') }}">Create an account</a>
        </x-slot:footer>
    </x-auth-shell>
</x-layout>
