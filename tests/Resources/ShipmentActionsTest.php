<?php

declare(strict_types=1);

namespace PigeonExpress\Tests\Resources;

use PHPUnit\Framework\TestCase;
use PigeonExpress\DTO\Response\ShipmentActions;
use PigeonExpress\Exceptions\ConflictException;
use PigeonExpress\Exceptions\ValidationException;
use PigeonExpress\Resources\AbstractResource;
use PigeonExpress\Resources\Shipments;

/**
 * The sender's quick actions: what is allowed now, and running or pricing
 * one. Shapes are taken from the API documentation (v1.0.32).
 */
class ShipmentActionsTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function actionsData(): array
    {
        return [
            'actions' => [
                'change_delivery' => ['allowed' => true],
                'services' => ['allowed' => 'true'],
                'declared_value' => [
                    'allowed' => false,
                    'reason_code' => 'action_not_allowed',
                    'reason' => 'Действието не е възможно за тази пратка.',
                ],
            ],
            'addable_services' => [
                ['code' => 'shipment_review', 'name' => 'Преглед преди плащане'],
                ['name' => 'no code'],
            ],
            'removable_services' => [['code' => 'shipment_test_before_payment', 'name' => 'Тест преди плащане']],
            'pricing' => ['total' => 5.4, 'paid_amount' => 0, 'payment_mode' => 'deferred', 'currency' => 'EUR'],
            'current' => ['delivery_type' => 'office', 'delivery_office_id' => 12, 'cod_amount' => 120.5],
            'active_reference_number' => null,
        ];
    }

    public function testActionsReadsAllFourAndTheirReasons(): void
    {
        $client = new ScriptedHttpClient([ScriptedHttpClient::json(['success' => true, 'data' => $this->actionsData()])]);

        $actions = (new Shipments($client))->actions(' 459073686609 ');

        $this->assertSame('GET', $client->calls[0]['method']);
        $this->assertSame('shipments/459073686609/actions', $client->calls[0]['path']);
        $this->assertTrue($actions->isAllowed('change_delivery'));
        $this->assertTrue($actions->isAllowed('change-delivery'), 'either spelling');
        $this->assertTrue($actions->isAllowed(Shipments::ACTION_SERVICES), 'a string "true" is true');
        $this->assertFalse($actions->isAllowed('cod'), 'an action left out is not allowed');
        $this->assertFalse($actions->isAllowed('declared-value'));
        $this->assertSame('action_not_allowed', $actions->getReasonCode('declared_value'));
        $this->assertSame('Действието не е възможно за тази пратка.', $actions->getReason('declared_value'));
        $this->assertFalse($actions->isAllowed('edit'), 'not a quick action');
        $this->assertSame(
            ['change_delivery', 'services', 'cod', 'declared_value'],
            array_keys($actions->getActions())
        );
        $this->assertSame(
            ['allowed' => false, 'reason_code' => '', 'reason' => ''],
            $actions->toArray()['actions']['cod'],
            'toArray() always holds all four in one shape'
        );
        $this->assertSame([['code' => 'shipment_review', 'name' => 'Преглед преди плащане']], $actions->getAddableServices());
        $this->assertSame('shipment_test_before_payment', $actions->getRemovableServices()[0]['code']);
        $this->assertSame('deferred', $actions->getPricing()['payment_mode']);
        $this->assertSame(12, $actions->getCurrent()['delivery_office_id']);
        $this->assertSame('', $actions->getActiveReferenceNumber());
    }

    public function testActionsOfARedirectedShipmentNameTheActiveOne(): void
    {
        $data = $this->actionsData();
        $data['active_reference_number'] = '459073712345';
        $client = new ScriptedHttpClient([ScriptedHttpClient::json(['success' => true, 'data' => $data])]);

        $this->assertSame('459073712345', (new Shipments($client))->actions('459073686609')->getActiveReferenceNumber());
    }

    public function testActionsNeedAReference(): void
    {
        $client = new ScriptedHttpClient();

        $this->expectException(\InvalidArgumentException::class);
        try {
            (new Shipments($client))->actions(' ');
        } finally {
            $this->assertCount(0, $client->calls);
        }
    }

    public function testTheActionSegmentAcceptsBothSpellings(): void
    {
        $this->assertSame('change-delivery', Shipments::actionSegment('change_delivery'));
        $this->assertSame('change-delivery', Shipments::actionSegment(' Change-Delivery '));
        $this->assertSame('declared-value', Shipments::actionSegment('declared_value'));
        $this->assertSame('services', Shipments::actionSegment('services'));
        $this->assertSame('cod', Shipments::actionSegment('cod'));
        $this->assertSame('', Shipments::actionSegment('edit'));
        $this->assertSame('declared_value', ShipmentActions::nameOf('declared-value'));
    }

    public function testADryRunIsPricedAndNeverRepeated(): void
    {
        $client = new ScriptedHttpClient([ScriptedHttpClient::json([
            'success' => true,
            'message' => 'Цената на промяната е изчислена. Пратката не е променена.',
            'data' => [
                'dry_run' => true,
                'price' => [
                    'previous_total' => 5.4,
                    'new_total' => 7.35,
                    'paid_amount' => 0,
                    'amount_due' => 7.35,
                    'payment_mode' => 'deferred',
                ],
                'result' => [
                    'reference_number' => '459073686609',
                    'new_reference_number' => null,
                    'expected_delivery_date' => null,
                    'removed_services' => ['allow_cod_card_payment'],
                ],
            ],
        ])]);
        $shipments = new SleeplessShipments($client);

        $result = $shipments->runAction('459073686609', 'change_delivery', [
            'delivery_type' => 'office',
            'delivery_office_id' => 12,
            'dry_run' => 'no',
        ], true);

        $this->assertSame('POST', $client->calls[0]['method']);
        $this->assertSame('shipments/459073686609/actions/change-delivery', $client->calls[0]['path']);
        $this->assertSame(
            ['delivery_type' => 'office', 'delivery_office_id' => 12, 'dry_run' => true],
            $client->calls[0]['body'],
            'dry_run comes from the argument, as a boolean'
        );
        $this->assertTrue($result->isDryRun());
        $this->assertSame(7.35, $result->getAmountDue());
        $this->assertSame(7.35, $result->getNewTotal());
        $this->assertSame(5.4, $result->getPreviousTotal());
        $this->assertSame('deferred', $result->getPaymentMode());
        $this->assertSame('', $result->getNewReferenceNumber());
        $this->assertSame(['allow_cod_card_payment'], $result->getRemovedServices());
        $this->assertSame('Цената на промяната е изчислена. Пратката не е променена.', $result->getMessage());
        $this->assertSame(AbstractResource::REPLAY_NONE, $shipments->getLastReplay());
    }

    public function testADryRunConflictIsThrownAtOnce(): void
    {
        $client = new ScriptedHttpClient([new ConflictException('in progress', 3)]);
        $shipments = new SleeplessShipments($client);

        $this->expectException(ConflictException::class);
        try {
            $shipments->runAction('1', 'cod', ['cod_amount' => 10], true);
        } finally {
            $this->assertCount(1, $client->calls);
            $this->assertSame([], $shipments->pauses);
        }
    }

    public function testARealActionRepeatsAConflictAndReportsTheRedirect(): void
    {
        $client = new ScriptedHttpClient([
            new ConflictException('in progress', 2),
            ScriptedHttpClient::json([
                'success' => true,
                'message' => 'Промяната е направена.',
                'data' => [
                    'dry_run' => false,
                    'price' => ['new_total' => 7.35, 'amount_due' => 7.35],
                    'result' => ['reference_number' => '459073686609', 'new_reference_number' => '459073712345'],
                ],
            ], 200, ['X-Duplicate-Request' => 'true']),
        ]);
        $shipments = new SleeplessShipments($client);

        $result = $shipments->runAction('459073686609', 'change-delivery', ['delivery_type' => 'office', 'delivery_office_id' => 12]);

        $this->assertCount(2, $client->calls);
        $this->assertSame($client->calls[0]['body'], $client->calls[1]['body'], 'the repeat must be identical');
        $this->assertFalse($client->calls[0]['body']['dry_run']);
        $this->assertSame([2000000], $shipments->pauses);
        $this->assertSame(AbstractResource::REPLAY_REPEAT, $shipments->getLastReplay());
        $this->assertFalse($result->isDryRun());
        $this->assertSame('459073712345', $result->getNewReferenceNumber());
        $this->assertSame('Промяната е направена.', $result->getMessage());
    }

    public function testANullIsSentToRemoveAValue(): void
    {
        $client = new ScriptedHttpClient();

        (new Shipments($client))->runAction('1', 'declared-value', ['declared_value' => null]);
        (new Shipments($client))->runAction('1', 'services', ['add' => ['shipment_review']]);

        $this->assertSame('shipments/1/actions/declared-value', $client->calls[0]['path']);
        $this->assertArrayHasKey('declared_value', $client->calls[0]['body']);
        $this->assertNull($client->calls[0]['body']['declared_value']);
        $this->assertSame('shipments/1/actions/services', $client->calls[1]['path']);
    }

    public function testAnUnknownActionIsNotSent(): void
    {
        $client = new ScriptedHttpClient();

        $this->expectException(\InvalidArgumentException::class);
        try {
            (new Shipments($client))->runAction('1', 'edit', []);
        } finally {
            $this->assertCount(0, $client->calls);
        }
    }

    public function testARefusalCarriesItsCodeAndFields(): void
    {
        $refusal = (new ValidationException('Пратката е пренасочена.', [], 422))->withResponseData([
            'success' => false,
            'message' => 'Пратката е пренасочена.',
            'code' => 'already_redirected',
            'errors' => [],
            'status_code' => 422,
            'active_reference_number' => '459073712345',
        ]);
        $client = new ScriptedHttpClient([$refusal]);

        try {
            (new SleeplessShipments($client))->runAction('459073686609', 'change_delivery', ['delivery_type' => 'office']);
            $this->fail('expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame('already_redirected', $e->getErrorCode());
            $this->assertSame('459073712345', $e->getResponseField('active_reference_number'));
            $this->assertSame(422, $e->getHttpStatusCode());
            $this->assertCount(1, $client->calls, 'a refusal is not repeated');
        }
    }
}
