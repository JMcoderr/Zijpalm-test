@use('\App\UserType')
<x-page-wrapper page="Afmelden">

    <x-zijpalm-div color="transparent" :editable="false" :title="$content->title"/>

    <x-zijpalm-div color="light" :editable="false">
        <x-settings.layout :user="auth()->user() !== $user ? $user : null">
            <x-edit-content :id="$content->id" :name="$content->name" :editables="['Titel', 'Tekst']" />
                @if ($user->isType(UserType::Medewerker))
                    {!!$content->textHTML!!}
                @elseif($user->isType(UserType::System))
                    <p>Dit account kan niet worden verwijderd omdat het een systeem account is</p>
                @else
                    {!!$content->textHTML!!}
                @endif
                @if ($errors->any())
                    <div class="text-red-500">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>                
                @endif
        </x-settings.layout>
    </x-zijpalm-div>
    {{-- If the user is not a system user or an Medewerker, they can stop their membership --}}
    @if (!$user->isType(UserType::Medewerker) && !$user->isType(UserType::System))
        <form id="user-edit" method="POST" action="{{$route}}">
            @csrf
            @method('DELETE')
            <x-zijpalm-button type="submit" form="user-edit" center="horizontal" label="Afmelden" onclick="return confirm('Weet u zeker dat u zich wilt uitschrijven voor de personeelsvereniging?\nDeze actie kan niet ongedaan worden gemaakt')" />
        </form>
    @endif
</x-page-wrapper>