<?php
$options=$viewData->options;
$field_name = 'customProductData['.$viewData->product->virtuemart_product_id.']['.$viewData->virtuemart_custom_id.']['.$viewData->virtuemart_customfield_id .']['.$viewData->custom->virtuemart_custom_id.'][stockable]';
$select_id='stockableselect'.$viewData->product->virtuemart_product_id.$viewData->virtuemart_customfield_id.$viewData->custom->virtuemart_custom_id;
$wrapper_id='stockablecustomfields_field_wrapper_'.$viewData->virtuemart_customfield_id;
?>

<div class="stockablecustomfields_field_wrapper control-group" id="<?php echo $wrapper_id?>">
<?php
$fist_option=array();
$selects=array();
//empty options should exist only on the parent product loading
//if($viewData->product->product_parent_id==0)
$fist_option=array('value'=>0, 'text'=>JText::_('PLG_STOCKABLECUSTOMFIELDS_SELECT_OPTION'));
foreach ($options as $v) {
	$label=JText::_($v->value);
	$selects[] = array('value' => $v->id, 'text' =>$label );
}
if(!empty($fist_option))array_unshift($selects,$fist_option);
if(!empty($selects)){?>
<label for="<?php echo $select_id?>"><?php echo JText::_($viewData->custom->custom_title)?></label>
<div class="controls">
	<?php 
	echo JHTML::_('select.genericlist', $selects,$field_name,'','value','text',$selects[0],$id=$select_id,true);?>
</div>
<?php 
}?>
</div>
