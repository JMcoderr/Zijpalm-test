<?php

namespace App\Livewire;

use Livewire\Component;

class InfoPopUp extends Component{
    public $tabs = [];
    public $currentTab = 0;
    public $tab = [
        'title' => '',
        'content' => [],
    ];

    // Placeholder info, should look up content in DB instead (or scrape from source, optimally)
    public function addTabs($info){
        if($info === 'whatsapp-info'){
            $addedTab = $this->tab;
            $addedTab['title'] = "WhatsApp Groep Link Verkrijgen";
            $addedTab['content'] = [
                "Open de WhatsApp website",
                "Maak een WhatsApp groep aan",
                "Open de groep en klik op het groepsonderwerp",
                "Klik op 'Uitnodigen voor groep via link'",
                "Kies voor 'Link kopiëren'",
                "Plak de gekopieërde link hieronder",
            ];
            $this->tabs[] = $addedTab;
        }
    }

    // Go back and forth in multi-tab info pop-ups
    public function changeTab($direction){
        switch($direction){
            case 'backward':
                if($this->currentTab > 0){
                    $this->currentTab--;
                }
            break;

            case 'forward':
                if($this->currentTab < (count($this->tabs) - 1)){
                    $this->currentTab++;
                }
            break;
        }
    }

    // Mount the info from the info attribute to variable, add tabs based on the info given
    public function mount($info){
        $this->info = $info;
        $this->addTabs($this->info);
    }

    public function render(){
        return view('livewire.info-pop-up');
    }
}
