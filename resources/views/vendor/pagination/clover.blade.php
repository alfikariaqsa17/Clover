@if ($paginator->hasPages())
    <nav class="pgn" role="navigation" aria-label="Navigasi halaman">
        @if ($paginator->onFirstPage())
            <span class="btn btn-glass btn-sm off"><x-icon name="arrow-left" :size="16" /> Sebelumnya</span>
        @else
            <a class="btn btn-glass btn-sm" href="{{ $paginator->previousPageUrl() }}" rel="prev"><x-icon name="arrow-left" :size="16" /> Sebelumnya</a>
        @endif
        <span class="pgi">Halaman {{ $paginator->currentPage() }}@if(method_exists($paginator, 'lastPage')) dari {{ $paginator->lastPage() }}@endif</span>
        @if ($paginator->hasMorePages())
            <a class="btn btn-glass btn-sm" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya <x-icon name="arrow-right" :size="16" /></a>
        @else
            <span class="btn btn-glass btn-sm off">Berikutnya <x-icon name="arrow-right" :size="16" /></span>
        @endif
    </nav>
@endif
