<?php

namespace PigeonExpress\DTO\Response;

use PigeonExpress\Contracts\Arrayable;

/**
 * Additional Service DTO
 *
 * Definition of an additional service as returned by GET /additional-services.
 *
 * Note: the endpoint returns service definitions only. It does NOT return
 * prices. The price of any additional service is resolved by sending the
 * service in `service_codes` to POST /shipments/calculate and reading
 * `total_service_fees` from the response.
 */
class AdditionalService implements Arrayable
{
    const INPUT_TYPE_CHECKBOX = 'checkbox';
    const INPUT_TYPE_TEXT = 'text';
    const INPUT_TYPE_SELECT = 'select';

    /**
     * @var string Code used as the key inside the service_codes object
     */
    private $code;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string|null One of checkbox, text, select
     */
    private $inputType;

    /**
     * @var string|null Services sharing an option group are mutually exclusive
     */
    private $optionGroup;

    /**
     * Get service code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get service name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get service description
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get input type (checkbox, text, select)
     *
     * @return string|null
     */
    public function getInputType()
    {
        return $this->inputType;
    }

    /**
     * Get option group name
     *
     * @return string|null
     */
    public function getOptionGroup()
    {
        return $this->optionGroup;
    }

    /**
     * Whether the service carries a value (text input) rather than a flag
     *
     * @return bool
     */
    public function expectsValue()
    {
        return $this->inputType === self::INPUT_TYPE_TEXT;
    }

    /**
     * Whether the service is mutually exclusive with others in its group
     *
     * @return bool
     */
    public function isExclusive()
    {
        return $this->optionGroup !== null && $this->optionGroup !== '';
    }

    /**
     * Create from API response data
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $service = new self();

        $service->code = isset($data['code']) ? (string) $data['code'] : '';
        $service->name = isset($data['name']) ? (string) $data['name'] : '';
        $service->description = isset($data['description'])
            ? (string) $data['description']
            : null;
        $service->inputType = isset($data['input_type'])
            ? (string) $data['input_type']
            : null;
        $service->optionGroup = isset($data['option_group'])
            ? (string) $data['option_group']
            : null;

        return $service;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'input_type' => $this->inputType,
            'option_group' => $this->optionGroup,
        );
    }
}
