<?php

namespace PigeonExpress\DTO\Request;

use PigeonExpress\Contracts\Arrayable;

/**
 * Create Shipment Request DTO
 *
 * Request data for creating a new shipment.
 */
class CreateShipmentRequest implements Arrayable
{
    // Required fields
    private $receiverName;
    private $receiverPhone;
    private $pickupType;
    private $deliveryType;
    private $packages = array();
    private $serviceType;
    private $whoPays;

    // Optional fields
    private $receiverEmail;
    private $pickupOfficeId;
    private $deliveryOfficeId;
    private $senderAddress;
    private $deliveryAddress;
    private $packageType;
    private $contentCategoryId;
    private $codAmount;
    private $note;
    private $smsNotification = false;
    private $specialPackaging = false;
    private $posPayment = false;
    private $returnAtMyExpense = false;
    private $requiresSignature = false;
    private $shipmentReview = false;
    private $shipmentTestBeforePayment = false;

    /**
     * Create shipment request
     *
     * @param string $receiverName Receiver name
     * @param string $receiverPhone Receiver phone
     * @param string $pickupType Pickup type (office, address, locker)
     * @param string $deliveryType Delivery type (office, address, locker)
     * @param string $serviceType Service type (standard, express)
     * @param string $whoPays Who pays (sender, receiver)
     */
    public function __construct($receiverName, $receiverPhone, $pickupType, $deliveryType, $serviceType, $whoPays)
    {
        $this->receiverName = $receiverName;
        $this->receiverPhone = $receiverPhone;
        $this->pickupType = $pickupType;
        $this->deliveryType = $deliveryType;
        $this->serviceType = $serviceType;
        $this->whoPays = $whoPays;
    }

