@extends('layouts.public')

@section('title', 'About BloodCare')

@section('content')
    <section class="page-hero">
        <div class="public-container page-hero-grid">
            <div>
                <p class="eyebrow">About BloodCare</p>
                <h1>A clearer connection between donors, staff and blood supply.</h1>
                <p>
                    BloodCare is a university project designed to make donor services convenient
                    and give staff one dependable place to manage donation records and inventory.
                </p>
            </div>
            <div class="page-hero-icon"><x-bloodcare-icon name="heart" :size="62" /></div>
        </div>
    </section>

    <section class="section">
        <div class="public-container impact-grid">
            <div class="content-heading">
                <p class="eyebrow">Our purpose</p>
                <h2>Less paperwork. Better visibility. More time to care.</h2>
                <p>
                    Donors can register, schedule a visit and access a safe digital card.
                    Authorized staff manage donor records, appointments, donations and blood inventory
                    from a protected workspace.
                </p>

                <div class="step-list">
                    <div class="step">
                        <span class="step-number">01</span>
                        <div><h3>Designed around donor privacy</h3><p>Public pages show only the information needed for each donor service.</p></div>
                    </div>
                    <div class="step">
                        <span class="step-number">02</span>
                        <div><h3>One connected staff workflow</h3><p>Donation records update donor history, eligibility and stock together.</p></div>
                    </div>
                    <div class="step">
                        <span class="step-number">03</span>
                        <div><h3>Accountable changes</h3><p>Important staff actions appear in a read-only system history.</p></div>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <article class="content-card">
                    <span class="content-card-icon"><x-bloodcare-icon name="map" :size="25" /></span>
                    <h2>Yangon Central</h2>
                    <p>Main donation centre<br>Monday–Saturday<br>8:30 AM–4:30 PM</p>
                </article>
                <article class="content-card">
                    <span class="content-card-icon"><x-bloodcare-icon name="phone" :size="25" /></span>
                    <h2>Contact the team</h2>
                    <p>01 555 0123<br>donors@bloodcare.test<br>Appointment support</p>
                </article>
                <article class="content-card wide">
                    <span class="content-card-icon"><x-bloodcare-icon name="shield" :size="25" /></span>
                    <h2>Prototype notice</h2>
                    <p>
                        BloodCare is currently a development project. Contact details, centre names,
                        statistics and donor results on this version are sample content for testing the user interface.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <section class="section section-soft">
        <div class="public-container">
            <div class="cta-panel">
                <div>
                    <h2>Ready to take the first step?</h2>
                    <p>Register your details or explore the eligibility guide before booking.</p>
                </div>
                <a class="button button-secondary" href="{{ route('donor.register') }}">Become a donor</a>
            </div>
        </div>
    </section>
@endsection
