/**
 * Calls a function to the parent window to add an element to the list
 * @param string	field1	The 1st field
 * @param string	field2	The 2nd field
 * @param int		id		The id of the element
 */
function addToList(field1,field2,id){
	if (window.parent) window.parent.jSelectElement(field1,field2,id);	
	setSelectedElement(id);
}

/**
 * Calls a function to the parent window to remove an element from the list
 * @param int		id		The id of the element
 */
function removeFromList(id){
	if (window.parent) window.parent.removeProduct(id);	
	unsetSelectedElement(id);
}

function unsetSelectedElement(id){
	jQuery('#element_'+id).removeClass('selected_element');
}

function setSelectedElement(id){
	jQuery('#element_'+id).addClass('selected_element');
}

//set the required class to the selected products
jQuery(document).ready(function(){
	var selectedElements=window.parent.selectedElements;
	jQuery.each(selectedElements,function(){
		setSelectedElement(this);
	})
});