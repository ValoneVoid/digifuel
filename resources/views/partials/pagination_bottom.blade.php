@if(method_exists($paginator, 'hasPages') && $paginator->hasPages())
    <div class="digi-pagination-bar" style="margin-top: 14px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
        <div style="font-size: 12.5px; color: #64748b;">
            Halaman <strong>{{ $paginator->currentPage() }}</strong> dari <strong>{{ $paginator->lastPage() }}</strong>
        </div>

        <div class="digi-page-btns">
            @if($paginator->onFirstPage())
                <span class="digi-page-btn" style="opacity: 0.4; cursor: not-allowed;">&lt;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="digi-page-btn" title="Halaman Sebelumnya">&lt;</a>
            @endif

            @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $pNum => $pUrl)
                <a href="{{ $pUrl }}" class="digi-page-btn {{ $pNum == $paginator->currentPage() ? 'active' : '' }}">
                    {{ $pNum }}
                </a>
            @endforeach

            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="digi-page-btn" title="Halaman Berikutnya">&gt;</a>
            @else
                <span class="digi-page-btn" style="opacity: 0.4; cursor: not-allowed;">&gt;</span>
            @endif
        </div>
    </div>
@endif

