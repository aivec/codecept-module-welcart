<?php

use Codeception\Module\Welcart;

class CreateItemTest extends \Codeception\TestCase\WPTestCase
{
    /**
     * @var \WpunitTester
     */
    protected $tester;

    public function setUp(): void {
        // Before...
        parent::setUp();

        // Your set up methods here.
    }

    public function tearDown(): void {
        // Your tear down methods here.
        // Then...
        parent::tearDown();
    }

    public function testItemCreationFailsWhenSkusIsEmpty() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(Welcart::SKUS_EMPTY);
        $this->tester->createItem('TEST_ITEM', []);
    }

    public function testItemCreationFailsWhenSkuNameIsEmpty() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(Welcart::SKU_NAME_EMPTY);
        $this->tester->createItem('TEST_ITEM', [[]]);
    }

    public function testItemCreationFailsWhenSkuPriceIsEmpty() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(Welcart::SKU_PRICE_EMPTY);
        $this->tester->createItem('TEST_ITEM', [['newskuname' => 'TEST_SKU']]);
    }

    public function testItemCreatedExistsInDatabase() {
        global $usces;

        $itemcode = 'TEST_ITEM';
        $sku1code = 'TEST_SKU1';
        $sku1price = 2000;
        $post_id = $this->tester->createItem($itemcode, [
            [
                'newskuname' => $sku1code,
                'newskuprice' => $sku1price,
            ],
        ]);

        $this->assertIsInt($post_id);
        $this->assertSame($itemcode, $usces->getItemCode($post_id));
        $this->assertSame(0, (int)$usces->getItemZaikoStatusId($post_id, $sku1code));

        $skus = $usces->get_skus($post_id, 'code');
        $this->assertSame($sku1price, (int)$skus[$sku1code]['price']);
    }

    public function testItemCanBeCreatedWithMultipleSkus() {
        global $usces;

        $post_id = $this->tester->createItem('TEST_ITEM', [
            [
                'newskuname' => 'TEST_SKU1',
                'newskuprice' => 1000,
            ],
            [
                'newskuname' => 'TEST_SKU2',
                'newskuprice' => 2000,
            ],
        ]);

        $skus = $usces->get_skus($post_id, 'code');
        $this->assertSame(2, count($skus));
        $this->assertSame(1000, (int)$skus['TEST_SKU1']['price']);
        $this->assertSame(2000, (int)$skus['TEST_SKU2']['price']);
    }
}
