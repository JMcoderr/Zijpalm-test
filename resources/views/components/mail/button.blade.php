@props([
    'href' => '#',
    'label' => 'Klik',
])

<p style="margin: 10px; text-align: center; padding: 10px 20px;">
    <a href="{{ $href }}" style="background-color: #054d7c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        <span>{{$slot}}</span>
        <span>{{$label}}</span>
    </a>
</p>