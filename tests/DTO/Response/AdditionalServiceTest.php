<?php

declare(strict_types=1);

namespace PigeonExpress\Tests\DTO\Response;

use PHPUnit\Framework\TestCase;
use PigeonExpress\DTO\Response\AdditionalService;

class AdditionalServiceTest extends TestCase
{
    public function testDefinitionFieldsAreParsed(): void
    {
        $service = AdditionalService::fromArray([
            'code'         => 'sms_notification_receiver',
            'name'         => 'SMS известие на получателя',
            'description'  => 'SMS известие до получателя',
            'input_type'   => 'checkbox',
            'option_group' => null,
        ]);

        $this->assertSame('sms_notification_receiver', $service->getCode());
        $this->assertSame('SMS известие на получателя', $service->getName());
        $this->assertSame('checkbox', $service->getInputType());
        $this->assertNull($service->getOptionGroup());
    }

    public function testTheCatalogueCarriesNoPrice(): void
    {
        // GET /additional-services returns definitions only. If a price field ever
        // appears here, the fee resolution strategy needs revisiting on purpose,
        // not by accident.
        $array = AdditionalService::fromArray(['code' => 'cod_amount'])->toArray();

        $this->assertSame(
            ['code', 'name', 'description', 'input_type', 'option_group'],
            array_keys($array)
        );
    }

    public function testTextServicesExpectAValue(): void
    {
        $service = AdditionalService::fromArray(['code' => 'cod_amount', 'input_type' => 'text']);

        $this->assertTrue($service->expectsValue());
    }

    public function testCheckboxServicesDoNotExpectAValue(): void
    {
        $service = AdditionalService::fromArray(['code' => 'return_receipt', 'input_type' => 'checkbox']);

        $this->assertFalse($service->expectsValue());
    }

    public function testServicesInAnOptionGroupAreMutuallyExclusive(): void
    {
        $service = AdditionalService::fromArray([
            'code'         => 'shipment_review',
            'input_type'   => 'select',
            'option_group' => 'shipment_view',
        ]);

        $this->assertTrue($service->isExclusive());
        $this->assertSame('shipment_view', $service->getOptionGroup());
    }

    public function testAServiceWithoutAGroupIsNotExclusive(): void
    {
        $service = AdditionalService::fromArray(['code' => 'return_receipt', 'input_type' => 'checkbox']);

        $this->assertFalse($service->isExclusive());
    }

    public function testNullDescriptionIsTolerated(): void
    {
        $service = AdditionalService::fromArray([
            'code'        => 'ID_verification_and_document_signature',
            'description' => null,
        ]);

        $this->assertNull($service->getDescription());
        $this->assertSame('ID_verification_and_document_signature', $service->getCode());
    }

    public function testAnEmptyPayloadYieldsAnEmptyCode(): void
    {
        $service = AdditionalService::fromArray([]);

        $this->assertSame('', $service->getCode());
        $this->assertNull($service->getInputType());
    }
}
