<?php

namespace App\Http\Controllers;

use iio\libmergepdf\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{
    /**
     * @param Request $request
     * @return array
     */
    public function getAutocomplete(Request $request)
    {
        $result = [];

        $search = '';
        if ($request->has('search')) {
            $search = $request->get('search');
        }

        $search_results = Http::get('https://maps.googleapis.com/maps/api/place/autocomplete/json?input=' . $search . '&language=en&types=address&key=AIzaSyD8d3zy4nJ5F-sGMM-L-St5rNNPr2PyF90');

        $search_results_array = $search_results->json();

        if (!empty($search_results_array) && !empty($search_results_array['predictions'])) {
            foreach ($search_results_array['predictions'] as $search_result) {
                $result[] = $search_result['description'];
            }
        }

        return $result;
    }

    /**
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function getDirections(Request $request)
    {
        $address_to = '';
        if ($request->has('to')) {
            $address_to = $request->get('to');
        }

        $address_from = '';
        if ($request->has('from')) {
            $address_from = $request->get('from');
        }

        if (empty($address_to) || empty($address_from)) {
            throw new Exception('Both addresses should be provider');
        }

        $coordinates_to = $this->getCoordinates($address_to);
        $coordinates_from = $this->getCoordinates($address_from);

        $result = $this->getDurationAndDistance($coordinates_to, $coordinates_from);

        return $result;
    }

    /**
     * @param string $address_string
     * @return array
     * @throws Exception
     */
    private function getCoordinates(string $address_string): array
    {
        $search_results_address_to = Http::get('https://api.mapbox.com/geocoding/v5/mapbox.places/' . $address_string . '.json?limit=1&access_token=pk.eyJ1IjoiYnVyaWtoaW4iLCJhIjoiY2t4OTIxcXJjMDNjMzJxbXd5d2hmNjhpeiJ9.fqGxIyW5hwv1IOBo6o-NlQ');

        if (!empty($search_results_address_to->json()) && !empty($search_results_address_to->json()['features'])) {
            $address_to_coordinates = array_pop($search_results_address_to->json()['features'])['center'];
        } else {
            throw new Exception('Coordinates not found for address ' . $address_string);
        }

        return $address_to_coordinates;
    }


    /**
     * @param array $coordinates_to
     * @param array $coordinates_from
     * @return array
     * @throws Exception
     */
    private function getDurationAndDistance(array $coordinates_to, array $coordinates_from): array
    {
        $mapbox_url = 'https://api.mapbox.com/directions/v5/mapbox/driving/';
        $url_params = '?access_token=pk.eyJ1IjoiYnVyaWtoaW4iLCJhIjoiY2t4OTIxcXJjMDNjMzJxbXd5d2hmNjhpeiJ9.fqGxIyW5hwv1IOBo6o-NlQ';

        foreach ($coordinates_to as $coordinates_value) {
            $mapbox_url .= $coordinates_value . ',';
        }

        $mapbox_url = substr($mapbox_url, 0, -1);
        $mapbox_url .= ';';

        foreach ($coordinates_from as $coordinates_value) {
            $mapbox_url .= $coordinates_value . ',';
        }

        $mapbox_url = substr($mapbox_url, 0, -1);

        $mapbox_url .= $url_params;

        $search_results = Http::get($mapbox_url);

        if (!empty($search_results->json()) && !empty($search_results->json()['routes'])) {
            $route = array_pop($search_results->json()['routes']);

            $result = [
                'duration' => $route['duration'],
                'distance' => $route['distance']
            ];
        } else {
            throw new Exception('Routes not found');
        }

        return $result;
    }
}
