@php
    $locale = $report['locale'];
    $t = fn (string $key, array $replace = []) => __('journey_result_pdf.'.$key, $replace, $locale);
    $choice = fn (string $key, int $count, array $replace = []) => trans_choice('journey_result_pdf.'.$key, $count, $replace, $locale);
    $bandColors = ['strong' => '#15803d', 'medium' => '#b45309', 'weak' => '#b91c1c'];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale === 'en' ? 'en' : 'pt-BR' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $t('document_title') }} - {{ $report['profile_name'] }}</title>
    <style>
        @page { margin: 32px 40px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 15px; margin: 0 0 6px; }
        h3 { font-size: 12px; margin: 14px 0 6px; }
        .eyebrow { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin: 0 0 2px; }
        .muted { color: #6b7280; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px 16px; margin-top: 16px; }
        .meta td { padding: 1px 12px 1px 0; }
        .score { font-size: 34px; font-weight: bold; }
        .band { font-weight: bold; }
        table.areas { width: 100%; border-collapse: collapse; }
        table.areas td { padding: 5px 0; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        .bar { width: 100%; height: 8px; background: #f3f4f6; border-radius: 4px; position: relative; }
        .bar-fill { height: 8px; border-radius: 4px; }
        .bar-marker { position: absolute; top: -2px; width: 1px; height: 12px; background: #111827; }
        .competency { margin: 0 0 6px; page-break-inside: avoid; }
        .competency-name { font-weight: bold; }
        .tags { font-size: 9px; color: #4b5563; }
        .footer { margin-top: 20px; font-size: 9px; text-align: right; }
    </style>
</head>
<body>
    <p class="eyebrow">{{ $t('heading') }}</p>
    <h1>{{ $report['profile_name'] }}</h1>
    <table class="meta">
        @if ($report['domain_name'])
            <tr><td class="muted">{{ $t('journey_domain') }}</td><td>{{ $report['domain_name'] }}</td></tr>
        @endif
        <tr><td class="muted">{{ $t('user') }}</td><td>{{ $report['user_name'] }}</td></tr>
        <tr><td class="muted">{{ $t('finished_at') }}</td><td>{{ $report['finished_at'] }}</td></tr>
    </table>

    <div class="card">
        <p class="eyebrow">{{ $t('overall') }}</p>
        <span class="score">{{ $report['score']['value'] }}</span><span class="muted">/100</span>
        <span class="band" style="color: {{ $bandColors[$report['score']['band']] }}">&nbsp;{{ $report['score']['band_label'] }}</span>
        <p class="muted">{{ $choice('declared_count', $report['total_count'], ['declared' => $report['declared_count'], 'total' => $report['total_count']]) }}</p>
    </div>

    <div class="card">
        <p class="eyebrow">{{ $t('areas_eyebrow') }}</p>
        <h2>{{ $t('areas_title') }}</h2>
        <p class="muted">{{ $t('recommendation', ['value' => $report['recommended_area_score']]) }}</p>
        @foreach ($report['area_groups'] as $group)
            @if ($group['title'])
                <h3>{{ $group['title'] }}</h3>
            @endif
            <table class="areas">
                @foreach ($group['areas'] as $area)
                    <tr>
                        <td style="width: 38%">
                            {{ $area['name'] }}<br>
                            <span class="tags">{{ $choice('area_count', $area['total_count'], ['declared' => $area['declared_count'], 'total' => $area['total_count']]) }}</span>
                        </td>
                        <td style="width: 44%; padding-right: 10px">
                            <div class="bar">
                                <div class="bar-fill" style="width: {{ $area['score']['value'] }}%; background: {{ $bandColors[$area['score']['band']] }}"></div>
                                <div class="bar-marker" style="left: {{ $report['recommended_area_score'] }}%"></div>
                            </div>
                        </td>
                        <td style="width: 18%; text-align: right">
                            <strong>{{ $area['score']['value'] }}%</strong>
                            <span class="tags" style="color: {{ $bandColors[$area['score']['band']] }}">{{ $area['score']['band_label'] }}</span>
                        </td>
                    </tr>
                @endforeach
            </table>
        @endforeach
    </div>

    @foreach (['declared_competencies' => 'declared_title', 'undeclared_competencies' => 'undeclared_title'] as $groupsKey => $titleKey)
        <div class="card">
            <p class="eyebrow">{{ $t('competencies_eyebrow') }}</p>
            <h2>{{ $t($titleKey) }}</h2>
            @forelse ($report[$groupsKey] as $group)
                <h3>{{ $group['area'] }}</h3>
                @foreach ($group['competencies'] as $competency)
                    <div class="competency">
                        <span class="competency-name">{{ $competency['name'] }}</span>
                        @if ($competency['description'])
                            <br><span class="muted">{{ $competency['description'] }}</span>
                        @endif
                        <br><span class="tags">
                            @if ($competency['difficulty']){{ $t('difficulty') }}: {{ $competency['difficulty'] }}@endif
                            @if ($competency['difficulty'] && $competency['domain']) &middot; @endif
                            @if ($competency['domain']){{ $t('domain') }}: {{ $competency['domain'] }}@endif
                        </span>
                    </div>
                @endforeach
            @empty
                <p class="muted">{{ $t('empty_group') }}</p>
            @endforelse
        </div>
    @endforeach

    <p class="footer muted">{{ $t('generated_at', ['date' => $report['generated_at']]) }}</p>
</body>
</html>
