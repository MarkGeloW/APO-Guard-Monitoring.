<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'APO Guard Report')</title>
    <style>
        @page { margin: 10mm 10mm 12mm 10mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #14233a;
            margin: 0;
            padding: 0;
        }

        .letterhead {
            border-bottom: 2.5px solid #14233a;
            padding-bottom: 8px;
            margin-bottom: 2px;
        }
        .gold-rule {
            height: 2.5px;
            background: #b08938;
            margin-bottom: 12px;
        }
        .top { width: 100%; border-collapse: collapse; }
        .logo-box {
            width: 42px;
            height: 42px;
            border: 2px solid #14233a;
            background: #14233a;
            color: #fff;
            text-align: center;
            line-height: 38px;
            font-size: 16px;
            font-weight: bold;
        }
        .agency {
            font-size: 9px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #b08938;
            font-weight: bold;
            margin: 0 0 2px 0;
        }
        .agency-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin: 0;
            color: #14233a;
        }
        .agency-loc {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #5b6b7f;
            margin: 2px 0 0 0;
        }
        .doc-box {
            border: 1px solid #d6dde8;
            background: #f7f9fc;
            padding: 6px 8px;
            text-align: right;
            font-size: 9px;
            color: #5b6b7f;
            width: 210px;
        }
        .doc-box strong {
            display: block;
            color: #14233a;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 2px;
        }

        .title-wrap { text-align: center; margin: 10px 0 12px; }
        .title-wrap h1 {
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin: 0 0 4px;
            color: #14233a;
        }
        .title-wrap .period {
            margin: 0;
            color: #5b6b7f;
            font-size: 10px;
        }
        .title-wrap .filters {
            margin: 2px 0 0;
            color: #5b6b7f;
            font-size: 9px;
        }
        .confidential {
            display: inline-block;
            margin-top: 6px;
            border: 1px solid #b08938;
            color: #b08938;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            padding: 2px 8px;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .summary td {
            border: 1px solid #d6dde8;
            background: #f7f9fc;
            padding: 7px 6px;
            text-align: center;
        }
        .summary .label {
            display: block;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: #5b6b7f;
            margin-bottom: 2px;
        }
        .summary .value {
            font-size: 14px;
            font-weight: bold;
            color: #14233a;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.data th {
            background: #14233a;
            color: #ffffff;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 4px;
            text-align: left;
            border: 1px solid #14233a;
        }
        table.data td {
            border: 1px solid #dce3ee;
            padding: 5px 4px;
            vertical-align: top;
            font-size: 9px;
            word-wrap: break-word;
        }
        table.data tr:nth-child(even) td { background: #f8fafc; }

        .sign-table {
            width: 100%;
            margin-top: 18px;
            border-collapse: collapse;
        }
        .sign-table td {
            width: 33.33%;
            vertical-align: top;
            padding-right: 12px;
            font-size: 9px;
            color: #5b6b7f;
        }
        .sign-label {
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #14233a;
            margin-bottom: 28px;
        }
        .sign-line {
            border-top: 1px solid #94a3b8;
            padding-top: 3px;
        }
        .footer {
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #d6dde8;
            font-size: 8px;
            color: #5b6b7f;
        }
        .empty { text-align: center; padding: 20px; color: #5b6b7f; }
        .muted { color: #5b6b7f; font-size: 8px; }
    </style>
</head>
<body>
    <div class="letterhead">
        <table class="top">
            <tr>
                <td style="width:48px; vertical-align:middle;">
                    <div class="logo-box">A</div>
                </td>
                <td style="vertical-align:middle; padding-left:8px;">
                    <p class="agency">Republic of the Philippines</p>
                    <p class="agency-name">APO Security Agency</p>
                    <p class="agency-loc">Kidapawan City · Cotabato</p>
                </td>
                <td style="width:220px; vertical-align:top;">
                    <div class="doc-box">
                        <strong>Document Control</strong>
                        Ref: @hasSection('doc-ref')@yield('doc-ref')@else APO-RPT-{{ now()->format('Ymd-Hi') }}@endif<br>
                        Generated: {{ now()->timezone(config('app.timezone'))->format('M j, Y · g:i A') }}<br>
                        Prepared by: {{ auth()->user()?->name ?? 'System' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="gold-rule"></div>

    <div class="title-wrap">
        <h1>@yield('heading')</h1>
        @hasSection('period')
            <p class="period">Reporting Period: @yield('period')</p>
        @endif
        @hasSection('filter-summary')
            <p class="filters">@yield('filter-summary')</p>
        @endif
        <div class="confidential">Confidential · Internal Use Only</div>
    </div>

    @hasSection('summary')
        @yield('summary')
    @endif

    @yield('content')

    <table class="sign-table">
        <tr>
            <td>
                <div class="sign-label">Prepared by</div>
                <div class="sign-line">
                    {{ auth()->user()?->name ?? '________________' }}<br>
                    <span class="muted">AUTHORIZED OFFICER</span>
                </div>
            </td>
            <td>
                <div class="sign-label">Reviewed by</div>
                <div class="sign-line">
                    _________________________<br>
                    <span class="muted">SUPERVISOR / ADMIN</span>
                </div>
            </td>
            <td>
                <div class="sign-label">System</div>
                <div>
                    APO Guard Monitoring System<br>
                    <span class="muted">Official operational report. Authorized personnel only.</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        APO Security Agency · Guard Monitoring System · @yield('title')
    </div>
</body>
</html>
