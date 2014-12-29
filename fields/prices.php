<?php
/**
 * @version		$Id: prices.php 2014-04-24 19:12 sakis Terz $
 * @package		productbundles
 * @copyright	Copyright (C)2014 breakdesigns.net . All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');
if(!class_exists('BundleCustomfield'))require_once(JPATH_PLUGINS.DIRECTORY_SEPARATOR.'vmcustom'.DIRECTORY_SEPARATOR.'productbundles'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'customfield.php');

/**
 *
 * Class that generates a prices list
 * @todo	When VM will replace params with fields in the plugin's XML, replace that class with a JFormField Class
 * @author 	Sakis Terzis
 */
Class JElementPrices extends JElement{
	function fetchElement($fieldname='', $selected='', &$node='', $control_name='')
	{	
		if($selected=='')$selected=array('salesPrice');
		if(!is_array($selected))(array)$selected;
		$price_types=array(
		'COM_VIRTUEMART_PRODUCT_BASEPRICE'=>'basePrice',
		'COM_VIRTUEMART_PRODUCT_BASEPRICE_WITHTAX'=>'basePriceWithTax',
		'COM_VIRTUEMART_PRODUCT_SALESPRICE_WITH_DISCOUNT'=>'salesPriceWithDiscount',
		'COM_VIRTUEMART_PRODUCT_SALESPRICE'=>'salesPrice',
		'COM_VIRTUEMART_PRODUCT_SALESPRICE_WITHOUT_TAX'=>'discountedPriceWithoutTax',
		);
		
		ob_start();
		?>
		<select class="vm-chzn-select" name="price_type[]" multiple>
		<?php 
		foreach ($price_types as $key=>$value){
			$checked='';
			if(in_array($value, $selected))$checked='selected';?>
					
			<option value="<?php echo $value?>" <?php echo $checked?>><?php echo JText::_($key);?></option>
							
		<?php 
		}
		?>
		</select>
		<?php 
		$html=(string)ob_get_clean();
		return $html;
	}
}