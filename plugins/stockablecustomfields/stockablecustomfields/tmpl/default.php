<?php
/**
 * @version		$Id: default.php 2014-12-15 20:10 sakis Terz $2
 * @package		stockablecustomfield
 * @copyright	Copyright (C) 2014-2015 breakdesigns.net . All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

if (!defined('_JEXEC')) die;

$options=$viewData->options;
$field_name = 'customProductData['.$viewData->product->virtuemart_product_id.']['.$viewData->virtuemart_custom_id.']['.$viewData->virtuemart_customfield_id .'][stockable]';
$select_id='stockableselect'.$viewData->product->virtuemart_product_id.$viewData->virtuemart_customfield_id.$viewData->custom->virtuemart_custom_id;
$wrapper_id='stockablecustomfields_field_wrapper_'.$viewData->virtuemart_customfield_id;
?>

<div class="stockablecustomfields_field_wrapper control-group" id="<?php echo $wrapper_id?>">
<?php
$fist_option=array();
$selects=array();
//empty options should exist only on the parent product loading
if($viewData->product->product_parent_id==0 && !$viewData->isderived)
$fist_option=array('value'=>0, 'text'=>JText::_('PLG_STOCKABLECUSTOMFIELDS_SELECT_OPTION'));

foreach ($options as $key=>$v) {
	$label=JText::_($v->value);
	$selects[] = array('value' => $v->id, 'text' =>$label );
	if(!empty($v->selected))$selected=$key;
}
if(isset($selected))$selected=$selected+1;
else $selected=0;

if(!empty($fist_option))array_unshift($selects,$fist_option);
if(!empty($selects)){?>
<div>
	<?php 
	echo JHTML::_('select.genericlist', $selects,$field_name,'','value','text',$selects[$selected],$id=$select_id,true);?>
</div>
<?php 
}?>
</div>
