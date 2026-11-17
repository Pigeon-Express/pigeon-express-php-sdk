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

    // Reverse shipments: created by the receiver (this account)
    private $isReverse = false;
    private $senderName;
    private $senderPhone;
    private $senderEmail;
    private $senderCompany = array();

    // Saved addresses (GET /addresses) instead of a full address
    private $senderAddressId;
    private $deliveryAddressId;

    // Return of a refused shipment and of return documents
    private $returnOfficeId;
    private $returnAddressId;
    private $returnDocumentsOfficeId;
    private $returnDocumentsAddressId;

    private $externalReference;
    private $hideContentOnLabel = false;
    private $labelFormat;
    private $payWithPos = false;

    /** @var array Additional services, code => true or a number */
    private $serviceCodes = array();

    /** @var array[] */
    private $inventoryItems = array();

    /** @var array Fields with no typed setter, sent as given */
    private $extra = array();

    /** The longest external_reference the API accepts. */
    const EXTERNAL_REFERENCE_MAX = 100;

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
     * Mark the shipment as reverse: created by its receiver (this account)
     * instead of by the sender. Must be enabled for the account by Pigeon
     * Express. The sender is then given with setSender(), and the receiver
     * fields default to the account's profile when left empty.
     *
     * @param bool $enable
     * @return $this
     */
    public function enableReverse($enable = true)
    {
        $this->isReverse = (bool) $enable;
        return $this;
    }

    /**
     * @return bool
     */
    public function isReverse()
    {
        return $this->isReverse;
    }

    /**
     * Set the sender of a reverse shipment. Name and phone are required then.
     *
     * @param string $name
     * @param string $phone
     * @param string|null $email
     * @return $this
     */
    public function setSender($name, $phone, $email = null)
    {
        $this->senderName = $name;
        $this->senderPhone = $phone;
        $this->senderEmail = $email;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setSenderName($name)
    {
        $this->senderName = $name;
        return $this;
    }

    /**
     * @param string $phone
     * @return $this
     */
    public function setSenderPhone($phone)
    {
        $this->senderPhone = $phone;
        return $this;
    }

    /**
     * @param string|null $email
     * @return $this
     */
    public function setSenderEmail($email)
    {
        $this->senderEmail = $email;
        return $this;
    }

    /**
     * Set the sender's company on a reverse shipment. The API requires name,
     * VAT, MOL and address together; the VAT registration number is optional.
     *
     * @param string $name
     * @param string $vat VAT / Bulstat (EIK)
     * @param string $mol Responsible person
     * @param string $address
     * @param string|null $ddsNumber VAT registration number
     * @return $this
     */
    public function setSenderCompany($name, $vat, $mol, $address, $ddsNumber = null)
    {
        $this->senderCompany = array(
            'sender_company_name' => $name,
            'sender_company_vat' => $vat,
            'sender_company_mol' => $mol,
            'sender_company_address' => $address,
        );
        if ($ddsNumber !== null && $ddsNumber !== '') {
            $this->senderCompany['sender_company_dds_number'] = $ddsNumber;
        }
        return $this;
    }

    /**
     * Pick up from one of the account's saved addresses (GET /addresses)
     * instead of a full sender address. Only with pickup_type address on a
     * normal shipment.
     *
     * @param int $addressId
     * @return $this
     */
    public function setSenderAddressId($addressId)
    {
        $this->senderAddressId = (int) $addressId;
        return $this;
    }

    /**
     * Deliver to one of the account's saved addresses. Only on a reverse
     * shipment with delivery_type address.
     *
     * @param int $addressId
     * @return $this
     */
    public function setDeliveryAddressId($addressId)
    {
        $this->deliveryAddressId = (int) $addressId;
        return $this;
    }

    /**
     * Office a refused shipment is returned to. Not together with a return
     * address id.
     *
     * @param int $officeId
     * @return $this
     */
    public function setReturnOfficeId($officeId)
    {
        $this->returnOfficeId = (int) $officeId;
        return $this;
    }

    /**
     * Saved address a refused shipment is returned to. Not together with a
     * return office id.
     *
     * @param int $addressId
     * @return $this
     */
    public function setReturnAddressId($addressId)
    {
        $this->returnAddressId = (int) $addressId;
        return $this;
    }

    /**
     * Office the return documents are sent to. Only accepted with a service
     * that generates return documents, otherwise the API answers 422.
     *
     * @param int $officeId
     * @return $this
     */
    public function setReturnDocumentsOfficeId($officeId)
    {
        $this->returnDocumentsOfficeId = (int) $officeId;
        return $this;
    }

    /**
     * Saved address the return documents are sent to. Same condition as
     * setReturnDocumentsOfficeId().
     *
     * @param int $addressId
     * @return $this
     */
    public function setReturnDocumentsAddressId($addressId)
    {
        $this->returnDocumentsAddressId = (int) $addressId;
        return $this;
    }

    /**
     * The caller's own reference, for example an order number. At most 100
     * characters; validate() refuses a longer one rather than cutting it.
     *
     * @param string $reference
     * @return $this
     */
    public function setExternalReference($reference)
    {
        $this->externalReference = (string) $reference;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getExternalReference()
    {
        return $this->externalReference;
    }

    /**
     * Print the label without the shipment content line.
     *
     * @param bool $enable
     * @return $this
     */
    public function enableHideContentOnLabel($enable = true)
    {
        $this->hideContentOnLabel = (bool) $enable;
        return $this;
    }

    /**
     * Label format generated with the shipment: default, pdf150 or zpl300.
     *
     * @param string $format
     * @return $this
     */
    public function setLabelFormat($format)
    {
        $this->labelFormat = $format;
        return $this;
    }

    /**
     * Pay at the door by card (POS terminal). Sent as the top-level
     * `pay_with_pos` flag, as the WooCommerce integration does.
     *
     * @param bool $enable
     * @return $this
     */
    public function enablePayWithPos($enable = true)
    {
        $this->payWithPos = (bool) $enable;
        return $this;
    }

    /**
     * Set the additional services, keyed by service code (GET
     * /additional-services), each `true` or a number, for example
     * `['sms_notification_receiver' => true, 'cod_amount' => 50.0]`.
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
     * Add or replace one additional service.
     *
     * @param string $code
     * @param bool|int|float $value
     * @return $this
     */
    public function setServiceCode($code, $value = true)
    {
        $this->serviceCodes[(string) $code] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getServiceCodes()
    {
        return $this->serviceCodes;
    }

    /**
     * Set the items in the shipment, each with at least `description`, and
     * optionally `quantity`, `unit_price`, `unit_weight`.
     *
     * @param array[] $items
     * @return $this
     */
    public function setInventoryItems(array $items)
    {
        $this->inventoryItems = array_values($items);
        return $this;
    }

    /**
     * Add one item to the shipment.
     *
     * @param string $description
     * @param int $quantity
     * @param float|null $unitPrice
     * @param float|null $unitWeight
     * @return $this
     */
    public function addInventoryItem($description, $quantity = 1, $unitPrice = null, $unitWeight = null)
    {
        $item = array('description' => (string) $description, 'quantity' => (int) $quantity);
        if ($unitPrice !== null) {
            $item['unit_price'] = (float) $unitPrice;
        }
        if ($unitWeight !== null) {
            $item['unit_weight'] = (float) $unitWeight;
        }
        $this->inventoryItems[] = $item;
        return $this;
    }

    /**
     * @return array[]
     */
    public function getInventoryItems()
    {
        return $this->inventoryItems;
    }

    /**
     * Set a field that has no typed setter, for example
     * `allow_partial_acceptance` or the receiver company fields. It is sent as
     * given, after the typed fields, so it wins over one of the same name.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->extra[(string) $key] = $value;
        return $this;
    }

    /**
     * Get the fields set with set().
     *
     * @return array
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * Build a request from a create body, as the API names its fields.
     *
     * Fields with a typed setter go through it, so validate() sees them.
     * Every other field is kept with set() and sent unchanged: nothing in the
     * array is dropped.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $get = function ($key, $default = null) use ($data) {
            return array_key_exists($key, $data) ? $data[$key] : $default;
        };

        $request = new self(
            $get('receiver_name'),
            $get('receiver_phone'),
            $get('pickup_type'),
            $get('delivery_type'),
            $get('service_type'),
            $get('who_pays')
        );

        $handled = array(
            'receiver_name', 'receiver_phone', 'pickup_type', 'delivery_type', 'service_type', 'who_pays',
        );

        $typed = array(
            'receiver_email' => 'setReceiverEmail',
            'pickup_office_id' => 'setPickupOfficeId',
            'delivery_office_id' => 'setDeliveryOfficeId',
            'sender_address' => 'setSenderAddress',
            'pickup_address' => 'setSenderAddress',
            'delivery_address' => 'setDeliveryAddress',
            'packages' => 'setPackages',
            'sender_address_id' => 'setSenderAddressId',
            'delivery_address_id' => 'setDeliveryAddressId',
            'return_office_id' => 'setReturnOfficeId',
            'return_address_id' => 'setReturnAddressId',
            'return_documents_office_id' => 'setReturnDocumentsOfficeId',
            'return_documents_address_id' => 'setReturnDocumentsAddressId',
            'external_reference' => 'setExternalReference',
            'label_format' => 'setLabelFormat',
            'note' => 'setNote',
            'sender_name' => 'setSenderName',
            'sender_phone' => 'setSenderPhone',
            'sender_email' => 'setSenderEmail',
            'service_codes' => 'setServiceCodes',
            'inventory_items' => 'setInventoryItems',
        );

        $flags = array(
            'is_reverse' => 'enableReverse',
            'hide_content_on_label' => 'enableHideContentOnLabel',
            'return_at_my_expense' => 'enableReturnAtMyExpense',
            'pay_with_pos' => 'enablePayWithPos',
            'sms_notification' => 'enableSmsNotification',
            'special_packaging' => 'enableSpecialPackaging',
            'pos_payment' => 'enablePosPayment',
            'requires_signature' => 'enableRequiresSignature',
            'shipment_review' => 'enableShipmentReview',
            'shipment_test_before_payment' => 'enableShipmentTestBeforePayment',
        );

        foreach ($data as $key => $value) {
            if (in_array($key, $handled, true)) {
                continue;
            }

            if (isset($typed[$key]) && $value !== null) {
                $setter = $typed[$key];
                $isList = in_array($key, array('packages', 'service_codes', 'inventory_items'), true);
                $isAddress = in_array($key, array('sender_address', 'pickup_address', 'delivery_address'), true);
                if (($isList || $isAddress) && !is_array($value) && !($value instanceof AddressData)) {
                    $request->set($key, $value);
                    continue;
                }
                $request->$setter($value);
                continue;
            }

            if (isset($flags[$key]) && is_bool($value)) {
                $setter = $flags[$key];
                $request->$setter($value);
                continue;
            }

            $request->set($key, $value);
        }

        return $request;
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

        // On a reverse shipment the receiver is this account, and a receiver
        // left out defaults to its profile. An empty value would be refused.
        if ($this->isReverse) {
            foreach (array('receiver_name', 'receiver_phone') as $key) {
                if ($data[$key] === null || $data[$key] === '') {
                    unset($data[$key]);
                }
            }
        }

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

        if ($this->payWithPos) {
            $data['pay_with_pos'] = true;
        }

        if ($this->isReverse) {
            $data['is_reverse'] = true;
        }

        $nullable = array(
            'sender_name' => $this->senderName,
            'sender_phone' => $this->senderPhone,
            'sender_email' => $this->senderEmail,
            'sender_address_id' => $this->senderAddressId,
            'delivery_address_id' => $this->deliveryAddressId,
            'return_office_id' => $this->returnOfficeId,
            'return_address_id' => $this->returnAddressId,
            'return_documents_office_id' => $this->returnDocumentsOfficeId,
            'return_documents_address_id' => $this->returnDocumentsAddressId,
            'external_reference' => $this->externalReference,
            'label_format' => $this->labelFormat,
        );
        foreach ($nullable as $key => $value) {
            if ($value !== null && $value !== '') {
                $data[$key] = $value;
            }
        }

        foreach ($this->senderCompany as $key => $value) {
            $data[$key] = $value;
        }

        if ($this->hideContentOnLabel) {
            $data['hide_content_on_label'] = true;
        }

        // An object on the wire, never a list: only sent when it has keys.
        if (!empty($this->serviceCodes)) {
            $data['service_codes'] = $this->serviceCodes;
        }

        if (!empty($this->inventoryItems)) {
            $data['inventory_items'] = $this->inventoryItems;
        }

        foreach ($this->extra as $key => $value) {
            $data[$key] = $value instanceof Arrayable ? $value->toArray() : $value;
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
        // A reverse shipment's receiver is this account and defaults to its
        // profile; its sender has to be named instead.
        if (!$this->isReverse) {
            if (empty($this->receiverName)) {
                throw new \InvalidArgumentException('Receiver name is required');
            }

            if (empty($this->receiverPhone)) {
                throw new \InvalidArgumentException('Receiver phone is required');
            }
        } else {
            if (empty($this->senderName)) {
                throw new \InvalidArgumentException('Sender name is required for a reverse shipment');
            }

            if (empty($this->senderPhone)) {
                throw new \InvalidArgumentException('Sender phone is required for a reverse shipment');
            }

            if ($this->senderAddressId !== null) {
                throw new \InvalidArgumentException('A saved sender address cannot be used on a reverse shipment');
            }
        }

        if (!$this->isReverse && $this->deliveryAddressId !== null) {
            throw new \InvalidArgumentException('A saved delivery address can only be used on a reverse shipment');
        }

        if ($this->senderAddress !== null && $this->senderAddressId !== null) {
            throw new \InvalidArgumentException('Send either a sender address or a saved sender address id, not both');
        }

        if ($this->returnOfficeId !== null && $this->returnAddressId !== null) {
            throw new \InvalidArgumentException('Send either a return office or a return address, not both');
        }

        if ($this->returnDocumentsOfficeId !== null && $this->returnDocumentsAddressId !== null) {
            throw new \InvalidArgumentException('Send either a return documents office or a return documents address, not both');
        }

        if ($this->externalReference !== null) {
            $length = function_exists('mb_strlen')
                ? mb_strlen($this->externalReference, 'UTF-8')
                : strlen($this->externalReference);
            if ($length > self::EXTERNAL_REFERENCE_MAX) {
                throw new \InvalidArgumentException(sprintf(
                    'External reference must be at most %d characters',
                    self::EXTERNAL_REFERENCE_MAX
                ));
            }
        }

        if (empty($this->packages)) {
            throw new \InvalidArgumentException('At least one package is required');
        }

        // Validate pickup type requirements
        if (in_array($this->pickupType, array('office', 'locker')) && $this->pickupOfficeId === null) {
            throw new \InvalidArgumentException('Pickup office ID is required for office/locker pickup');
        }

        if ($this->pickupType === 'address' && $this->senderAddress === null && $this->senderAddressId === null) {
            throw new \InvalidArgumentException('Sender address is required for address pickup');
        }

        // Validate delivery type requirements
        if (in_array($this->deliveryType, array('office', 'locker')) && $this->deliveryOfficeId === null) {
            throw new \InvalidArgumentException('Delivery office ID is required for office/locker delivery');
        }

        // A reverse shipment's address delivery defaults to the account's
        // default address, so neither is needed then.
        if ($this->deliveryType === 'address' && !$this->isReverse
            && $this->deliveryAddress === null && $this->deliveryAddressId === null
        ) {
            throw new \InvalidArgumentException('Delivery address is required for address delivery');
        }

        // Validate locker delivery requirements. The SMS may be requested as
        // the documented service code instead of the legacy flag.
        if ($this->deliveryType === 'locker' && !$this->smsNotification
            && empty($this->serviceCodes['sms_notification_receiver'])
        ) {
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
