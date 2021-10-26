<?php
require 'offers.php';

class PromoTest extends \PHPUnit\Framework\TestCase {
	public function testPromo()
	{
		$promo    = new Promo();
		$vendorId = 35;
		$result   = $promo->checkByVendor($vendorId);
		$this->assertEquals(2, $result);

		$vendorId = 0;
		$result   = $promo->checkByVendor($vendorId);
		$this->assertEquals(0, $result);
	}
}