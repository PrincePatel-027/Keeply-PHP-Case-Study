<x-layout title="Account settings">
    <section class="collection-heading">
        <div>
            <p class="section-code">Account / Privacy</p>
            <h1>Your records belong to you.</h1>
            <p class="collection-copy">Export your structured Keeply data or request deletion of your account and sole-owner workspaces.</p>
        </div>
    </section>

    @if ($errors->has('account'))
        <p class="notice" role="alert">{{ $errors->first('account') }}</p>
    @endif

    <section class="record-grid" aria-label="Account data controls">
        <article class="record-card">
            <span class="record-type">Portable copy</span>
            <h2>Export account data</h2>
            <p>Download products, cases, timelines, assessments, Action Pack snapshots, and document integrity metadata as JSON.</p>
            <p>Private file contents are not embedded. Download any required receipts, evidence, or PDFs from their records separately.</p>
            <a class="button" href="{{ route('account.export') }}">Download JSON export</a>
        </article>

        <article class="record-card">
            <span class="record-type">30-day recovery window</span>
            <h2>Delete this account</h2>
            <p>Access stops immediately. After 30 days, Keeply permanently removes your account, sole-owner workspaces, records, and private files.</p>
            <p>Deletion is blocked for shared workspaces until ownership can be transferred or the workspace is left.</p>

            <form method="POST" action="{{ route('account.destroy') }}">
                @csrf
                @method('DELETE')

                <label for="password">Current password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                @error('password')<span id="password-error" class="field-error">{{ $message }}</span>@enderror

                <label for="confirmation">Type DELETE MY ACCOUNT</label>
                <input id="confirmation" name="confirmation" type="text" autocomplete="off" required @error('confirmation') aria-invalid="true" aria-describedby="confirmation-error" @enderror>
                @error('confirmation')<span id="confirmation-error" class="field-error">{{ $message }}</span>@enderror

                <button class="button" type="submit">Request account deletion</button>
            </form>
        </article>
    </section>
</x-layout>
