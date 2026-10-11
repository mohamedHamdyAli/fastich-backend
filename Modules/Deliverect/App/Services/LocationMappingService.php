<?php


namespace Modules\Deliverect\App\Services;

use Modules\Deliverect\App\Models\LocationMapping;

class LocationMappingService
{
    public function addMapping($businessId, $posLocation, $deliverectLocId)
    {
        // Check if the mapping already exists
        $existingMapping = LocationMapping::where('business_id', $businessId)
            ->where('pos_location', $posLocation)
            ->orWhere('deliverect_loc_id', $deliverectLocId)
            ->first();

        if ($existingMapping) {
            return [
                'data' => null,
                'message' => 'Mapping already exists.',
                'success' => false
            ];
        }

        // Create new mapping
        $mapping = LocationMapping::create([
            'business_id' => $businessId,
            'pos_location' => $posLocation,
            'deliverect_loc_id' => $deliverectLocId
        ]);

        return [
            'data' => $mapping,
            'message' => 'Mapping added successfully.',
            'success' => true
        ];
    }

    /**
     * Get a record by deliverect_loc_id.
     *
     * @param string $deliverectLocId
     * @return array
     */
    public function getByDeliverectLocationId($deliverectLocId)
    {
        $mapping = LocationMapping::where('deliverect_loc_id', $deliverectLocId)->first();

        if (!$mapping) {
            return [
                'data' => null,
                'message' => 'No mapping found for the given Deliverect Location ID.',
                'success' => false
            ];
        }

        return [
            'data' => $mapping,
            'message' => 'Mapping retrieved successfully.',
            'success' => true
        ];
    }

    /**
     * Get a record by business_id and pos_location.
     *
     * @param int $businessId
     * @param int $posLocation
     * @return array
     */
    public function getByBusinessAndPosLocation($businessId, $posLocation)
    {
        $mapping = LocationMapping::where('business_id', $businessId)
            ->where('pos_location', $posLocation)
            ->first();

        if (!$mapping) {
            return [
                'data' => null,
                'message' => 'No mapping found for the given Business ID and POS Location.',
                'success' => false
            ];
        }

        return [
            'data' => $mapping,
            'message' => 'Mapping retrieved successfully.',
            'success' => true
        ];
    }
}