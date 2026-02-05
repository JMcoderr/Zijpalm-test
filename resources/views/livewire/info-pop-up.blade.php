<x-zijpalm-div :editable="false" width="w-min-max">
    <div id="info" class="flex flex-col size-full font-semibold text-center text-sm">
        @foreach($tabs as $index => $tab)
            {{-- Only show if it's the current tab --}}
            @if($index == $currentTab)
                <div id="page-{{lcfirst($tab['title'])}}" class="flex flex-col gap-y-1">
                    <span class="text-xl font-extrabold drop-shadow-[0_2.0px_2.0px_rgba(0,0,0,0.5)]">  
                        {{$tab['title']}}
                    </span>
                    <flux:separator/>
                    <div class="text-left ps-2 text-sm text-nowrap drop-shadow-[0_1.0px_1.0px_rgba(0,0,0,0.5)]">
                        @if(is_array($tab['content']))
                            <ul>
                                @foreach($tab['content'] as $index => $content)
                                    <li>
                                        <b>{{$index+1}}.</b> 
                                        <span class="font-medium">{{$content}}</span>
                                    </li>
                                @endforeach
                            </ul>
                            @else
                                {{$tab['content']}}
                        @endif
                    </div>
                    @if(count($tabs) > 1)
                        <flux:separator variant="subtle"/>
                        <div class="grid grid-cols-3 pt-2 w-full"> 
                            @if($currentTab == count($tabs) - 1)
                                <x-zijpalm-button type="action" variant="backward" wireclick="changeTab('backward')" class="col-start-1 me-auto"/>
                            @endif
                            <span class="col-start-2 flex flex-col justify-center text-lg">{{($index + 1) . '/' . count($tabs)}}</span>
                            @if($currentTab != count($tabs) - 1)
                                <x-zijpalm-button type="action" variant="forward" wireclick="changeTab('forward')" class="col-start-3 ms-auto"/>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        @endforeach
    </div>
</x-zijpalm-div>