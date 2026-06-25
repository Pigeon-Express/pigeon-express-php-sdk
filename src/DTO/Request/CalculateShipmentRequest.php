<?php

namespace PigeonExpress\DTO\Request;

use PigeonExpress\Contracts\Arrayable;

/**
 * Calculate Shipment Request DTO
 *
 * Request data for calculating shipment price.
 */
class CalculateShipmentRequest implements Arrayable
{
    /**
     * @var array Packages
     */
    private $packages = array();

    /**
     * @var string Pickup type (office, address, locker)
     */
    private $pickupType;

    /**
     * @var int|null Pickup office ID
     */
    private $pickupOfficeId;

    /**
     * @var string Delivery type (office, address, locker)
     */
    private $deliveryType;

    /**
     * @var int|null Delivery office ID
     */
    private $deliveryOfficeId;

    /**
     * @var string Service type (standard, express)
     */
    private $serviceType;

    /**
     * @var array Service codes
     */
    private $serviceCodes = array();

    /**
     * @var array|null Delivery address (for delivery_type = address)
     */
    private $deliveryAddress;

    /**
     * @var array|null Pickup address (for pickup_type = address)
     */
    private $pickupAddress;

    /**
     * @var string|null Who pays (sender, receiver)
     */
    private $whoPays;

    /**
     * @var float|null COD amount
     */
    private $codAmount;

    /**
     * Create calculate shipment request
     *
     * @param string $pickupType Pickup type (office, address, locker)
     * @param string $deliveryType Delivery type (office, address, locker)
     * @param string $serviceType Service type (standard, express)
     */
    public function __construct($pickupType, $deliveryType, $serviceType)
    {
        $this->pickupType = $pickupType;
        $this->deliveryType = $deliveryType;
        $this->serviceType = $serviceType;
    }

    /**
     * Add a package
     *
     * @param PackageData|array $package
     * @return $this
     */
    public function addPackage($package)
    {
        if ($package instanceof PackageData) {
            $this->packages[] = $package;
        } elseif (is_array($package)) {
            $this->packages[] = PackageData::fromArray($package);
        }

        return $this;
    }

    /**
     * Set packages
     *
     * @param array $packages
     * @return $this
     */
    public function setPackages(array $packages)
    {
        $this->packages = array();

        foreach ($packages as $package) {
            $this->addPackage($package);
        }

        return $this;
    }

    /**
     * Get packages
     *
     * @return array
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * Set pickup office ID
     *
     * @param int $officeId
     * @return $this
     */
    public function setPickupOfficeId($officeId)
    {
        $this->pickupOfficeId = (int) $officeId;
        return $this;
    }

    /**
     * Get pickup office ID
     *
     * @return int|null
     */
    public function getPickupOfficeId()
    {
        return $this->pickupOfficeId;
    }

    /**
     * Set delivery office ID
     *
     * @param int $officeId
     * @return $this
     */
    public function setDeliveryOfficeId($officeId)
    {
        $this->deliveryOfficeId = (int) $officeId;
        return $this;
    }

    /**
     * Get delivery office ID
     *
     * @return int|null
     */
    public function getDeliveryOfficeId()
    {
        return $this->deliveryOfficeId;
    }

    /**
     * Set service codes
     *
     * @param array $codes
     * @return $this
     */
    public function setServiceCodes(array $codes)
    {
        $this->serviceCodes = $codes;
        return $this;
    }

    /**
     * Add service code
     *
     * @param string $code
     * @return $this
     */
    public function addServiceCode($code)
    {
        if (!in_array($code, $this->serviceCodes)) {
            $this->serviceCodes[] = $code;
        }

        return $this;
    }

    /**
     * Get service codes
     *
     * @return array
     */
    public function getServiceCodes()
    {
        return $this->serviceCodes;
    }

    /**
     * Set delivery address (required when delivery_type = address).
     *
     * @param array $address
     * @return $this
     */
    public function setDeliveryAddress(array $address)
    {
        $this->deliveryAddress = $address;
        return $this;
    }

    /**
     * Get delivery address
     *
     * @return array|null
     */
    public function getDeliveryAddress()
    {
        return $this->deliveryAddress;
    }

    /**
     * Set pickup address (required when pickup_type = address).
     *
     * @param array $address
     * @return $this
     */
    public function setPickupAddress(array $address)
    {
        $this->pickupAddress = $address;
        return $this;
    }

    /**
     * Get pickup address
     *
     * @return array|null
     */
    public function getPickupAddress()
    {
        return $this->pickupAddress;
    }

    /**
     * Set who pays for delivery (sender or receiver).
     *
     * @param string $whoPays
     * @return $this
     */
    public function setWhoPays($whoPays)
    {
        $this->whoPays = (string) $whoPays;
        return $this;
    }

    /**
     * Get who pays
     *
     * @return string|null
     */
    public function getWhoPays()
    {
        return $this->whoPays;
    }

    /**
     * Set COD amount
     *
     * @param float $amount
     * @return $this
     */
    public function setCodAmount($amount)
    {
        $this->codAmount = (float) $amount;
        return $this;
    }

    /**
     * Get COD amount
     *
     * @return float|null
     */
    public function getCodAmount()
    {
        return $this->codAmount;
    }

    /**
     * Get pickup type
     *
     * @return string
     */
    public function getPickupType()
    {
        return $this->pickupType;
    }

    /**
     * Get delivery type
     *
     * @return string
     */
    public function getDeliveryType()
    {
        return $this->deliveryType;
    }

    /**
     * Get service type
     *
     * @return string
     */
    public function getServiceType()
    {
        return $this->serviceType;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        $packagesArray = array();
        foreach ($this->packages as $package) {
            $packagesArray[] = $package instanceof PackageData ? $package->toArray() : $package;
        }

        $data = array(
            'packages' => $packagesArray,
            'pickup_type' => $this->pickupType,
            'delivery_type' => $this->deliveryType,
            'service_type' => $this->serviceType,
        );

        if ($this->pickupOfficeId !== null) {
            $data['pickup_office_id'] = $this->pickupOfficeId;
        }

        if ($this->deliveryOfficeId !== null) {
            $data['delivery_office_id'] = $this->deliveryOfficeId;
        }

        if (!empty($this->serviceCodes)) {
            $data['service_codes'] = $this->serviceCodes;
        }

        if ($this->deliveryAddress !== null) {
            $data['delivery_address'] = $this->deliveryAddress;
        }

        if ($this->pickupAddress !== null) {
            $data['pickup_address'] = $this->pickupAddress;
        }

        if ($this->whoPays !== null) {
            $data['who_pays'] = $this->whoPays;
        }

        if ($this->codAmount !== null) {
            $data['cod_amount'] = $this->codAmount;
        }

        return $data;
    }
}