    /**
     * Set receiver email
     *
     * @param string $email
     * @return $this
     */
    public function setReceiverEmail($email)
    {
        $this->receiverEmail = $email;
        return $this;
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
     * Set sender address
     *
     * @param AddressData|array $address
     * @return $this
     */
    public function setSenderAddress($address)
    {
        if ($address instanceof AddressData) {
            $this->senderAddress = $address;
        } elseif (is_array($address)) {
            $this->senderAddress = AddressData::fromArray($address);
        }

        return $this;
    }

    /**
     * Set delivery address
     *
     * @param AddressData|array $address
     * @return $this
     */
    public function setDeliveryAddress($address)
    {
        if ($address instanceof AddressData) {
            $this->deliveryAddress = $address;
        } elseif (is_array($address)) {
            $this->deliveryAddress = AddressData::fromArray($address);
        }

        return $this;
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
     * Set package type
     *
     * @param string $type (package, pallet, document, envelope)
     * @return $this
     */
    public function setPackageType($type)
    {
        $this->packageType = $type;
        return $this;
    }

    /**
     * Set content category ID
     *
     * @param int $id
     * @return $this
     */
    public function setContentCategoryId($id)
    {
        $this->contentCategoryId = (int) $id;
        return $this;
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
     * Set note
     *
     * @param string $note
     * @return $this
     */
    public function setNote($note)
    {
        $this->note = $note;
        return $this;
    }

    /**
     * Enable SMS notification
     *
     * @param bool $enable
     * @return $this
     */
    public function enableSmsNotification($enable = true)
    {
        $this->smsNotification = (bool) $enable;
        return $this;
    }

    /**
     * Enable special packaging
     *
     * @param bool $enable
     * @return $this
     */
    public function enableSpecialPackaging($enable = true)
    {
        $this->specialPackaging = (bool) $enable;
        return $this;
    }

    /**
     * Enable POS payment
     *
     * @param bool $enable
     * @return $this
     */
    public function enablePosPayment($enable = true)
    {
        $this->posPayment = (bool) $enable;
        return $this;
    }

    /**
     * Enable return at sender's expense
     *
     * @param bool $enable
     * @return $this
     */
    public function enableReturnAtMyExpense($enable = true)
    {
        $this->returnAtMyExpense = (bool) $enable;
        return $this;
    }

    /**
     * Enable signature requirement
     *
     * @param bool $enable
     * @return $this
     */
    public function enableRequiresSignature($enable = true)
    {
        $this->requiresSignature = (bool) $enable;
        return $this;
    }

    /**
     * Enable shipment review
     *
     * @param bool $enable
     * @return $this
     */
    public function enableShipmentReview($enable = true)
    {
        $this->shipmentReview = (bool) $enable;
        return $this;
    }

    /**
     * Enable test before payment
     *
     * @param bool $enable
     * @return $this
     */
    public function enableShipmentTestBeforePayment($enable = true)
    {
        $this->shipmentTestBeforePayment = (bool) $enable;
        return $this;
    }

    /**
     * Get receiver name
     *
     * @return string
     */
    public function getReceiverName()
    {
        return $this->receiverName;
    }

    /**
     * Get receiver phone
     *
     * @return string
     */
    public function getReceiverPhone()
    {
        return $this->receiverPhone;
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
            'receiver_name' => $this->receiverName,
            'receiver_phone' => $this->receiverPhone,
            'pickup_type' => $this->pickupType,
            'delivery_type' => $this->deliveryType,
            'packages' => $packagesArray,
            'service_type' => $this->serviceType,
            'who_pays' => $this->whoPays,
        );

        // Optional fields
        if ($this->receiverEmail !== null) {
            $data['receiver_email'] = $this->receiverEmail;
        }

        if ($this->pickupOfficeId !== null) {
            $data['pickup_office_id'] = $this->pickupOfficeId;
        }

        if ($this->deliveryOfficeId !== null) {
            $data['delivery_office_id'] = $this->deliveryOfficeId;
        }

        if ($this->senderAddress !== null) {
            $data['sender_address'] = $this->senderAddress instanceof AddressData
                ? $this->senderAddress->toArray()
                : $this->senderAddress;
        }

        if ($this->deliveryAddress !== null) {
            $data['delivery_address'] = $this->deliveryAddress instanceof AddressData
                ? $this->deliveryAddress->toArray()
                : $this->deliveryAddress;
        }

        if ($this->packageType !== null) {
            $data['package_type'] = $this->packageType;
        }

        if ($this->contentCategoryId !== null) {
            $data['content_category_id'] = $this->contentCategoryId;
        }

        if ($this->codAmount !== null) {
            $data['cod_amount'] = $this->codAmount;
        }

        if ($this->note !== null) {
            $data['note'] = $this->note;
        }

        // Boolean service options
        if ($this->smsNotification) {
            $data['sms_notification'] = true;
        }

        if ($this->specialPackaging) {
            $data['special_packaging'] = true;
        }

        if ($this->posPayment) {
            $data['pos_payment'] = true;
        }

        if ($this->returnAtMyExpense) {
            $data['return_at_my_expense'] = true;
        }

        if ($this->requiresSignature) {
            $data['requires_signature'] = true;
        }

        if ($this->shipmentReview) {
            $data['shipment_review'] = true;
        }

        if ($this->shipmentTestBeforePayment) {
            $data['shipment_test_before_payment'] = true;
        }

        return $data;
    }

    /**
     * Validate the request
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validate()
    {
        if (empty($this->receiverName)) {
            throw new \InvalidArgumentException('Receiver name is required');
        }

        if (empty($this->receiverPhone)) {
            throw new \InvalidArgumentException('Receiver phone is required');
        }

        if (empty($this->packages)) {
            throw new \InvalidArgumentException('At least one package is required');
        }

        // Validate pickup type requirements
        if (in_array($this->pickupType, array('office', 'locker')) && $this->pickupOfficeId === null) {
            throw new \InvalidArgumentException('Pickup office ID is required for office/locker pickup');
        }

        if ($this->pickupType === 'address' && $this->senderAddress === null) {
            throw new \InvalidArgumentException('Sender address is required for address pickup');
        }

        // Validate delivery type requirements
        if (in_array($this->deliveryType, array('office', 'locker')) && $this->deliveryOfficeId === null) {
            throw new \InvalidArgumentException('Delivery office ID is required for office/locker delivery');
        }

        if ($this->deliveryType === 'address' && $this->deliveryAddress === null) {
            throw new \InvalidArgumentException('Delivery address is required for address delivery');
        }

        // Validate locker delivery requirements
        if ($this->deliveryType === 'locker' && !$this->smsNotification) {
            throw new \InvalidArgumentException('SMS notification is required for locker delivery');
        }

        // Validate mutually exclusive options
        if ($this->shipmentReview && $this->shipmentTestBeforePayment) {
            throw new \InvalidArgumentException('Shipment review and test before payment are mutually exclusive');
        }

        // Validate locker restrictions
        if ($this->deliveryType === 'locker' && ($this->shipmentReview || $this->shipmentTestBeforePayment)) {
            throw new \InvalidArgumentException('Shipment review and test before payment are not available for locker delivery');
        }

        return true;
    }
}
