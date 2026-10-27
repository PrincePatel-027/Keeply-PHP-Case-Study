@props(['eyebrow', 'title', 'copy' => null, 'step' => 'Private account'])

<section class="auth-shell">
    <aside class="auth-context" aria-label="Keeply account setup">
        <div>
            <p class="section-code">Keeply / {{ $step }}</p>
            <p class="auth-statement">A private place for the details you should not have to remember.</p>
        </div>

        <dl class="trust-list">
            <div>
                <dt>Documents</dt>
                <dd>Private</dd>
            </div>
            <div>
                <dt>Workspace</dt>
                <dd>Personal</dd>
            </div>
            <div>
                <dt>Your data</dt>
                <dd>Not sold</dd>
            </div>
        </dl>
    </aside>

    <section class="auth-folder">
        <span class="folder-tab">{{ $eyebrow }}</span>
        <div class="auth-folder-body">
            <header class="auth-heading">
                <h1>{{ $title }}</h1>
                @if ($copy)
                    <p>{{ $copy }}</p>
                @endif
            </header>

            {{ $slot }}

            @isset($footer)
                <footer class="auth-footer">{{ $footer }}</footer>
            @endisset
        </div>
    </section>
</section>
