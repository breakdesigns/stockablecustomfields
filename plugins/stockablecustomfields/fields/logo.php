<?php
/**
 * @version		$Id: logo.php 2014-12-18 19:12 sakis Terz $
 * @package		stockablecustomfields
 * @copyright	Copyright (C)2015 breakdesigns.net . All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');


/**
 *
 * Class that generates a product's list
 * @todo	When VM will replace params with fields in the plugin's XML, replace that class with a JFormField Class
 * @author 	Sakis Terzis
 */
Class JFormFieldLogo extends JFormField{
	function getInput()
	{		
		$html='
		<div style="margin-bottom:30px;">
		<img src="'.JURI::root().'/plugins/vmcustom/stockablecustomfields/assets/images/logo-64.png"/>
		<h3 style="display:inline-block; padding:10px 0px 5px 5px; ">Stockable Customfields</h3>
		</div>';
		return $html;
	}
}