<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Request - {{ $fuelRequest->request_no }}</title>
    <link rel="stylesheet" href="{{ asset('css/digifuel.css') }}">
    <style>
        body {
            background: #f1f5f9;
            padding: 40px 20px;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="width: 100%; max-width: 760px; margin: 0 auto 24px auto; display: flex; justify-content: space-between; align-items: center; gap: 16px;">
        <a href="{{ route('history.index') }}" class="btn-digi btn-digi-gray">Kembali ke History</a>
        <button onclick="window.print()" class="btn-digi btn-digi-blue">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Cetak Dokumen
        </button>
    </div>

    @include('fuel.document_partial', ['fuelRequest' => $fuelRequest])
</body>
</html>
