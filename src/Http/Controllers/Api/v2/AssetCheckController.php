<?php

namespace Helious\SeatBusaCynos\Http\Controllers\Api\v2;

use Seat\Api\Http\Traits\Filterable;
use Illuminate\Http\Resources\Json\Resource;

use GuzzleHttp\Client;
use Seat\Eveapi\Models\Character\CharacterInfo;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Illuminate\Http\Request;

class AssetCheckController extends ApiController
{

    use Filterable;

    public function get_characters_in_corp()
    {
        return CorporationInfo::where('corporation_id', 2014367342)
            ->first()
            ->characters
            ->map(function ($character) {
                return [
                    'main_character_id' => $character->main_character_id
                ];
            });
    }

    /**
     * Gets the list of characters in corp and check their assets to see if they have anything 
     * in $lookingForGroup array which will contain group types of Captaisl, Faxs, Supers and titans
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $desto = $request->desto;
        // explode the lookingForGroup string into an array
        $lookingForGroup = explode(',', $request->lookingForGroup);

        $charactersInCorp = $this->get_characters_in_corp();

    
        $result = CharacterInfo::whereIn('character_id', $charactersInCorp->pluck('main_character_id')->toArray())
            ->get()
            ->all_characters
            ->map(function ($character) use ($charactersInCorp) {
                $assets = $character->assets;
                
                $assets = $assets->filter(function ($asset) use ($lookingForGroup) {
                    if($asset->solar_system->name == $desto){
                        if(in_array($asset->type->group->groupName, $lookingForGroup)){
                            return [
                                'character_name' => $character->name,
                                'asset_name' => $asset->type->typeName,
                            ];
                        }
                    }
                });
            })
            ->filter(); // Remove null values from the collection

        return $result;
    }

}
