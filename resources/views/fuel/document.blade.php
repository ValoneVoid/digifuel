<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Request - {{ $fuelRequest->request_no }}</title>
    <link rel="stylesheet" href="{{ asset('css/digifuel.css') }}?v={{ file_exists(public_path('css/digifuel.css')) ? filemtime(public_path('css/digifuel.css')) : time() }}">
    @if(file_exists(public_path('css/digifuel.css')))
        <style id="digifuel-core-css">{!! file_get_contents(public_path('css/digifuel.css')) !!}</style>
    @endif
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
        <div style="display: flex; gap: 8px;">
            @if($fuelRequest->isVendorApproved())
                <a href="{{ route('fuel.pdf', $fuelRequest->id) }}" class="btn-digi btn-digi-green" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Unduh PDF Resmi
                </a>
            @endif
            <button onclick="window.print()" class="btn-digi btn-digi-blue">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Cetak Dokumen
            </button>
        </div>
    </div>

    <div class="digi-document-scroll-wrapper">
        @include('fuel.document_partial', ['fuelRequest' => $fuelRequest])
    </div>
</body>
</html>
