<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'APO Guard Report')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=source-sans-3:400,600,700,800|libre-baskerville:700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    <style>
        :root {
            --apo-navy: #14233a;
            --apo-navy-2: #1e3354;
            --apo-gold: #b08938;
            --apo-line: #d6dde8;
            --apo-muted: #5b6b7f;
        }
        body { font-family: 'Source Sans 3', ui-sans-serif, system-ui, sans-serif; color: #14233a; }
        .report-title { font-family: 'Libre Baskerville', Georgia, serif; }

        .doc-sheet {
            background: #fff;
            border: 1px solid var(--apo-line);
            box-shadow: 0 10px 30px rgba(20, 35, 58, 0.06);
        }

        .letterhead {
            border-bottom: 3px solid var(--apo-navy);
            position: relative;
        }
        .letterhead::after {
            content: '';
            display: block;
            height: 3px;
            background: var(--apo-gold);
            margin-top: 3px;
        }

        .kpi-strip {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            border: 1px solid var(--apo-line);
        }
        .kpi-strip > div {
            padding: 10px 8px;
            text-align: center;
            border-right: 1px solid var(--apo-line);
            background: #f7f9fc;
        }
        .kpi-strip > div:last-child { border-right: 0; }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .report-table th {
            background: var(--apo-navy);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 8px 6px;
            text-align: left;
            border: 1px solid var(--apo-navy);
        }
        .report-table td {
            font-size: 11.5px;
            padding: 7px 6px;
            border: 1px solid #e5ebf3;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }
        .report-table tbody tr:nth-child(even) td { background: #f8fafc; }

        @media print {
            .no-print { display: none !important; }
            html, body {
                background: #fff !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .page-wrap { max-width: none !important; margin: 0 !important; padding: 0 !important; }
            .doc-sheet {
                border: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            .report-table { font-size: 10px; }
            .report-table th, .report-table td { padding: 5px 4px; }
            a { text-decoration: none; color: inherit; }
        }

        @page {
            size: @yield('page-size', 'A4 landscape');
            margin: 10mm;
        }
    </style>
</head>
<body class="bg-[#eef2f7] antialiased">
    <div class="no-print sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <div>
                <p class="text-sm font-extrabold uppercase tracking-wide text-slate-900">@yield('title', 'Report')</p>
                <p class="text-xs text-slate-500">Official APO Security Agency document</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @isset($csvUrl)
                    <a href="{{ $csvUrl }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        Export CSV
                    </a>
                @endisset
                @isset($pdfUrl)
                    <a href="{{ $pdfUrl }}" class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-800 hover:bg-emerald-100">
                        Download PDF
                    </a>
                    <a href="{{ $pdfUrl }}" target="_blank" rel="noreferrer" class="inline-flex items-center rounded-lg bg-[var(--apo-navy)] px-4 py-2 text-sm font-bold text-white hover:bg-[var(--apo-navy-2)]">
                        Print / PDF
                    </a>
                @else
                    <button type="button" onclick="window.print()" class="inline-flex items-center rounded-lg bg-[var(--apo-navy)] px-4 py-2 text-sm font-bold text-white hover:bg-[var(--apo-navy-2)]">
                        Print
                    </button>
                @endif
                <a href="{{ route('reports.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                    Back
                </a>
            </div>
        </div>
    </div>

    <div class="page-wrap mx-auto max-w-7xl px-4 py-6">
        @hasSection('filters')
            <div class="no-print mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                @yield('filters')
            </div>
        @endif

        <div class="doc-sheet overflow-hidden rounded-sm bg-white">
            {{-- Formal letterhead --}}
            <div class="letterhead px-6 pt-6 pb-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full border-2 border-[var(--apo-navy)] bg-[var(--apo-navy)] text-white">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                <path d="M12 3 5.5 5.4v5.2c0 4.1 2.6 7.8 6.5 9.4 3.9-1.6 6.5-5.3 6.5-9.4V5.4L12 3Z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[.28em] text-[var(--apo-gold)]">Republic of the Philippines</p>
                            <p class="text-xl font-extrabold uppercase tracking-[.12em] text-[var(--apo-navy)]">APO Security Agency</p>
                            <p class="text-xs font-semibold uppercase tracking-[.16em] text-[var(--apo-muted)]">Kidapawan City · Cotabato</p>
                        </div>
                    </div>
                    <div class="min-w-[210px] border border-[var(--apo-line)] bg-[#f7f9fc] px-3 py-2 text-right text-[11px] leading-relaxed text-[var(--apo-muted)]">
                        <p class="font-bold uppercase tracking-wide text-[var(--apo-navy)]">Document Control</p>
                        <p>Ref: <span class="font-semibold text-[var(--apo-navy)]">@hasSection('doc-ref')@yield('doc-ref')@else APO-RPT-{{ now()->format('Ymd-Hi') }}@endif</span></p>
                        <p>Generated: {{ now()->timezone(config('app.timezone'))->format('M j, Y · g:i A') }}</p>
                        <p>Prepared by: {{ auth()->user()?->name ?? 'System' }}</p>
                    </div>
                </div>

                <div class="mt-5 text-center">
                    <h1 class="report-title text-2xl font-bold uppercase tracking-[.08em] text-[var(--apo-navy)]">@yield('heading')</h1>
                    @hasSection('period')
                        <p class="mt-1 text-sm text-[var(--apo-muted)]">
                            Reporting Period: <span class="font-semibold text-[var(--apo-navy)]">@yield('period')</span>
                        </p>
                    @endif
                    @hasSection('filter-summary')
                        <p class="mt-1 text-xs text-[var(--apo-muted)]">@yield('filter-summary')</p>
                    @endif
                    <p class="mt-2 inline-block border border-[var(--apo-gold)] px-3 py-0.5 text-[10px] font-bold uppercase tracking-[.18em] text-[var(--apo-gold)]">
                        Confidential · Internal Use Only
                    </p>
                </div>
            </div>

            @hasSection('summary')
                <div class="px-6 pt-4">
                    @yield('summary')
                </div>
            @endif

            <div class="px-6 py-4">
                @yield('content')
            </div>

            <div class="mx-6 mb-6 grid grid-cols-1 gap-6 border-t border-[var(--apo-line)] pt-5 text-xs text-[var(--apo-muted)] md:grid-cols-3">
                <div>
                    <p class="mb-8 font-bold uppercase tracking-wide text-[var(--apo-navy)]">Prepared by</p>
                    <div class="border-t border-slate-400 pt-1">
                        {{ auth()->user()?->name ?? '________________' }}<br>
                        <span class="text-[10px] uppercase tracking-wide">Authorized Officer</span>
                    </div>
                </div>
                <div>
                    <p class="mb-8 font-bold uppercase tracking-wide text-[var(--apo-navy)]">Reviewed by</p>
                    <div class="border-t border-slate-400 pt-1">
                        _________________________<br>
                        <span class="text-[10px] uppercase tracking-wide">Supervisor / Admin</span>
                    </div>
                </div>
                <div class="text-right md:text-left">
                    <p class="font-bold uppercase tracking-wide text-[var(--apo-navy)]">APO Guard Monitoring System</p>
                    <p class="mt-1">Official operational report. Do not distribute outside authorized personnel.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
