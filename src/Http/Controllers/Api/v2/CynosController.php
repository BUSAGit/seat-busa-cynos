<?php

namespace Helious\SeatBeacons\Http\Controllers\Api\v2;

class CynosController extends Controller
{
    /**
     * Gets the list of cyno characters in corp and returns their location
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()
            ->json('TEST', true);
    }


}
