<?php

namespace ZuluCrypto\StellarSdk\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use ZuluCrypto\StellarSdk\Horizon\Api\HorizonResponse;
use ZuluCrypto\StellarSdk\Model\Account;
use ZuluCrypto\StellarSdk\Model\AssetTransferInterface;
use ZuluCrypto\StellarSdk\Model\Operation;

class AccountPaymentOperationsTest extends TestCase
{
    /**
     * Verifies getPaymentOperations() filters to payment/path_payment and returns typed models.
     */
    public function testGetPaymentOperationsFilters()
    {
        // Build a minimal account via fromHorizonResponse to set accountId
        $accountJson = json_encode([
            'id' => '12345',
            'account_id' => 'GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWHF',
            'sequence' => '1',
            'subentry_count' => 0,
            'thresholds' => ['low_threshold' => 0, 'med_threshold' => 0, 'high_threshold' => 0],
            'balances' => [],
            'status' => 200,
        ]);
        $account = Account::fromHorizonResponse(new HorizonResponse($accountJson));

        // Dummy ApiClient that returns a payments feed with mixed types
        $dummyClient = new class {
            public function get($relativeUrl)
            {
                $payload = [
                    '_embedded' => [
                        'records' => [
                            // Should be ignored by getPaymentOperations
                            [
                                'id' => '1',
                                'type' => Operation::TYPE_CREATE_ACCOUNT,
                                'type_i' => 0,
                                'account' => 'GCREATE',
                                'funder' => 'GFUNDER',
                                'starting_balance' => '100.0000000',
                                'transaction_hash' => 'tx1',
                            ],
                            // Included (payment)
                            [
                                'id' => '2',
                                'type' => Operation::TYPE_PAYMENT,
                                'type_i' => 1,
                                'source_account' => 'GSRC',
                                'from' => 'GFROM',
                                'to' => 'GTO',
                                'asset_type' => 'native',
                                'amount' => '12.0000000',
                                'transaction_hash' => 'tx2',
                            ],
                            // Should be ignored by getPaymentOperations
                            [
                                'id' => '3',
                                'type' => Operation::TYPE_ACCOUNT_MERGE,
                                'type_i' => 8,
                                'account' => 'GOLD',
                                'into' => 'GNEW',
                                'transaction_hash' => 'tx3',
                            ],
                            // Included (path_payment)
                            [
                                'id' => '4',
                                'type' => Operation::TYPE_PATH_PAYMENT,
                                'type_i' => 2,
                                'from' => 'GFROM2',
                                'to' => 'GTO2',
                                // destination asset
                                'amount' => '5.0000000',
                                'asset_type' => 'credit_alphanum4',
                                'asset_code' => 'USD',
                                'asset_issuer' => 'GISSUER',
                                // source asset and max
                                'source_amount' => '6.0000000',
                                'source_asset_type' => 'native',
                                'source_asset_code' => null,
                                'source_asset_issuer' => null,
                                'source_max' => '6.0000000',
                                'transaction_hash' => 'tx4',
                            ],
                        ],
                    ],
                ];

                return new HorizonResponse(json_encode($payload));
            }
        };

        $account->setApiClient($dummyClient);

        $ops = $account->getPaymentOperations();

        $this->assertCount(2, $ops);
        foreach ($ops as $op) {
            $this->assertInstanceOf(AssetTransferInterface::class, $op);
            $this->assertTrue(in_array($op->getAssetTransferType(), [Operation::TYPE_PAYMENT, Operation::TYPE_PATH_PAYMENT], true));
        }
    }
}

