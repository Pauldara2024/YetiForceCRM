<?php

/**
 * WAPRO ERP company bank accounts synchronizer file.
 *
 * @package Integration
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

namespace App\Integrations\Wapro\Synchronizer;

/**
 * WAPRO ERP company bank accounts synchronizer class.
 */
class BankAccounts extends \App\Integrations\Wapro\Synchronizer
{
	/** {@inheritdoc} */
	const NAME = 'LBL_COMPANY_BANK_ACCOUNTS';

	/** {@inheritdoc} */
	protected $fieldMap = [
		'NAZWA' => 'name',
		'NUMER_RACHUNKU' => 'account_number',
		'bankName' => 'bank_name',
		'SWIFT' => 'swift',
		'SYM_WALUTY' => ['currency_id', 'convertCurrency'],
	];

	/** {@inheritdoc} */
	public function process(): void
	{
		$dataReader = (new \App\Db\Query())->select(['dbo.RACHUNEK_FIRMY.*', 'dbo.BANKI.SWIFT', 'bankName' => 'dbo.BANKI.NAZWA'])->from('dbo.RACHUNEK_FIRMY')
			->leftJoin('dbo.BANKI', 'dbo.RACHUNEK_FIRMY.ID_BANKU = dbo.BANKI.ID_BANKU')
			->createCommand($this->controller->getDb())->query();
		$e = $s = $i = $u = 0;
		while ($row = $dataReader->read()) {
			$this->row = $row;
			$this->skip = false;
			try {
				switch ($this->importRecord()) {
					default:
					case 0:
						++$s;
						break;
					case 1:
						++$u;
						break;
					case 2:
						++$i;
						break;
				}
			} catch (\Throwable $th) {
				$this->logError($th);
				++$e;
			}
		}
		$this->log("Create {$i} | Update {$u} | Skipped {$s} | Error {$e}");
	}

	/** {@inheritdoc} */
	public function importRecord(): int
	{
		$multiCompanyId = $this->findInMapTable($this->row['ID_FIRMY'], 'FIRMA');
		if (!$multiCompanyId) {
			return 0;
		}
		if ($id = $this->findInMapTable($this->row['ID_RACHUNKU'], 'RACHUNEK_FIRMY')) {
			$this->recordModel = \Vtiger_Record_Model::getInstanceById($id, 'BankAccounts');
		} else {
			$this->recordModel = \Vtiger_Record_Model::getCleanInstance('BankAccounts');
			$this->recordModel->setDataForSave([\App\Integrations\Wapro::RECORDS_MAP_TABLE_NAME => [
				'wtable' => 'RACHUNEK_FIRMY',
			]]);
		}
		$this->recordModel->set('bankaccount_status', $this->row['AKTYWNY'] ? 'PLL_ACTIVE' : 'PLL_INACTIVE');
		$this->recordModel->set('wapro_id', $this->row['ID_RACHUNKU']);
		$this->recordModel->set('multicompanyid', $multiCompanyId);
		$this->loadFromFieldMap();
		$this->recordModel->save();
		return $id ? 1 : 2;
	}
}