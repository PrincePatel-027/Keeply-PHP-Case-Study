<x-layout title="Choose a new password">
    <x-auth-shell
        eyebrow="New password"
        title="Secure your cabinet again."
        copy="Choose a new password for your Keeply account."
        step="Recovery file"
    >
        <form method="POST" action="{{ route('password.store') }}" class="form-stack">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="email" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email') <p id="email-error" class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field-grid">
                <div class="field">
                    <label for="password">New password</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" aria-describedby="password-hint @error('password') password-error @enderror">
                    <p id="password-hint" class="field-hint">Use at least 8 characters.</p>
                    @error('password') <p id="password-error" class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                </div>
            </div>

            <button class="button button-full" type="submit">Save my new password</button>
        </form>
    </x-auth-shell>
</x-layout>
