// arr icons for item states
const icons = {
	"0"			: "<svg class=\"icon icon-cross\"><use xlink:href=\"assets/symbol-defs.svg\#icon-cross\"></use></svg>",
	"1"			: "<svg class=\"icon icon-checkmark\"><use xlink:href=\"assets/symbol-defs.svg\#icon-checkmark\"></use></svg>",
	"loading"	: "<svg class=\"icon icon-spinner6\"><use xlink:href=\"assets/symbol-defs.svg\#icon-spinner6\"></use></svg>"
};

// get current url - this *should* let us define the complete url to the ajax file without having to define it with PHP or hard code.
let currUrl = document.URL,
    newUrl;
if (currUrl.charAt(currUrl.length - 1) === '/') {
    newUrl = currUrl.slice(0, currUrl.lastIndexOf('/'));
    newUrl = newUrl.slice(0, newUrl.lastIndexOf('/')) + '/';
} else {
    newUrl = currUrl.slice(0, currUrl.lastIndexOf('/')) + '/';
}
// define ajax url - replace current url admin dir with ajax dir and add ajax file 
urlAjax=newUrl.replace("ac-admin", "ac-ajax")+"admin.ajax.php";


// update item state via AJAX
var updateState = function() {
	let data = { 
		action	: "mod-state",
		type 	: ""+this.getAttribute("data-type")+"",
		id		: ""+this.getAttribute("data-id")+"",
		state	: ""+this.getAttribute("data-state")+""
	};
	// replace with spinner
	this.innerHTML = icons["loading"];
	
	// ajax fetch
	fetch(urlAjax, {
	    method 		: "POST",
		mode		: "same-origin",
		credentials	: "same-origin",
	    headers		: {
	    	"Content-Type": "application/json"
	    },
	    body 		: JSON.stringify(data)	
	}).then(
		response => response.text()
	).then(
		html => this.innerHTML = icons[html]
	);
};

// once doc has loaded
document.addEventListener("DOMContentLoaded", function(){
	// get all items with update-state class
	var $elStates = document.getElementsByClassName("update-state");
	
	// add click event to state elements
	for (var i = 0; i < $elStates.length; i++) {
	    $elStates[i].addEventListener("click", updateState, false);
	}
});