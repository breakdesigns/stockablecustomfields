<?php
/**
 * @version		$Id: customfield.php 2014-12-19 18:57 sakis Terz $
 * @package		stockablecustomfields
 * @copyright	Copyright (C)2014-2015 breakdesigns.net . All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 *
 * Class that contains the necessary functions used by the customfield
 * @package		stockablecustomfields
 *
 */
Class CustomfieldStockablecustomfields{
	protected $_custom_id;
	protected static $instances;
	protected static $_customparams;
	/**
	 * Constructor
	 *
	 * @param 	int $_custom_id
	 * @since	1.0
	 */
	public function __construct($_custom_id){
		$this->_custom_id=(int)$_custom_id;
	}

	/**
	 * Get the singleton customfield instance
	 *
	 * @param int $custom_id
	 */
	public static function getInstance($custom_id){
		if(empty(self::$instances[$custom_id])){
			self::$instances[$custom_id]=new CustomfieldStockablecustomfields($custom_id);
		}
		return self::$instances[$custom_id];
	}

	/**
	 * Get a custom record from the db
	 *
	 * @param 	int $custom_id
	 * @return	object	The custom record
	 * @since	1.0
	 */
	public static function getCustom($custom_id){
		$db=JFactory::getDbo();
		$q=$db->getQuery(true);
		$q->select('*')->from('#__virtuemart_customs ')->where('virtuemart_custom_id='.(int)$custom_id);
		$db->setQuery($q);
		$result=$db->loadObject();
		return $result;
	}

	/**
	 *
	 * Returns the lang string of the custom type
	 * @param 	string $key_type
	 * @return	string
	 * @since	1.0
	 */
	static function getCustomTypeName ($key_type) {

		$types=array('S' => 'COM_VIRTUEMART_CUSTOM_STRING',
			'C' => 'COM_VIRTUEMART_CHILDVARIANT',
			'D' => 'COM_VIRTUEMART_DATE',
			'T' => 'COM_VIRTUEMART_TIME',
			'M' => 'COM_VIRTUEMART_IMAGE',
			'B' => 'COM_VIRTUEMART_CUSTOM_BOOLEAN',
			'G' => 'COM_VIRTUEMART_CUSTOM_GROUP',
			'A' => 'COM_VIRTUEMART_CHILD_GENERIC_VARIANT',
			'X' => 'COM_VIRTUEMART_CUSTOM_EDITOR',
			'Y' => 'COM_VIRTUEMART_CUSTOM_TEXTAREA',
			'E' => 'COM_VIRTUEMART_CUSTOM_EXTENSION',
			'R'=>'COM_VIRTUEMART_RELATED_PRODUCTS',
			'Z'=>'COM_VIRTUEMART_RELATED_CATEGORIES'
			);
			if(isset($types[$key_type]))return $types[$key_type];
			else return $types[$key_type];
	}

	/**
	 * Get the params of a plugin with a given id
	 *
	 * @param int $custom_id
	 * @since 1.0
	 */
	public function getCustomfieldParams($custom_id){
		if(empty($custom_id))return array();
		if(empty (self::$_customparams[$custom_id])){
			$db=JFactory::getDbo();
			$q=$db->getQuery(true);
			$q->select('custom_params');
			$q->from('#__virtuemart_customs');
			$q->where('virtuemart_custom_id='.(int)$custom_id);
			$db->setQuery($q);
			$custom_params=$db->loadResult();

			if(empty($custom_params))return false;
			$custom_param_array=explode('|', $custom_params);
			$params_array=array();
			foreach ($custom_param_array as $var){
				$values=explode('=',$var);

				if(isset($values[0])&& isset($values[1])){
					$params_array[$values[0]]=json_decode($values[1]);//removes the double quotes
				}
				unset($values);
			}
			self::$_customparams[$custom_id]=$params_array;
		}
		return self::$_customparams[$custom_id];
	}

	/**
	 * Updates fields in the virtuemart_product_customfields table
	 *
	 * @param 	int		$customfield_id
	 * @param 	string	$field
	 * @param 	mixed 	$value
	 *
	 * @return	mixed	 mixed A database cursor resource on success, boolean false on failure.
	 * @since	1.0
	 */
	public static function updateCustomfield($customfield_id,$field='customfield_params',$value=''){
		if(empty($customfield_id) || empty($field) || empty($value))return false;
		$db=JFactory::getDbo();
		$q=$db->getQuery(true);
		$q->update('#__virtuemart_product_customfields')->set($db->quoteName($field).'='.$db->quote($value))->where('virtuemart_customfield_id='.(int)$customfield_id);
		$db->setQuery($q);
		try
		{
			$result=$db->query();
		}
		catch (RuntimeException $e)
		{
			JError::raiseWarning(500, $e->getMessage());
		}
		return $result;
	}

	/**
	 * Gets the custom fields of a product from the database
	 *
	 * @param 	int 	$product_id
	 * @param 	int 	$custom_id
	 *
	 * @return	JTable	 A database object
	 * @since	1.0
	 */
	public static function getCustomfields($product_id,$custom_id=0){
		if(empty($product_id))return false;
		$db=JFactory::getDbo();
		$q=$db->getQuery(true);
		$q->select('*')->from('#__virtuemart_product_customfields AS pc')->where('virtuemart_product_id='.(int)$product_id);
		if(!empty($custom_id))$q->where('pc.virtuemart_custom_id='.(int)$custom_id);
		$q->leftJoin('#__virtuemart_customs AS customs ON pc.virtuemart_custom_id=customs.virtuemart_custom_id');
		$q->order('pc.ordering ASC');
		$db->setQuery($q);
		try
		{
			$result=$db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			JError::raiseWarning(500, $e->getMessage());
		}
		return $result;
	}

	/**
	 * Saves customfields to a product
	 *
	 * @param 	int $product_id
	 * @param 	array $customsfields
	 *
	 * @todo	It saves the same custom values again and again. Use either the customfield_id or the custom_id-product_id key to track a value and update it
	 *
	 * @since	1.0
	 * @author	Sakis Terz
	 */
	public static function storeCustomFields($product_id,$customsfields){
		$log=array();
		if(!empty($customsfields)){
			$customfieldModel=VmModel::getModel('Customfields');

			foreach ($customsfields as $custom_id=>$customf){

				$data=array();
				$data['virtuemart_product_id']=$product_id;
				$data['virtuemart_custom_id']=$custom_id;
				$data['customfield_value']=$customf['value'];

				//get the existing customfields for that product with that custom_id
				$customfieldz=self::getCustomfields($product_id,$custom_id);

				//exists a record for that product
				if(!empty($customfieldz[0])){
					//same customfield same value. Do nothing
					if($customfieldz[0]->customfield_value==$customf['value'])$result=true;
					//same customfield different value. Update
					else $result=self::updateCustomfield($customfieldz[0]->virtuemart_customfield_id,'customfield_value',$customf['value']);
				}
				//no customfield record. Insert
				else {
					$tableCustomfields = $customfieldModel->getTable('product_customfields');
					$tableCustomfields->setPrimaryKey('virtuemart_product_id');
					$tableCustomfields->_xParams = 'customfield_params';
					$result=$tableCustomfields->bindChecknStore($data);
				}

				if(!$result){
					vmdebug('Stockables - Custom id:'.$custom_id.':'.$customf['value'].' Not Saved to Product:',$product_id);
					//return false;
				}else vmdebug('Stockables - Custom Value:'.$custom_id.':'.$customf['value'].' Saved to Product:'.$product_id);
				if(!empty($tableCustomfields))unset($tableCustomfields);
			}
		}
		return true;
	}

	/**
	 * Gets the custom fields of the a set of products and returns them
	 *
	 * @param 	int	$parent_product_id
	 * @param 	int	$custom_ids
	 *
	 * @return	JTable	 A database object
	 * @since	1.0
	 */
	public static function getChildCustomFields($product_ids,$custom_id){
		if(empty($product_ids) || empty($custom_id))return false;
		$db=JFactory::getDbo();
		$q=$db->getQuery(true);
		$q->select('pcf.virtuemart_customfield_id, pcf.virtuemart_product_id, pcf.virtuemart_custom_id, pcf.customfield_value AS value')
		->from('#__virtuemart_product_customfields AS pcf')
		->where('pcf.virtuemart_product_id IN ('.implode(',', $product_ids).') AND pcf.virtuemart_custom_id='.(int)$custom_id)
		->order('FIELD(pcf.virtuemart_product_id, '.implode(',', $product_ids).'),pcf.ordering');
		$db->setQuery($q);
		try
		{
			$result=$db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			JError::raiseWarning(500, $e->getMessage());
		}
		return $result;
	}

	/**
	 * Remove duplicate records based on a key
	 *
	 * @param 	array $objects		The array of the obejects to be checked
	 * @param 	string $filter_key	The key based on which will happen the filtration
	 *
	 * @since	1.0
	 */
	public static function filterUniqueValues($objects,$filter_key='value'){
		$new_array=array();
		$value_array=array();
		foreach ($objects as $key=>$ob){
			if(in_array($ob->$filter_key, $value_array)){
				unset($objects[$key]);
				continue;
			}
			$value_array[$key]=$ob->$filter_key;
		}
		print_r($objects); echo '<br/>'	, '<br/>';
		return $objects;
	}
}