<x-layout title="Create account">
    <x-auth-shell
        eyebrow="Create account"
        title="Open your Keeply cabinet."
        copy="One private workspace for the products, documents, and follow-ups that matter."
        step="Account file 01"
    >
        <form method="POST" action="{{ route('register') }}" class="form-stack">
            @csrf

            <div class="field">
                <label for="name">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                @error('name') <p id="name-error" class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email') <p id="email-error" class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field-grid">
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" aria-describedby="password-hint @error('password') password-error @enderror">
                    <p id="password-hint" class="field-hint">Use at least 8 characters.</p>
                    @error('password') <p id="password-error" class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                </div>
            </div>

            <button class="button button-full" type="submit">Create my cabinet</button>
        </form>

        <x-slot:footer>
            Already have an account? <a href="{{ route('login') }}">Log in</a>
        </x-slot:footer>
    </x-auth-shell>
</x-layout>
