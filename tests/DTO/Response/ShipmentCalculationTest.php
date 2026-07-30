<?php

declare(strict_types=1);

namespace PigeonExpress\Tests\DTO\Response;

use PHPUnit\Framework\TestCase;
use PigeonExpress\DTO\Response\ShipmentCalculation;

/**
 * The calculate response is the only authoritative source of money in the whole
 * integration. This DTO silently dropped total_service_fees, which meant the
 * carrier's real COD fee could never reach the caller and a hardcoded percentage
 * was used instead. These tests exist to stop that happening again.
 */
class ShipmentCalculationTest extends TestCase
{
    /** Exactly what the live API returns for a priced shipment. */
    private function apiResponse(): array
    {
        return [
            'shipping_price'     => 2.21,
            'total_service_fees' => 1.88,
            'total_price'        => 4.09,
            'service_type'       => 'standard',
            'currency'           => 'EUR',
            'service_codes'      => ['cod_fee'],
        ];
    }

    public function testTotalServiceFeesIsParsed(): void
    {
        $calculation = ShipmentCalculation::fromArray($this->apiResponse());

        $this->assertSame(1.88, $calculation->getTotalServiceFees());
    }

    public function testShippingPriceIsParsed(): void
    {
        $calculation = ShipmentCalculation::fromArray($this->apiResponse());

        $this->assertSame(2.21, $calculation->getDeliveryFee());
    }

    public function testTotalPriceIsParsed(): void
    {
        $calculation = ShipmentCalculation::fromArray($this->apiResponse());

        $this->assertSame(4.09, $calculation->getTotal());
    }

    public function testCurrencyAndServiceTypeAreParsed(): void
    {
        $calculation = ShipmentCalculation::fromArray($this->apiResponse());

        $this->assertSame('EUR', $calculation->getCurrency());
        $this->assertSame('standard', $calculation->getServiceType());
    }

    public function testAppliedServiceCodesAreParsed(): void
    {
        $calculation = ShipmentCalculation::fromArray($this->apiResponse());

        $this->assertSame(['cod_fee'], $calculation->getAppliedServiceCodes());
    }

    public function testTotalPlusFeesAddUp(): void
    {
        $calculation = ShipmentCalculation::fromArray($this->apiResponse());

        $this->assertEqualsWithDelta(
            $calculation->getTotal(),
            $calculation->getDeliveryFee() + $calculation->getTotalServiceFees(),
            0.001
        );
    }

    public function testToArrayCarriesTotalServiceFeesThroughToConsumers(): void
    {
        // RateClient reads this array, so a missing key here is a silent zero fee.
        $array = ShipmentCalculation::fromArray($this->apiResponse())->toArray();

        $this->assertArrayHasKey('total_service_fees', $array);
        $this->assertSame(1.88, $array['total_service_fees']);
    }

    public function testToArrayExposesBothLegacyAndCurrentFieldNames(): void
    {
        $array = ShipmentCalculation::fromArray($this->apiResponse())->toArray();

        $this->assertSame(2.21, $array['delivery_fee']);
        $this->assertSame(2.21, $array['shipping_price']);
        $this->assertSame(4.09, $array['total']);
        $this->assertSame(4.09, $array['total_price']);
    }

    public function testServiceFeesDefaultToZeroWhenAbsent(): void
    {
        $calculation = ShipmentCalculation::fromArray(['shipping_price' => 5.0, 'total_price' => 5.0]);

        $this->assertSame(0.0, $calculation->getTotalServiceFees());
    }

    public function testAnEmptyResponseDoesNotBlowUp(): void
    {
        $calculation = ShipmentCalculation::fromArray([]);

        $this->assertSame(0.0, $calculation->getDeliveryFee());
        $this->assertSame(0.0, $calculation->getTotalServiceFees());
        $this->assertSame(0.0, $calculation->getTotal());
        $this->assertSame([], $calculation->getAppliedServiceCodes());
    }

    public function testBaselineFeesAreNotMistakenForACodFee(): void
    {
        // The API charges a baseline service fee even with no services selected,
        // so the COD fee is the difference between two calculations, never the
        // raw total_service_fees value.
        $baseline = ShipmentCalculation::fromArray([
            'shipping_price' => 2.21, 'total_service_fees' => 0.38, 'total_price' => 2.59,
        ]);
        $withCod = ShipmentCalculation::fromArray($this->apiResponse());

        $this->assertSame(0.38, $baseline->getTotalServiceFees());
        $this->assertEqualsWithDelta(
            1.50,
            $withCod->getTotalServiceFees() - $baseline->getTotalServiceFees(),
            0.001,
            'the real COD fee is 1.5 percent of 100.00'
        );
    }

    public function testLegacyResponseShapeStillParses(): void
    {
        $calculation = ShipmentCalculation::fromArray([
            'delivery_fee' => 3.0,
            'total'        => 4.0,
            'cod_fee'      => 1.0,
        ]);

        $this->assertSame(3.0, $calculation->getDeliveryFee());
        $this->assertSame(4.0, $calculation->getTotal());
        $this->assertSame(1.0, $calculation->getCodFee());
        $this->assertTrue($calculation->hasCodFee());
    }
}
