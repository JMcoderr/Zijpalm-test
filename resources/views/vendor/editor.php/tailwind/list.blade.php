@if ($data('style') === 'ordered')
    <ol class="pl-8 mb-4 list-decimal list-inside text-left">
        @foreach ($data('items', []) as $item)
            <li class="mb-1 [&>a]:text-blue-600 [&>a]:underline hover:[&>a]:text-blue-400 [&>code]:text-red-500 [&>code]:bg-red-100 [&>code]:px-1 [&>code]:rounded-md [&>code]:whitespace-nowrap [&>code]:font-medium [&>mark]:bg-yellow-400">{!! $item !!}</li>
        @endforeach
    </ol>
@else
    <ul class="pl-8 mb-4 list-disc list-inside text-left">
        @foreach ($data('items', []) as $item)
            <li class="mb-1 [&>a]:text-blue-600 [&>a]:underline hover:[&>a]:text-blue-400 [&>code]:text-red-500 [&>code]:bg-red-100 [&>code]:px-1 [&>code]:rounded-md [&>code]:whitespace-nowrap [&>code]:font-medium [&>mark]:bg-yellow-400">{!! $item !!}</li>
        @endforeach
    </ul>
@endif
