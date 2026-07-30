<?php

namespace PigeonExpress\Resources;

use PigeonExpress\DTO\Response\AdditionalService;

/**
 * Additional Services Resource
 *
 * API resource for the additional services catalogue.
 */
class AdditionalServices extends AbstractResource
{
    /**
     * Get all available additional services.
     *
     * Only active, visible and global services are returned by the API.
     *
     * @return AdditionalService[]
     */
    public function all()
    {
        $response = $this->getClient()->get('additional-services');

        return $this->mapToDto($response->getData('data'), AdditionalService::class);
    }

    /**
     * Get all additional services keyed by their service code.
     *
     * @return array<string,AdditionalService>
     */
    public function allByCode()
    {
        $result = array();

        foreach ($this->all() as $service) {
            $result[$service->getCode()] = $service;
        }

        return $result;
    }
}
