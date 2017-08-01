<?php
namespace App\Fields;

/**
 * Picklist class
 * @package YetiForce.App
 * @copyright YetiForce Sp. z o.o.
 * @license YetiForce Public License 2.0 (licenses/License.html or yetiforce.com)
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Picklist
{

	/**
	 * Function to get role based picklist values
	 * @param string $fieldName
	 * @param string $roleId
	 * @return array list of role based picklist values
	 */
	public static function getRoleBasedPicklistValues($fieldName, $roleId)
	{
		$cacheKey = $fieldName . $roleId;
		if (\App\Cache::has('getRoleBasedPicklistValues', $cacheKey)) {
			return \App\Cache::get('getRoleBasedPicklistValues', $cacheKey);
		}
		$dataReader = (new \App\Db\Query())->select($fieldName)
				->from("vtiger_$fieldName")
				->innerJoin('vtiger_role2picklist', "vtiger_role2picklist.picklistvalueid = vtiger_$fieldName.picklist_valueid")
				->innerJoin('vtiger_picklist', 'vtiger_picklist.picklistid = vtiger_role2picklist.picklistid')
				->where(['vtiger_role2picklist.roleid' => $roleId])
				->orderBy("vtiger_{$fieldName}.sortorderid")
				->createCommand()->query();
		$fldVal = [];
		while (($val = $dataReader->readColumn(0)) !== false) {
			$fldVal[] = decode_html($val);
		}
		\App\Cache::save('getRoleBasedPicklistValues', $cacheKey, $fldVal);
		return $fldVal;
	}

	/**
	 * Function which will give the picklist values for a field
	 * @param string $fieldName -- string
	 * @return array -- array of values
	 */
	public static function getPickListValues($fieldName)
	{
		if (\App\Cache::has('getPickListValues', $fieldName)) {
			return \App\Cache::get('getPickListValues', $fieldName);
		}
		$primaryKey = static::getPickListId($fieldName);
		$dataReader = (new \App\Db\Query())->select([$primaryKey, $fieldName])
				->from("vtiger_$fieldName")
				->orderBy('sortorderid')
				->createCommand()->query();
		$values = [];
		while ($row = $dataReader->read()) {
			$values[$row[$primaryKey]] = decode_html(decode_html($row[$fieldName]));
		}
		\App\Cache::save('getPickListValues', $fieldName, $values);
		return $values;
	}

	/**
	 * Function which will give the editable picklist values for a field
	 * @param string $fieldName -- string
	 * @return array -- array of values
	 */
	public static function getEditablePicklistValues($fieldName)
	{
		$values = static::getPickListValues($fieldName);
		$nonEditableValues = static::getNonEditablePicklistValues($fieldName);
		foreach ($values as $key => &$value) {
			if ($value === '--None--' || isset($nonEditableValues[$key])) {
				unset($values[$key]);
			}
		}
		return $values;
	}

	/**
	 * Function which will give the non editable picklist values for a field
	 * @param string $fieldName -- string
	 * @return array -- array of values
	 */
	public static function getNonEditablePicklistValues($fieldName)
	{
		if (\App\Cache::has('getNonEditablePicklistValues', $fieldName)) {
			return \App\Cache::get('getNonEditablePicklistValues', $fieldName);
		}
		$primaryKey = static::getPickListId($fieldName);
		$dataReader = (new \App\Db\Query())->select([$primaryKey, $fieldName])
				->from("vtiger_$fieldName")
				->where(['presence' => 0])
				->createCommand()->query();
		$values = [];
		while ($row = $dataReader->read()) {
			$values[$row[$primaryKey]] = decode_html(decode_html($row[$fieldName]));
		}
		\App\Cache::save('getNonEditablePicklistValues', $fieldName, $values);
		return $values;
	}

	/**
	 * Function to get picklist key for a picklist
	 */
	public static function getPickListId($fieldName)
	{
		$pickListIds = [
			'opportunity_type' => 'opptypeid',
			'sales_stage' => 'sales_stage_id',
			'rating' => 'rating_id',
			'ticketpriorities' => 'ticketpriorities_id',
			'ticketseverities' => 'ticketseverities_id',
			'ticketstatus' => 'ticketstatus_id',
			'salutationtype' => 'salutationid',
			'faqstatus' => 'faqstatus_id',
			'faqcategories' => 'faqcategories_id',
			'recurring_frequency' => 'recurring_frequency_id',
			'payment_duration' => 'payment_duration_id',
			'language' => 'id',
			'duration_minutes' => 'minutesid'
		];
		if (isset($pickListIds[$fieldName])) {
			return $pickListIds[$fieldName];
		}
		return $fieldName . 'id';
	}

	/**
	 * Function to get modules which has picklist values
	 * It gets the picklist modules and return in an array in the following format
	 * $modules = Array($tabid=>$tablabel,$tabid1=>$tablabel1,$tabid2=>$tablabel2,-------------,$tabidn=>$tablabeln)
	 */
	public static function getPickListModules()
	{
		$adb = \PearDatabase::getInstance();
		// vtlib customization: Ignore disabled modules.
		$query = 'select distinct vtiger_field.fieldname,vtiger_field.tabid,vtiger_tab.tablabel, vtiger_tab.name as tabname,uitype from vtiger_field inner join vtiger_tab on vtiger_tab.tabid=vtiger_field.tabid where uitype IN (15,33) and vtiger_field.tabid != 29 and vtiger_tab.presence != 1 and vtiger_field.presence in (0,2) order by vtiger_field.tabid ASC';
		// END
		$result = $adb->pquery($query, []);
		while ($row = $adb->fetch_array($result)) {
			$modules[$row['tablabel']] = $row['tabname'];
		}
		return $modules;
	}

	/**
	 * this function returns all the assigned picklist values for the given tablename for the given roleid
	 * @param string $tableName - the picklist tablename
	 * @param integer $roleid - the roleid of the role for which you want data
	 * @return array $val - the assigned picklist values in array format
	 */
	public static function getAssignedPicklistValues($tableName, $roleid)
	{
		if (\App\Cache::has('getAssignedPicklistValues', $tableName . $roleid)) {
			return \App\Cache::get('getAssignedPicklistValues', $tableName . $roleid);
		}
		$values = [];
		$adb = \PearDatabase::getInstance();
		$sql = "select picklistid from vtiger_picklist where name = ?";
		$result = $adb->pquery($sql, array($tableName));
		if ($adb->num_rows($result)) {
			$picklistid = $adb->query_result($result, 0, "picklistid");

			$sub = getSubordinateRoleAndUsers($roleid);
			$subRoles = array($roleid);
			$subRoles = array_merge($subRoles, array_keys($sub));

			$roleids = [];
			foreach ($subRoles as $role) {
				$roleids[] = $role;
			}

			$sql = sprintf('SELECT distinct %s, sortid FROM %s inner join vtiger_role2picklist on %s.picklist_valueid=vtiger_role2picklist.picklistvalueid'
				. ' and roleid in (%s) order by sortid', $adb->sql_escape_string($tableName, true), $adb->sql_escape_string("vtiger_$tableName", true), $adb->sql_escape_string("vtiger_$tableName", true), $adb->generateQuestionMarks($roleids));
			$result = $adb->pquery($sql, $roleids);
			$count = $adb->num_rows($result);

			if ($count) {
				while ($resultrow = $adb->fetch_array($result)) {
					/** Earlier we used to save picklist values by encoding it. Now, we are directly saving those(getRaw()).
					 *  If value in DB is like "test1 &amp; test2" then $abd->fetch_[] is giving it as
					 *  "test1 &amp;$amp; test2" which we should decode two time to get result
					 */
					$pick_val = decode_html(decode_html($resultrow[$tableName]));
					$values[$pick_val] = $pick_val;
				}
			}

			// END
			\App\Cache::save('getAssignedPicklistValues', $tableName . $roleid, $values);
			return $values;
		}
	}

	/**
	 * Function to get picklist dependency data source
	 * @param string $module
	 * @return array
	 */
	public static function getPicklistDependencyDatasource($module)
	{
		if (\App\Cache::has('getPicklistDependencyDatasource', $module)) {
			return \App\Cache::get('getPicklistDependencyDatasource', $module);
		}
		$query = (new \App\Db\Query())->from('vtiger_picklist_dependency')->where(['tabid' => $tabId]);
		$dataReader = $query->createCommand()->query();
		$picklistDependencyDatasource = [];
		while ($row = $dataReader->read()) {
			$pickArray = [];
			$sourceField = $row['sourcefield'];
			$targetField = $row['targetfield'];
			$sourceValue = App\Purifier::decodeHtml($row['sourcevalue']);
			$targetValues = App\Purifier::decodeHtml($row['targetvalues']);
			$unserializedTargetValues = \App\Json::decode(html_entity_decode($targetValues));
			$criteria = App\Purifier::decodeHtml($row['criteria']);
			$unserializedCriteria = \App\Json::decode(html_entity_decode($criteria));

			if (!empty($unserializedCriteria) && $unserializedCriteria['fieldname'] !== null) {
				$conditionValue = [
					'condition' => [$unserializedCriteria['fieldname'] => $unserializedCriteria['fieldvalues']],
					'values' => $unserializedTargetValues
				];
				$picklistDependencyDatasource[$sourceField][$sourceValue][$targetField][] = $conditionValue;
			} else {
				$picklistDependencyDatasource[$sourceField][$sourceValue][$targetField] = $unserializedTargetValues;
			}
			if (empty($picklistDependencyDatasource[$sourceField]['__DEFAULT__'][$targetField])) {
				foreach (App\Fields\Picklist::getPickListValues($targetField) as $picklistValue) {
					$pickArray[] = App\Purifier::decodeHtml($picklistValue);
				}
				$picklistDependencyDatasource[$sourceField]['__DEFAULT__'][$targetField] = $pickArray;
			}
		}
		\App\Cache::save('getPicklistDependencyDatasource', $module, $picklistDependencyDatasource);
		return $picklistDependencyDatasource;
	}
}
