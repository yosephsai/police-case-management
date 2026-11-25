<div class="print-bar no-print">
    <span class="muted">Print or save this document as a PDF.</span>
    <div class="page-actions">
        <button type="button" class="btn btn--primary btn--sm" onclick="window.print()">
            @include('partials.icon', ['name' => 'print'])
            Print / Save PDF
        </button>
        <a href="{{ url()->previous() }}" class="btn btn--outline btn--sm">Back</a>
    </div>
</div>
