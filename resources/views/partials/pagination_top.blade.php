@php
    $total = method_exists($paginator, 'total') ? $paginator->total() : (is_countable($paginator) ? count($paginator) : 0);
    $first = method_exists($paginator, 'firstItem') ? ($paginator->firstItem() ?? 0) : ($total > 0 ? 1 : 0);
    $last = method_exists($paginator, 'lastItem') ? ($paginator->lastItem() ?? 0) : $total;
@endphp
<div style="display: flex; justify-content: space-between; align-items: center; margin: 14px 0 10px 0; flex-wrap: wrap; gap: 10px;">
    <div style="font-size: 13px; color: #475569; display: flex; align-items: center; gap: 8px;">
        <span>Tampilkan:</span>
        <select onchange="updatePerPage(this.value)" class="digi-select" style="width: auto; padding: 4px 10px; font-size: 12.5px; height: 32px;">
            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 data</option>
            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 data</option>
            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 data</option>
            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 data</option>
        </select>
        <span>per halaman</span>
    </div>
    <div style="font-size: 12.5px; color: #64748b;">
        Menampilkan <strong>{{ $first }}</strong> - <strong>{{ $last }}</strong> dari total <strong>{{ $total }}</strong> data
    </div>
</div>

