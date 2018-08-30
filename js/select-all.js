//This file facilitates the "Select All" checkbox feature.

jQuery(document).ready(function($) {

	$('input#select_all').click(function() {   
		if(this.checked) {
			$('tr#gaddon-setting-row-field_choices input').each(function() {
				this.checked = true;       
				$(this).siblings("input[type=hidden]").val(1);
				
			});
		}
		else {
			$('tr#gaddon-setting-row-field_choices input').each(function() {
				this.checked = false;
				$(this).siblings("input[type=hidden]").val(0);				
			});
		}
	});

});