<?php
/**
 * @package stockablecustomfields
 * @copyright   Copyright (C)2014-2020 breakdesigns.net . All rights reserved.
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

 if (!defined('_JEXEC')) die;

 use Joomla\Utilities\ArrayHelper;
 use Joomla\CMS\Factory;
 use Joomla\CMS\Plugin\PluginHelper;

/**
 *
 * Class that contains the necessary functions used by the customfield
 * @package		stockablecustomfields
 * @since 1.0
 *
 */
Class CustomfieldStockablecustomfields
{
	protected $_custom_id;
	protected static $instances;
	protected static $_customparams;

    /**
     * @var string
     * @since 1.5.1
     */
	protected static $defaultLang;

    /**
     * Constructor
     *
     * @param int $_custom_id
     * @since    1.0
     */
    public function __construct($_custom_id)
    {
        $this->_custom_id = (int)$_custom_id;
    }

    /**
     * Get the singleton customfield instance
     *
     * @param int $custom_id
     * @return CustomfieldStockablecustomfields
     * @since 2.0
     */
    public static function getInstance($custom_id)
    {
        if (empty(self::$instances[$custom_id])) {
            self::$instances[$custom_id] = new CustomfieldStockablecustomfields($custom_id);
        }
        return self::$instances[$custom_id];
    }

    /**
     * Get a custom record from the db
     *
     * @param int $custom_id
     * @return    object    The custom record
     * @since    1.0
     */
    public static function getCustom($custom_id)
    {
        $db = Factory::getDbo();
        $q = $db->getQuery(true);
        $q->select('*')->from('#__virtuemart_customs ')->where('virtuemart_custom_id=' . (int)$custom_id);
        $db->setQuery($q);
        $result = $db->loadObject();
        return $result;
    }

    /**
     *
     * Returns the lang string of the custom type
     *
     * @param string $key_type
     * @return    string
     * @since    1.0
     */
    static function getCustomTypeName($key_type)
    {
        $types = array(
            'S' => 'COM_VIRTUEMART_CUSTOM_STRING',
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
            'R' => 'COM_VIRTUEMART_RELATED_PRODUCTS',
            'Z' => 'COM_VIRTUEMART_RELATED_CATEGORIES'
        );
        if (isset($types[$key_type])) {
            return $types[$key_type];
        } else {
            return '';
        }
    }

    /**
     * Tracks which plugins can be used as stockables
     *
     * @return array
     * @throws Exception
     * @since 1.0
     */
    public static function getCompatiblePlugins()
    {
        PluginHelper::importPlugin('vmcustom');
        $compatibles = Factory::getApplication()->triggerEvent('onDetectStockables', array());
        return $compatibles;
    }

    /**
     * Get the params of a plugin with a given id
     *
     * @param int $custom_id
     * @return array
     * @since 1.0
     */
    public function getCustomfieldParams($custom_id)
    {
        if (empty($custom_id)) {
            return [];
        }
        if (empty (self::$_customparams[$custom_id])) {
            $custom = self::getCustom($custom_id);
            $custom_params = $custom->custom_params;

            if (empty($custom_params)) {
                return false;
            }
            $custom_param_array = explode('|', $custom_params);
            $params_array = array();
            foreach ($custom_param_array as $var) {
                $values = explode('=', $var);

                if (isset($values[0]) && isset($values[1])) {
                    $params_array[$values[0]] = json_decode($values[1]);//removes the double quotes
                }
                unset($values);
            }
            self::$_customparams[$custom_id] = $params_array;
        }
        return self::$_customparams[$custom_id];
    }

    /**
     * Updates fields in the virtuemart_product_customfields table
     *
     * @param int $customfield_id
     * @param string $field
     * @param mixed $value
     * @return    mixed     mixed A database cursor resource on success, boolean false on failure.
     * @since    1.0
     */
    public static function updateCustomfield($customfield_id, $field = 'customfield_params', $value = '')
    {
        if (empty($customfield_id) || empty($field) || empty($value)) {
            return false;
        }
        $db = Factory::getDbo();
        $q = $db->getQuery(true);
        $q->update('#__virtuemart_product_customfields')->set($db->quoteName($field) . '=' . $db->quote($value))->where('virtuemart_customfield_id=' . (int)$customfield_id);
        $db->setQuery($q);
        try {
            $result = $db->execute();
        } catch (\RuntimeException $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * Gets the custom fields of product/s from the database
     *
     * @param mixed $product_id Int or Array of integers
     * @param mixed $custom_id Int or Array og integers
     * @param int $limit
     * @return    JTable     A database object
     * @since    1.0
     */
    public static function getCustomfields($product_id = 0, $custom_id = 0, $limit = false)
    {
        if (empty($product_id) && empty($custom_id)) {
            return false;
        }
        $db = Factory::getDbo();
        $q = $db->getQuery(true);
        $q->select('*,pc.virtuemart_customfield_id AS id,pc.customfield_value AS value')->from('#__virtuemart_product_customfields AS pc');
        if (!empty($product_id)) {
            if (is_array($product_id)) {
                $product_id = ArrayHelper::toInteger($product_id);
                $q->where('virtuemart_product_id IN(' . implode(',', $product_id) . ')');
            } else {
                $q->where('virtuemart_product_id=' . (int)$product_id);
            }
        }
        if (!empty($custom_id)) {
            if (is_array($custom_id)) {
                $custom_id = ArrayHelper::toInteger($custom_id);
                $q->where('pc.virtuemart_custom_id IN(' . implode(',', $custom_id) . ')');
            } else {
                $q->where('pc.virtuemart_custom_id=' . (int)$custom_id);
            }
        }

        $q->leftJoin('#__virtuemart_customs AS customs ON pc.virtuemart_custom_id=customs.virtuemart_custom_id');
        if (is_array($product_id)) {
            $q->order('FIELD(pc.virtuemart_product_id, ' . implode(',', $product_id) . '),pc.ordering');
        } else {
            $q->order('pc.ordering ASC');
        }
        $db->setQuery($q, $offset = false, $limit);

        try {
            $result = $db->loadObjectList();
        } catch (\RuntimeException $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * Saves customfields to a product
     *
     * @param int $product_id
     * @param array $customsfields
     * @return array|bool|mixed
     * @throws Exception
     * @since 1.0
     */
	public static function storeCustomFields($product_id, $customsfields)
    {
        $result = false;
        if (! empty($customsfields)) {
            $customfieldModel = \VmModel::getModel('Customfields');
            $positive_storage = 0;
            foreach ($customsfields as $custom_id => $customf) {
                $custom = self::getCustom($custom_id);
                $data = array();
                $data['virtuemart_product_id'] = $product_id;
                $data['virtuemart_custom_id'] = $custom_id;
                if (! empty($customf['value'])) {
                    $data['customfield_value'] = $customf['value'];
                }
                // get the existing customfields for that product with that custom_id
                $customfieldz = self::getCustomfields($product_id, $custom_id);

                // exists a record for that product
                if (! empty($customfieldz[0])) {
                    $data['virtuemart_customfield_id'] = $customfieldz[0]->virtuemart_customfield_id;

                    if ($custom->field_type != 'E') {
                        // same customfield same value. Do nothing
                        if ($customfieldz[0]->customfield_value == $customf['value']) {
                            $result = true;
                        }
                        // same customfield different value. Update
                        else {
                            $result = self::updateCustomfield($customfieldz[0]->virtuemart_customfield_id, 'customfield_value', $customf['value']);
                        }
                    }
                }                 // no customfield record. Insert
                else {
                    $tableCustomfields = $customfieldModel->getTable('product_customfields');
                    $tableCustomfields->setPrimaryKey('virtuemart_product_id');
                    $tableCustomfields->_xParams = 'customfield_params';
                    $result = $tableCustomfields->bindChecknStore($data);
                }

                if ($custom->field_type == 'E') {
                    PluginHelper::importPlugin('vmcustom');
                    $result = Factory::getApplication()->triggerEvent('plgVmOnStockableSave', array(
                        $data,
                        $customf
                    ));
                }

                if (! $result) {
                    \vmdebug('Stockables - Custom id:' . $custom_id . ':' . $customf['value'] . ' Not Saved to Product:', $product_id);
                    // return false;
                } else {
                    $positive_storage ++;
                    \vmdebug('Stockables - Custom Value:' . $custom_id . ':' . $customf['value'] . ' Saved to Product:' . $product_id);
                }
                if (! empty($tableCustomfields)) {
                    unset($tableCustomfields);
                }
            }
            if ($positive_storage == count($customsfields) - 1) {
                $result = true;
            }
        }
        return $result;
    }

	/**
	 * Check and return orderable products (have stock etc based on the VM config)
	 *
	 * @param 	array $product_ids
	 *
	 * @return	array the the product objects or the product ids
	 * @since	1.0
	 */
	public static function getOrderableProducts($product_ids, $custom_params, $exclude=false)
	{
        $product_ids = ArrayHelper::toInteger($product_ids);

        // get the default language to get the product name
        $defaultLanguageTag = self::getDefaultLangTag();
        $currentLanguage = Factory::getLanguage()->getTag();
        $vmLanguages = \VmConfig::get('active_languages', array(
            $defaultLanguageTag
        ));

        $db = Factory::getDbo();
        $q = $db->getQuery(true);

        $q->select('p.virtuemart_product_id, p.`product_in_stock` - p.`product_ordered` AS stock')
            ->from('#__virtuemart_products AS p')->where('p.published=1');

        //stock management when it's not catalogue
        if (!VmConfig::get('use_as_catalog', 0) && (VmConfig::get('stockhandle', 'none') == 'disableit' || $custom_params['outofstockcombinations'] == 'hidden')) {

            /*
             * we may want to exclude a product no matter it has stock
             * this used mainly when the parent is stockable. We want to display it's combination
             */
            if (!empty($exclude)) {
                $q->where('(p.`product_in_stock` - p.`product_ordered` >0 OR p.`virtuemart_product_id`=' . (int)$exclude . ')');
            }
            else {
                $q->where('p.`product_in_stock` - p.`product_ordered` >0');
            }
        }
        $q->where('p.virtuemart_product_id IN(' . implode(',', $product_ids) . ')');

        // Get the name only if the current language is valid
        if(in_array($currentLanguage, $vmLanguages)) {
            $db_language_suffix = strtolower(str_replace('-', '_', $currentLanguage));
        }
        else {
            $db_language_suffix = strtolower(str_replace('-', '_', $defaultLanguageTag));
        }

        $q->leftJoin('#__virtuemart_products_' . $db_language_suffix . ' AS l ON p.virtuemart_product_id=l.virtuemart_product_id');
        $q->select('l.product_name AS product_name');

        //shopper groups
        $q->leftJoin('`#__virtuemart_product_shoppergroups` as ps ON p.`virtuemart_product_id` = ps.`virtuemart_product_id`');
        $usermodel = \VmModel::getModel('user');
        $currentVMuser = $usermodel->getCurrentUser();
        $virtuemart_shoppergroup_ids = (array)$currentVMuser->shopper_groups;
        $virtuemart_shoppergroup_ids = ArrayHelper::toInteger($virtuemart_shoppergroup_ids);

        if (is_array($virtuemart_shoppergroup_ids) && !empty($virtuemart_shoppergroup_ids)) {
            $q->where('(ps.`virtuemart_shoppergroup_id` IS NULL OR ps.`virtuemart_shoppergroup_id` IN(' . implode(',', $virtuemart_shoppergroup_ids) . '))');
        } else {
            $q->where('ps.`virtuemart_shoppergroup_id` IS NULL');
        }

        $quoted_product_ids = array_map(function ($n) {
            $db = Factory::getDbo();
            return $db->quote($n);
        }, $product_ids);

        $q->order('FIELD(p.virtuemart_product_id, ' . implode(',', $quoted_product_ids) . ')');
        $db->setQuery($q);

        try {
            $result = $db->loadAssocList('virtuemart_product_id');
        } catch (\RuntimeException $e) {
            throw $e;
        }

        return $result;
    }

	/**
	 * Remove duplicate records based on a key
	 *
	 * @param 	array $objects		The array of the obejects to be checked
	 * @param 	string $filter_key	The key based on which will happen the filtration
	 * @return	array an array with the filtered objects
	 * @since	1.0
	 */
	public static function filterUniqueValues($objects,$filter_key='value')
	{
		$new_array=array();
		$value_array=array();
		foreach ($objects as $key=>$ob) {
			if(in_array($ob->$filter_key, $value_array)){
				unset($objects[$key]);
				continue;
			}
			$value_array[$key]=$ob->$filter_key;
		}
		//rearanges the indexes
		if (!empty($objects)) {
		    $objects=array_values($objects);
        }
		return $objects;
	}


    /**
     * Creates arrays with the customfield combinations that generate a product
     *
     * @param array $customfields
     * @param array $product_array An associative array with the products, using as key the product id
     * @return    array
     * @since    1.0
     */
    public static function getProductCombinations($customfields, $product_array)
    {
        $products = array();
        $products_final = array();
        $custom_values = array();

        foreach ($customfields as $cf) {
            /**
             * This is a workaround
             * Unfortunately the VM native custom fields use the same table for storing the custom_value and the product_id
             * That means that in case we have the same value repeated several times (e.g. color:white), this value has different id each time
             * Since we can display the value only once in the FE (e.g. color:white) we are using the the 1st found customfield_id for that value
             */
            if (!isset($custom_values[$cf->virtuemart_custom_id])) {
                $custom_values[$cf->virtuemart_custom_id] = array();
            }
            if (!in_array($cf->value, $custom_values[$cf->virtuemart_custom_id])) {
                $custom_values[$cf->virtuemart_custom_id][$cf->id] = $cf->value;
                $id = (string)$cf->id;
            } else {
                $id = (string)array_search($cf->value, $custom_values[$cf->virtuemart_custom_id]);
            }
            if (!isset($products[$cf->virtuemart_product_id])) {
                $products[$cf->virtuemart_product_id] = array();
            }
            if (!in_array($cf->id, $products[$cf->virtuemart_product_id])) {
                $products[$cf->virtuemart_product_id][] = $id;
            }
        }
        //change the form to be easier to handle as json object
        foreach ($products as $pid => $p_array) {
            $product_name = isset($product_array[$pid]['product_name']) ? $product_array[$pid]['product_name'] : '';
            $products_final[] = array('product_id' => $pid, 'customfield_ids' => $p_array, 'stock' => $product_array[$pid]['stock'], 'product_name' => $product_name);
        }

        $return = new \stdClass();
        $return->combinations = $products_final;
        return $return;
    }

    /**
     * Get the shop's default language
     *
     * @param string $lang
     * @return Language
     * @since 1.5.1
     */
    public static function getDefaultLangTag($lang = null)
    {
        if (self::$defaultLang == null) {
            if ($lang == null) {
                self::$defaultLang = isset(\VmConfig::$jDefLangTag) ? \VmConfig::$jDefLangTag : (Factory::getLanguage()->getDefault());
            } else {
                self::$defaultLang = $lang;
            }
        }
        return self::$defaultLang;
    }
}

