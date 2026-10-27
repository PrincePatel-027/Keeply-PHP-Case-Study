<x-layout>
    <section class="cabinet-intro home-intro">
        <div>
            <p class="section-code">Your ownership cabinet</p>
            <h1>Everything you own, filed. Every next step, clear.</h1>
        </div>
        <aside class="intro-note">
            Keeply turns scattered receipts, warranty details, and service conversations into one useful ownership record.
        </aside>
    </section>

    <section class="cabinet-files" aria-label="How Keeply works">
        <article class="file-card" data-tab="Know your position">
            <span class="file-number">FILE 01</span>
            <h2>Deadlines without the guesswork.</h2>
            <p>See the purchase and coverage facts behind every suggested action.</p>
            <span class="file-detail">Returns · Warranty · Repairs</span>
        </article>

        <article class="file-card file-card-offset" data-tab="Build your case">
            <span class="file-number">FILE 02</span>
            <h2>Evidence ready when it matters.</h2>
            <p>Keep receipts, identifiers, issue photos, and contact references together.</p>
            <span class="file-detail">Facts · Documents · Timeline</span>
        </article>

        <article class="file-card file-card-lower" data-tab="Finish the follow-up">
            <span class="file-number">FILE 03</span>
            <h2>Promises that do not disappear.</h2>
            <p>Record what happened, what was promised, and when you need to act again.</p>
            <span class="file-detail">Actions · Reminders · Outcomes</span>
        </article>
    </section>

    <section class="home-cta">
        <div>
            <p class="section-code">Start with one product</p>
            <h2>Make your first ownership record.</h2>
        </div>
        <a class="button" href="{{ route('register') }}">Open your private cabinet</a>
    </section>
</x-layout>
