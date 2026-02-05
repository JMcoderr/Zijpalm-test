@props([
    'title' => null,
    'email' => null,
    'variables' => [],
    'href' => null,
    'icons' => [],
    'buttons' => ['button' => '#'],
])

@php
    $buttonIcons = [
        'button' => 'arrow-right',
        'link' => 'arrow-right',
        'download' => 'arrow-down-tray',
        'edit' => 'pencil-square',
        'add' => 'plus',
        'delete' => 'trash',
        'reinstate' => 'user-plus'
    ];
    // Throw an exception if the button type is not valid
    foreach ($buttons as $type => $buttonHref) {
        if (!in_array($type, ['button', 'link', 'download', 'edit', 'add', 'delete', 'reinstate'])) {
            throw new Exception("Invalid button type");
        }
    }
@endphp

<div class="flex flex-col sm:flex-row items-center justify-between mb-2 gap-x-5 w-full">
    <a href="{{$href}}" class="flex max-w-full sm:w-[300px] flex-1 overflow-hidden text-center sm:text-left">
        <h3 class="font-semibold text-gray-800 truncate">{{ $title }}</h3>
        @isset($email)
            <a class="align-middle" href="mailto:{{$email}}">{{ $email }}</a>
        @endisset
        @if(!empty($variables))
            <div class="flex justify-end grow items-center gap-4">
                @foreach($variables as $var)
                    <span class="{{ $var['class'] }}">{{ $var['text'] }}</span>
                @endforeach
            </div>
        @endisset
        @foreach ($icons as $icon)
            @if ($icon == 'star')
                <flux:tooltip content="Beheerder">
                    <flux:icon.star variant="micro" class="text-amber-400" />
                </flux:tooltip>
            @endif
        @endforeach
    </a>
    <div class="flex flex-shrink-0 space-x-2">
        @foreach ($buttons as $type => $buttonHref)
            @php
                $icon = $buttonIcons[$type] ?? 'arrow-right';
            @endphp
            @if ($type == 'delete')
                <form action="{{$buttonHref}}" method="POST" onsubmit="return confirm('Weet je zeker dat je dit wilt verwijderen?')">
                    @method('DELETE')
                    @csrf
                    <flux:tooltip content="{{ ucfirst($type) }}">
                        <flux:button type="submit" variant="primary" :icon="$icon" class="cursor-pointer bg-linear-to-t from-zijpalm-700 to-zijpalm-500" square="true"/>
                    </flux:tooltip>
                </form>
            @elseif($type == 'reinstate')
                <form action="{{$buttonHref}}" method="POST" onsubmit="return confirm('Weet je zeker dat je dit wilt hervatten?')">
                    @csrf
                    <flux:tooltip content="{{ ucfirst($type) }}">
                        <flux:button type="submit" variant="primary" :icon="$icon" class="bg-linear-to-t from-zijpalm-700 to-zijpalm-500" square="true"/>
                    </flux:tooltip>
                </form>
            @elseif($type == 'download')
                <flux:tooltip content="{{ ucfirst($type) }} deelnemerslijst">
                    <flux:button :href="$buttonHref" variant="primary" :icon="$icon" class="bg-linear-to-t from-zijpalm-700 to-zijpalm-500" square="true"/>
                </flux:tooltip>
            @else
                <flux:tooltip content="{{ ucfirst($type) }}">
                    <flux:button :href="$buttonHref" variant="primary" :icon="$icon" class="bg-linear-to-t from-zijpalm-700 to-zijpalm-500" square="true"/>
                </flux:tooltip>
            @endif
        @endforeach
    </div>
</div>
