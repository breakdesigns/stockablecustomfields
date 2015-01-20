<?php
/**
 * @package stockablecustomfields
 * @version $Id: fields/customs.php  2014-12-18 sakisTerzis $
 * @author Sakis Terzis (sakis@breakDesigns.net)
 * @copyright	Copyright (C) 2014 breakDesigns.net. All rights reserved
 * @license	GNU/GPL v2
 */

defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.access.access');
jimport('joomla.form.formfield');
JHtml::_('behavior.framework', true);
JHtml::_('behavior.modal');

if(!class_exists('CustomfieldStockablecustomfields'))require_once(JPATH_PLUGINS.DIRECTORY_SEPARATOR.'vmcustom'.DIRECTORY_SEPARATOR.'stockablecustomfields'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'customfield.php'); 

/**
 *
 * Class that generates a filter list
 * @author Sakis Terzis
 */
Class JFormFieldCustoms extends JFormField{
	/**
	 * Method to get the field input markup.
	 *
	 *
	 * @return	string	The field input markup.
	 * @since	1.6
	 */
	protected function getInput()
	{
		$jinput=JFactory::getApplication()->input;
		$virtuemart_custom_id=$jinput->get('virtuemart_custom_id',array(),'ARRAY');
		if(is_array($virtuemart_custom_id))$virtuemart_custom_id=end($virtuemart_custom_id);
		if (empty($virtuemart_custom_id)) return '<div class="alert alert-info"><span>'.JText::_('PLG_STOCKABLECUSTOMFIELDS_SAVE_CUSTOMFIELD_BEFORE_ADDING_CUSTOMS').'</span></div';
				
		$document=JFactory::getDocument();
		$document->addStyleSheet( JURI::root(true).'/plugins/vmcustom/stockablecustomfields/assets/css/stockables_be.css');
		$document->addStyleSheet( JURI::root(true).'/plugins/vmcustom/stockablecustomfields/assets/css/mybootstrap.css');
		$document->addScript(JURI::root(true).'/plugins/vmcustom/stockablecustomfields/assets/js/backend.js');
		$selectedElements=array();	
		
		
		
		if(!empty($this->value))$selectedElements=$this->value; 
		$display=empty($selectedElements)?'none':'block';
		$html='';
		$html.='
		<div id="elements_wrapper">
			<div class="elements_header" style="display:'.$display.'">
				<span class="element_name">'.JText::_('COM_VIRTUEMART_CUSTOM_TITLE').'</span>
				<span class="element_type">'.JText::_('COM_VIRTUEMART_CUSTOM_FIELD_TYPE').'</span>
				<span class="element_id">'.JText::_('COM_VIRTUEMART_ID').'</span>
			</div>';

		$html.='<ul class="sortable" id="elements_list" style="display:'.$display.'">';
		
		//iterate to print the elements
		if(!empty($selectedElements) && is_array($selectedElements)){
			$isAssignedToProduct=CustomfieldStockablecustomfields::getCustomfields($product=false,$virtuemart_custom_id,$limit=1); 
			foreach ($selectedElements as $el){
				//get the custom		
				$customObject=CustomfieldStockablecustomfields::getCustom($el);
				$html.='
				<li cclass="bd_element" id="element_'.$el.'">
					<span class="element_name">'.JText::_($customObject->custom_title).'</span>
					<span class="element_type">'.JText::_(CustomfieldStockablecustomfields::getCustomTypeName($customObject->field_type)).'</span>
					<span class="element_id">'.$el.'</span>
					<input type="hidden" name="custom_id[]" value="'.$el.'"/>
					<span class="bd_listtoolbar">						
						<span class="breakdesigns_btn element_move_btn" title="Drag to Move"><i class="bdicon-move"></i></span>';
				//if there are assignments cannot change the custom fields
				if(empty($isAssignedToProduct))$html.='<span class="breakdesigns_btn element_delete_btn" title="Remove"><i class="bdicon-cancel"></i></span>';		
				$html.='	
					</span>
				</li>';
			}
		}
		
		
		$html.='</ul>';
		
		//if there are assignments cannot change the custom fields
		if(empty($isAssignedToProduct)){
			$html.='
			<div class="elements_toolbar">			
				<a class="modal btn" role="modal" data-toggle="modal" title="'.JText::_('PLG_STOCKABLECUSTOMFIELDS_ADD_CUSTOMS_DESC').'"
				href="index.php?option=com_virtuemart&view=custom&layout=stockables&tmpl=component&function=jSelectCustom"
				onclick="return false;" rel="{handler: \'iframe\', size: {x: 820, y: 550}}">
				<i class="bdicon-plus-circled"></i>'.
			JText::_('PLG_STOCKABLECUSTOMFIELDS_ADD_CUSTOMS_LABEL')
			.'</a>
			</div>';
		}else{
			$html.='<div class="alert alert-info"><span>'.JText::_('PLG_STOCKABLECUSTOMFIELDS_CANNOT_CHANGE_CUSTOMS_IF_ASSIGNED').'</span><div>';
		}

		$html.='</div>';

		$script='
		<script type="text/javascript">
			jQuery("#elements_list").sortable({handle: ".element_move_btn"});
			jQuery("#elements_list").delegate("span.element_delete_btn","click",function(){
				var product_id=jQuery(this).parent().parent("li").find("input").attr("value");
				removeProduct(product_id);
			});
			var selectedElements=Array('.implode(',', $selectedElements).'); 
		</script>';

		$html=$html.$script;


		return $html;
	}

}
