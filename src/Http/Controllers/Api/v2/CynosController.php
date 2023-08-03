<?php

namespace Helious\SeatBusaCynos\Http\Controllers\Api\v2;

use Seat\Api\Http\Traits\Filterable;
use Illuminate\Http\Resources\Json\Resource;

use GuzzleHttp\Client;
use Seat\Eveapi\Models\Character\CharacterInfo;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Illuminate\Http\Request;

class CynosController extends ApiController
{

    use Filterable;

    private $desto;

    public function __construct($desto = null)
    {
        $this->desto = $desto;
    }

    public function system_to_id($system){

        // Create a new GuzzleHTTP client instance
        $client = new Client();

        // make sure $system is a string
        $system = (string)$system;
        
        // Define the request data
        $data = [
            'json' => [$system],
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Language' => 'en',
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache',
            ]
        ];
        
        // Make the POST request to the API
        $response = $client->post('https://esi.evetech.net/latest/universe/ids/?datasource=tranquility&language=en', $data);
        
        // Get the response body as a string
        $responseBody = $response->getBody()->getContents();
        
        // Decode the JSON response to an array
        $result = json_decode($responseBody, true);

        // if there is no result return null or system is not set
        if(!isset($result['systems'][0]['id'])) return null;

        return $result['systems'][0]['id'];
    }

    /**
     * Gets the number of jumps between two systems
     *
     * @return \Illuminate\Http\Response
     */
    public function jumps_between_systems($start, $end){
        $start = $this->system_to_id($start);
        // if start is null return 404 with error message
        if($start == null) return null;
        
        $client = new Client();
        $request = $client->get('https://esi.evetech.net/latest/route/'.$start.'/'.$end.'/', [
            'datasource' => 'tranquility',
            'flag' => 'shortest',
        ]);
        $response = $request->getBody();

        $result = json_decode($response, true);

        // count the number of jumps
        $jumps = count($result) - 1;

        return $jumps;
    }

    public function get_characters_in_corp()
    {
        return CorporationInfo::where('corporation_id', 2014367342)
            ->first()
            ->characters
            ->map(function ($character) {
                return [
                    'character_id' => $character->character_id,
                    'character_name' => $character->name,
                    'belongs_to' => $character->user->name,
                ];
            });
    }

    /**
     * Gets the list of cyno characters in corp and returns their location
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $desto = $request->desto;
        $maxJumps = $request->maxJumps;

        // if desto isnt set return 404 with error message
        if($maxJumps == null) $maxJumps = 40;

        $charactersInCorp = $this->get_characters_in_corp();

        $cynosWereLookingFor = [
            'Anathema' => 'CovOps',
            'Buzzard' => 'CovOps', 
            'Cheetah' => 'CovOps', 
            'Helios' => 'CovOps', 
            'Pacifier' => 'CovOps', 
            'Hound' => 'CovOps', 
            'Manticore' => 'CovOps', 
            'Nemesis' => 'CovOps', 
            'Purifier' => 'CovOps', 
            'Arazu' => 'CovOps', 
            'Enforcer' => 'CovOps', 
            'Falcon' => 'CovOps', 
            'Pilgrim' => 'CovOps', 
            'Rapier' => 'CovOps', 
            'Venture' => 'Indy'
        ];

    
        $result = CharacterInfo::whereIn('character_id', $charactersInCorp->pluck('character_id')->toArray())
            ->get()
            ->map(function ($character) use ($charactersInCorp, $cynosWereLookingFor) {
                if(!isset($character->ship) && !isset($character->ship->type)) return null;
                $shipName = $character->ship->type->typeName;
                if (!array_key_exists($shipName, $cynosWereLookingFor)) return null;

                return [
                    'character_id' => $character->character_id,
                    'character_name' => $character->name,
                    'belongs_to' => $charactersInCorp->where('character_id', $character->character_id)->first()['belongs_to'],
                    'location' => $character->location->solar_system->name,
                    'location_id' => $character->location->solar_system->system_id,
                    'ship' => $shipName,
                ];
            })
            ->filter(); // Remove null values from the collection


        // Calculate jumps for each character
        $result = $result->map(function ($character) use ($desto) {
            $character['jumps'] = $this->jumps_between_systems($desto, $character['location_id']);
            return $character;
        });

        // Filter out characters with null jumps
        $result = $result->filter(function ($character) {
            return $character['jumps'] !== null;
        });

        // If no characters are left, return the error response
        if ($result->isEmpty()) {
            return response()->json(['error' => 'Start system not found'], 404);
        }

        // Optionally, you can sort the remaining characters by the 'jumps' value
        $result = $result->sortBy('jumps')->where('jumps', '<=', $maxJumps)->values();

        // if $result is empty return error with message
        if($result->isEmpty()) return response()->json(['error' => 'No cynos found within '.$maxJumps.' jumps of '.$desto], 404);

        // Finally, return the filtered and sorted result
        return $result->values()->all();
    }

}
