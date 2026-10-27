<x-layout title="Reset password">
    <x-auth-shell
        eyebrow="Account recovery"
        title="Find your account file."
        copy="Enter your email and Keeply will send a secure link to choose a new password."
        step="Recovery file"
    >
        @if (session('status'))
            <p class="notice" role="status">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="form-stack">
            @csrf
            <div class="field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email') <p id="email-error" class="field-error">{{ $message }}</p> @enderror
            </div>
            <button class="button button-full" type="submit">Send my recovery link</button>
        </form>

        <x-slot:footer>
            Remembered it? <a href="{{ route('login') }}">Back to login</a>
        </x-slot:footer>
    </x-auth-shell>
</x-layout>
