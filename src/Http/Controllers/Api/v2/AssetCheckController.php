<?php

namespace Helious\SeatBusaCynos\Http\Controllers\Api\v2;

use Seat\Api\Http\Traits\Filterable;
use Illuminate\Http\Resources\Json\Resource;

use GuzzleHttp\Client;
use Seat\Web\Models\User;
use Seat\Eveapi\Models\Character\CharacterInfo;
use Seat\Eveapi\Models\Assets\CharacterAsset;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Illuminate\Http\Request;

class AssetCheckController extends ApiController
{

    use Filterable;

    private $desto;

    public function __construct($desto = null)
    {
        $this->desto = $desto;
    }

    public function get_characters_in_corp()
    {
        return CorporationInfo::where('corporation_id', 2014367342)
            ->first()
            ->characters
            ->map(function ($character) {
                return [
                    'character_id' => $character->character_id,
                    'name' => $character->name,
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
        $lookingForGroup = explode(',', $request->lookingForGroup);
    
        $charactersInCorp = $this->get_characters_in_corp()->pluck('character_id')->toArray();
    
        $characters = CharacterInfo::with([
            'user',
            'assets' => function ($query) use ($lookingForGroup, $desto) {
                $query->whereHas('solar_system', function ($query) use ($desto) {
                    $query->where('name', $desto);
                })->orWhereHas('type.group', function ($query) use ($lookingForGroup) {
                    $query->whereIn('groupName', $lookingForGroup);
                });
            },
            'assets.solar_system',
            'assets.type.group'
        ])
        ->whereIn('character_id', $charactersInCorp)
        ->get()
        ->map(function ($character) {
            return [
                'character_id' => $character->character_id,
                'name' => $character->name,
                'belongs_to' => $character->user->name,
                'hasAssetsWereLookingFor' => !$character->assets->isEmpty(),
            ];
        })
        ->filter()
        ->groupBy('belongs_to');
        
        return $characters;
    }

}
