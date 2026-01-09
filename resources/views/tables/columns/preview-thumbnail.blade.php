<div class="aspect-[4/3] w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="relative h-full w-full">
        <iframe
            src="data:text/html;base64,{{ $getRecord()->getBase64EmailPreviewData() }}"
            class="pointer-events-none h-full w-full origin-top-left"
            style="transform: scale(0.25); width: 400%; height: 400%;"
            loading="lazy"
            sandbox
        ></iframe>
        {{-- Overlay für bessere Klickbarkeit --}}
        <div class="absolute inset-0 bg-transparent"></div>
    </div>
</div>
