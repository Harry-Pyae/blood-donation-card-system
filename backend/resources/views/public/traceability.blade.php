@extends('layouts.public')

@section('title', $title)
@section('meta_description', __('bloodcare.traceability.meta'))
@section('robots', 'noindex,nofollow')

@section('content')
    <section class="bc-trace-section">
        <div class="public-container bc-trace-wrap">
            <header class="bc-trace-hero">
                <span class="bc-trace-verified" aria-hidden="true"><x-bloodcare-icon name="shield" :size="31" /></span>
                <div>
                    <p class="eyebrow">{{ $eyebrow }}</p>
                    <h1>{{ $title }}</h1>
                    <p>{{ $description }}</p>
                </div>
                <span class="bc-trace-status bc-trace-status-{{ str($status)->slug() }}">{{ $statusLabel }}</span>
            </header>

            <div class="bc-trace-reference">
                <span>{{ __('bloodcare.traceability.verified_reference') }}</span>
                <strong>{{ $reference }}</strong>
                <em><x-bloodcare-icon name="check" :size="18" /> {{ __('bloodcare.traceability.live_record') }}</em>
            </div>

            <section class="bc-trace-panel" aria-labelledby="bc-trace-summary-title">
                <header class="bc-trace-panel-heading">
                    <div>
                        <p class="eyebrow">{{ __('bloodcare.traceability.summary_eyebrow') }}</p>
                        <h2 id="bc-trace-summary-title">{{ __('bloodcare.traceability.summary_title') }}</h2>
                    </div>
                </header>
                <dl class="bc-trace-summary-grid">
                    @foreach ($summary as $item)
                        <div>
                            <dt>{{ $item['label'] }}</dt>
                            <dd>{{ $item['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            @if ($kind === 'unit' && (count($ancestors) || count($descendants)))
                <section class="bc-trace-panel" aria-labelledby="bc-trace-lineage-title">
                    <header class="bc-trace-panel-heading">
                        <div>
                            <p class="eyebrow">{{ __('bloodcare.traceability.lineage_eyebrow') }}</p>
                            <h2 id="bc-trace-lineage-title">{{ __('bloodcare.traceability.lineage_title') }}</h2>
                            <p>{{ __('bloodcare.traceability.lineage_help') }}</p>
                        </div>
                    </header>
                    <div class="bc-trace-lineage-grid">
                        @if (count($ancestors))
                            <div>
                                <h3>{{ __('bloodcare.traceability.source_units') }}</h3>
                                @foreach ($ancestors as $item)
                                    @if ($item['url'])
                                        <a href="{{ $item['url'] }}"><strong>{{ $item['unit'] }}</strong><span>{{ $item['component'] }}</span></a>
                                    @else
                                        <span><strong>{{ $item['unit'] }}</strong><span>{{ $item['component'] }}</span></span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        @if (count($descendants))
                            <div>
                                <h3>{{ __('bloodcare.traceability.derived_units') }}</h3>
                                @foreach ($descendants as $item)
                                    @if ($item['url'])
                                        <a href="{{ $item['url'] }}"><strong>{{ $item['unit'] }}</strong><span>{{ $item['component'] }}</span></a>
                                    @else
                                        <span><strong>{{ $item['unit'] }}</strong><span>{{ $item['component'] }}</span></span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            <section class="bc-trace-panel" aria-labelledby="bc-trace-timeline-title">
                <header class="bc-trace-panel-heading">
                    <div>
                        <p class="eyebrow">{{ __('bloodcare.traceability.timeline_eyebrow') }}</p>
                        <h2 id="bc-trace-timeline-title">{{ __('bloodcare.traceability.timeline_title') }}</h2>
                        <p>{{ __('bloodcare.traceability.timeline_help') }}</p>
                    </div>
                </header>
                <ol class="bc-trace-timeline">
                    @foreach ($timeline as $event)
                        <li class="bc-trace-event bc-trace-event-{{ $event['tone'] }}">
                            <span class="bc-trace-event-dot" aria-hidden="true"></span>
                            <div>
                                <header><strong>{{ $event['label'] }}</strong><time>{{ $event['date'] }}</time></header>
                                <p>{{ $event['detail'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            <aside class="bc-trace-privacy">
                <x-bloodcare-icon name="shield" :size="26" />
                <div>
                    <strong>{{ __('bloodcare.traceability.privacy_title') }}</strong>
                    <p>{{ $privacy }}</p>
                </div>
            </aside>

            <div class="bc-trace-actions">
                @if ($kind === 'card')
                    <a class="button button-primary" href="{{ route('card.check') }}">{{ __('bloodcare.traceability.card.open_lookup') }}</a>
                @endif
                <a class="button button-secondary" href="{{ route('home') }}">{{ __('bloodcare.traceability.return_home') }}</a>
            </div>
        </div>
    </section>
@endsection
