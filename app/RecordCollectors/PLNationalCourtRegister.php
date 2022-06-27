<?php
/**
 * Polish National Court Register record collector file.
 *
 * @package App
 *
 * @see https://prs.ms.gov.pl/krs/openApi
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Sławomir Rembiesa <s.rembiesa@yetiforce.com>
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

namespace App\RecordCollectors;

/**
 * Polish National Court Register record collector class.
 */
class PLNationalCourtRegister extends Base
{
	/** {@inheritdoc} */
	protected static $allowedModules = ['Accounts', 'Leads', 'Vendors'];

	/** {@inheritdoc} */
	public $icon = 'fa-solid fa-hammer';

	/** {@inheritdoc} */
	public $label = 'LBL_POLAND_NCR';

	/** {@inheritdoc} */
	public $displayType = 'FillFields';

	/** {@inheritdoc} */
	public $description = 'LBL_POLAND_NCR_DESC';

	/** {@inheritdoc} */
	protected $fields = [
		'taxNumber' => [
			'labelModule' => '_Base',
			'label' => 'Registration number 1',
			'typeofdata' => 'V~M',
		]
	];

	/** {@inheritdoc} */
	protected $modulesFieldsMap = [
		'Accounts' => [
			'taxNumber' => 'registration_number_1',
		],
		'Leads' => [
			'taxNumber' => 'registration_number_1',
		],
		'Vendors' => [
			'taxNumber' => 'registration_number_1',
		]
	];

	/** {@inheritdoc} */
	public $formFieldsToRecordMap = [
		'Accounts' => [
			'daneDzial1DanePodmiotuNazwa' => 'accountname',
			'daneDzial1DanePodmiotuIdentyfikatoryRegon' => 'registration_number_2',
			'naglowekANumerKRS' => 'registration_number_1',
			'daneDzial1DanePodmiotuIdentyfikatoryNip' => 'vat_id',
			'daneDzial1SiedzibaIAdresAdresStronyInternetowej' => 'website',
			'Annual revenue' => 'annual_revenue',
			'daneDzial3PrzedmiotDzialalnosciPrzedmiotPrzewazajacejDzialalnosci0' => 'siccode',
			'daneDzial3PrzedmiotDzialalnosciPrzedmiotPozostalejDzialalnosci0' => 'siccode',
			'daneDzial1SiedzibaIAdresAdresUlica' => 'addresslevel8a',
			'daneDzial1SiedzibaIAdresAdresNrDomu' => 'buildingnumbera',
			'dane_dzial1_siedzibaIAdres_siedziba_miejscowosc' => 'addresslevel5a',
			'dane_dzial1_siedzibaIAdres_adres_miejscowosc' => 'addresslevel5a',
			'daneDzial1SiedzibaIAdresAdresKodPocztowy' => 'addresslevel7a',
			'daneDzial1SiedzibaIAdresSiedzibaWojewodztwo' => 'addresslevel2a',
			'dane_dzial1_siedzibaIAdres_siedziba_kraj' => 'addresslevel1a',
			'dane_dzial1_siedzibaIAdres_adres_kraj' => 'addresslevel1a',
			'daneDzial1SiedzibaIAdresSiedzibaGmina' => 'addresslevel4a',
			'daneDzial1SiedzibaIAdresSiedzibaPowiat' => 'addresslevel3a',
		],
		'Leads' => [
			'daneDzial1DanePodmiotuNazwa' => 'company',
			'daneDzial1DanePodmiotuIdentyfikatoryRegon' => 'registration_number_2',
			'naglowekANumerKRS' => 'registration_number_1',
			'daneDzial1DanePodmiotuIdentyfikatoryNip' => 'vat_id',
			'Annual revenue' => 'annualrevenue',
			'daneDzial1SiedzibaIAdresAdresStronyInternetowej' => 'website',
			'daneDzial1SiedzibaIAdresAdresUlica' => 'addresslevel8a',
			'daneDzial1SiedzibaIAdresAdresNrDomu' => 'buildingnumbera',
			'dane_dzial1_siedzibaIAdres_adres_miejscowosc' => 'addresslevel5a',
			'daneDzial1SiedzibaIAdresAdresKodPocztowy' => 'addresslevel7a',
			'daneDzial1SiedzibaIAdresSiedzibaWojewodztwo' => 'addresslevel2a',
			'dane_dzial1_siedzibaIAdres_adres_kraj' => 'addresslevel1a',
			'daneDzial1SiedzibaIAdresSiedzibaGmina' => 'addresslevel4a',
			'daneDzial1SiedzibaIAdresSiedzibaPowiat' => 'addresslevel3a',
		],
		'Vendors' => [
			'daneDzial1DanePodmiotuNazwa' => 'vendorname',
			'daneDzial1DanePodmiotuIdentyfikatoryRegon' => 'registration_number_2',
			'naglowekANumerKRS' => 'registration_number_1',
			'daneDzial1DanePodmiotuIdentyfikatoryNip' => 'vat_id',
			'daneDzial1SiedzibaIAdresAdresStronyInternetowej' => 'website',
			'daneDzial1SiedzibaIAdresAdresUlica' => 'addresslevel8a',
			'daneDzial1SiedzibaIAdresAdresNrDomu' => 'buildingnumbera',
			'dane_dzial1_siedzibaIAdres_adres_miejscowosc' => 'addresslevel5a',
			'daneDzial1SiedzibaIAdresAdresKodPocztowy' => 'addresslevel7a',
			'daneDzial1SiedzibaIAdresSiedzibaWojewodztwo' => 'addresslevel2a',
			'dane_dzial1_siedzibaIAdres_adres_kraj' => 'addresslevel1a',
			'daneDzial1SiedzibaIAdresSiedzibaGmina' => 'addresslevel4a',
			'daneDzial1SiedzibaIAdresSiedzibaPowiat' => 'addresslevel3a',
		]
	];

