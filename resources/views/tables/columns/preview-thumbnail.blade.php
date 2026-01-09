@php
    $record = $getRecord();
    $previewUrl = url("/email-templates/{$record->getKey()}/preview");
@endphp
<div class="mb-4 h-[200px] w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-md dark:border-gray-700 dark:bg-gray-900">
    <div class="relative h-[1000px] w-[1400px] origin-top-left scale-[0.14]">
        <iframe
            src="{{ $previewUrl }}"
            class="pointer-events-none absolute inset-0 h-full w-full border-0 bg-white"
            loading="lazy"
            scrolling="no"
        ></iframe>
    </div>
</div>
