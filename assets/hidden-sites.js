
jQuery( document ).ready(function() {	
	watchHiddenBoxes(onOpen());
});


function watchHiddenBoxes(theIds){
	jQuery('.hidden-list input[type="checkbox"]').change(function() {
    	var theId = jQuery(this).val();
    	if (theIds.includes(theId)) {
    		onChange();
    	} else {
    		onChange();
    	}	
	})
}


function onOpen(){
	var hiddenSites = document.getElementById('my_hidden_sites');
	var blogIds = jQuery(hiddenSites).val();
	console.log(blogIds);
	var theIds = blogIds.split(',');
	for (let i=0; i<theIds.length; i++) {
	  jQuery( "#blog-"+theIds[i] ).prop( "checked", true );
	  console.log('#blog-'+theIds[i]);	  
	}
	return theIds;
}

function onChange(){
	var checked = jQuery('.hidden-list input[type="checkbox"]:checked');
	var newIds = [];
	for (let i=0; i<checked.length; i++) {
	  newIds.push(jQuery(checked[i]).attr('value')); 
	}
	var hiddenSites = document.getElementById('my_hidden_sites');
	 hiddenSites.value = newIds.join();
}