	/** @var string NCR sever address */
	protected $url = 'https://api-krs.ms.gov.pl/api/krs/OdpisAktualny/';

	/** {@inheritdoc} */
	public function search(): array
	{
		$this->moduleName = $this->request->getModule();
		$taxNumber = str_replace([' ', ',', '.', '-'], '', $this->request->getByType('taxNumber', 'Text'));
		if (!$taxNumber) {
			return [];
		}
		$this->getDataFromApi($taxNumber);
		$this->parseData();
		if (empty($this->data)) {
			return [];
		}
		$this->loadData();
		$this->response['additional'] = $this->data;
		return $this->response;
	}

	/**
	 * Function fetching from Polish National Court Register API.
	 *
	 * @param mixed $taxNumber
	 *
	 * @return void
	 */
	private function getDataFromApi($taxNumber): void
	{
		try {
			$responseData = (new \GuzzleHttp\Client(\App\RequestHttp::getOptions()))
				->request('GET', "{$this->url}{$taxNumber}?rejestr=P&format=json");
			$this->data = \App\Json::decode($responseData->getBody()->getContents())['odpis'] ?? [];
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			\App\Log::warning($e->getMessage(), 'RecordCollectors');
			$this->response['error'] = $e->getMessage();
		}
	}

	/**
	 * Function parsing data to fields from Polish National Court Register API.
	 *
	 * @return void
	 */
	private function parseData(): void
	{
		if (empty($this->data)) {
			return;
		}
		if (isset($this->data['dane']['dzial3']['przedmiotDzialalnosci']['przedmiotPrzewazajacejDzialalnosci'][0])) {
			$this->convertPkd($this->data['dane']['dzial3']['przedmiotDzialalnosci']['przedmiotPrzewazajacejDzialalnosci'][0]);
		}
		if (isset($this->data['dane']['dzial3']['przedmiotDzialalnosci']['przedmiotPozostalejDzialalnosci'])) {
			$all = '';
			foreach ($this->data['dane']['dzial3']['przedmiotDzialalnosci']['przedmiotPozostalejDzialalnosci'] as &$value) {
				$this->convertPkd($value);
				$all .= "$value, ";
			}
			$this->data['dane']['dzial3']['przedmiotDzialalnosci']['przedmiotPozostalejDzialalnosciAll'] = rtrim($all, ', ');
		}
		if (isset($this->data['dane']['dzial1']['kapital'])) {
			$this->data['Annual revenue'] = (float) $this->data['dane']['dzial1']['kapital']['wysokoscKapitaluZakladowego']['wartosc'];
			unset($this->data['dane']['dzial1']['kapital']['wysokoscKapitaluZakladowego']['wartosc']);
		}
		$this->data = \App\Utils::flattenKeys($this->data, 'ucfirst');
	}

	/**
	 * Convert PKD value.
	 *
	 * @param array $pkd
	 *
	 * @return void
	 */
	private function convertPkd(array &$pkd): void
	{
		$pkd = $pkd['kodDzial'] . '.' . $pkd['kodKlasa'] . '.' . $pkd['kodPodklasa'];
	}
}
