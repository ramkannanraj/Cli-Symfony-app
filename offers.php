<?php
declare(strict_types=1);

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

require './vendor/autoload.php';

/**
* Interface of Data Transfer Object, that represents external JSON data
*/
interface OfferInterface {}

/**
* Interface for The Collection class that contains Offers
*/
interface OfferCollectionInterface {
	public function get(int $index): OfferInterface;
	public function getIterator(): iterable;
}

/**
* The interface provides the contract for different readers
* E.g. it can be XML/JSON Remote Endpoint, or CSV/JSON/XML local files
*/
interface ReaderInterface {
/**
* Read in incoming data and parse to objects
*/
public function read(string $input): OfferCollectionInterface;
}

/**
 * Offer
 */
class Offer implements OfferInterface {

	public $offerId;
	public $productTitle;
	public $vendorId;
	public $price;

	public function __construct($offer)
	{
		$this->offerId      = $offer['offerId'];
		$this->productTitle = $offer['productTitle'];
		$this->vendorId     = $offer['vendorId'];
		$this->price        = $offer['price'];
	}

}

/**
 * OfferCollection
 */
class OfferCollection implements OfferCollectionInterface {
	public $offer;

	/**
	 * Constructor
	 *
	 * @param Array $offer
	 */
	public function __construct(array $offer)
	{
		$this->offer = $offer;
	}

	/**
	 * Get Offer
	 *
	 * @param integer $index IndexOF offer
	 *
	 * @return OfferInterface Offer
	 */
	public function get(int $index): OfferInterface
	{
		return new offer($this->offer[$index]);
	}

	/**
	 * Get offer Iterator
	 *
	 * @return iterable Offers
	 */
	public function getIterator(): iterable
	{
		return $this->offer;
	}
}

/**
 * Csv Reader
 */
class Csv implements ReaderInterface {
	public function read(string $input): OfferCollectionInterface
	{
		$inputData = json_decode($input, true);
		return new OfferCollection($inputData);
	}
}

/**
 * Json Reader
 */
class Json implements ReaderInterface {
	public function read(string $input): OfferCollectionInterface
	{
		$inputData = json_decode($input, true);
		return new OfferCollection($inputData);
	}
}

class FileReader {
	public function getData(ReaderInterface $type, string $data)
	{
		return $type->read($data);
	}
}

/**
 * Promo
 */
class Promo {
	public $offer;

	public function __construct()
	{
		$file     = 'offers.json';
		$filename = file_exists($file) ? $file : false;

		if (!$filename) {
			throw new \Exception('File not found');
		}

		$ext        = pathinfo($filename, PATHINFO_EXTENSION);
		$jsonString = file_get_contents('offers.json');

		$type = ($ext == 'json') ? new Json() : ($ext == 'csv') ? new Csv() : false;

		if (!$type) {
			throw new \Exception('Not a valid File');
		}

		$reader      = new FileReader();
		$this->offer = $reader->getData($type, $jsonString);
	}

	/**
	 * checkByVendor
	 *
	 * @param int $vendorId Vendor Id
	 *
	 * @return mixed
	 */
	public function checkByVendor(int $vendorId)
	{
		if (!is_int($vendorId)) {
			return false;
		}

		return count(array_filter($this->offer->getIterator(), function ($offer) use ($vendorId) {
			return $offer['vendorId'] ==  $vendorId;
		}));
	}

	/**
	 * checkByPriceBetween
	 *
	 * @param int $from From Range
	 * @param int $to   To Range
	 *
	 * @return int Count
	 */
	public function checkByPriceBetween(int $from, int $to)
	{
		return count(array_filter($this->offer->getIterator(), function ($offer) use ($from, $to) {
			return $offer['price'] > $from && $offer['price'] < $to;
		}));
	}

	/**
	 * Total
	 *
	 * @return int count
	 */
	public function getTotal(): int
	{
		return count($this->offer->getIterator());
	}
}

/**
 * Cli handler
 */
class Cli extends Command {

	protected function configure()
	 {
		  $this
		  ->setName('count_by_price_range')
		  ->setDescription('Filter Commands')
		  ->addArgument(
				'param1',
				InputArgument::OPTIONAL,
				'Which will be your first argument?'
		  )->addArgument(
				'param2',
				InputArgument::OPTIONAL,
				'Which will be your second argument?'
		  )->setAliases(array('count_by_vendor_id', 'count_total'));
	 }

	public function execute(InputInterface $input, OutputInterface $output)
	{
		$param1 = $input->getArgument('param1');
		$param2 = $input->getArgument('param2');

		$commandSelected = $input->getFirstArgument();
		$promo           = new Promo();

		if ($commandSelected == 'count_by_price_range')
			{
				$from = $input->getArgument('param1');
				$to   = $input->getArgument('param2');
				$output->write('Count by price range: ');

				if(empty($from) || empty($to)) {
					throw new \Exception('too few arguments');
				}

				$output->writeln($promo->checkByPriceBetween((int)$from, (int)$to));
			} else if ($commandSelected == 'count_by_vendor_id') {
				$vendorId = $input->getArgument('param1');

				if(empty($vendorId)) {
					throw new \Exception('too few arguments');
				}

				$output->write('Count by vendorId: ');
				$output->writeln($promo->checkByVendor($vendorId));
			} else if ($commandSelected == 'count_total') {
				$output->write('Total Count: ');
				$output->writeln($promo->getTotal());
			} else {
				$output->writeln('Invalid command');
			}

		return;
	}

}

