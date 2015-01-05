function jSelectElement(field1,field2,id){
	//check if exists
	if(selectedElements.indexOf(parseInt(id))<0){
		selectedElements.push(parseInt(id));
		var header=jQuery('.elements_header');
		var list=jQuery('#elements_list');
		header.css('display','block');
		list.css('display','block');
		var html='<li class="bd_element" id="element_'+id+'"><span class="element_name">'+field1+'</span><span class="element_type">'+field2+'</span><span class="element_id">'+id+'</span><input type="hidden" name="custom_id[]" value="'+id+'"/>';
		html+='<span class="bd_listtoolbar">';		
		html+='<span class="breakdesigns_btn element_move_btn" title="Drag to Move"><i class="bdicon-move"></i></span>';
		html+='<span class="breakdesigns_btn element_delete_btn" title="Remove"><i class="bdicon-cancel"></i></span>';
		html+='</span>';
		html+='</li>';
		list.append(html);
	}	
}

function removeProduct(id){	
	selectedElements = jQuery.grep(selectedElements, function( value ) {
		return  value!= id;
	});
	console.log(selectedElements);
	jQuery('#element_'+id).remove();
}

