(function($){
	"use strict";

	var oldGetImageFunction = jpibfi.fn.getImageUrl;

	jpibfi.fn.getImageUrl = function( $image) {
		var $parent = $image.parents('a').first();
		if ( $parent.length ) {
			var href = $parent.attr('href');
			var useUrl = !!href;
			useUrl = useUrl && fileExtensionAllowed( getFileExtension( href ), jpibfi.settings.pinFullImages.fileExtensions );
			useUrl = useUrl && ( jpibfi.settings.pinFullImages.checkDomain == "false" || inCurrentDomain( href ) );
			if ( useUrl) {
				return href;
			}
		}
		return oldGetImageFunction( $image );
	};

	function getFileExtension( url )	{
		var parts = url.split('.');
		if ( parts.length == 1 )
			return '';
		else
			return parts.pop().split(/\#|\?/)[0];
	}

	function fileExtensionAllowed( extension, listOfExtensions) {
		var extensions = listOfExtensions.split(',');

		for(var i = 0; i < extensions.length; i++) {
			if (extensions[i] == '*' || extensions[i] == extension)
				return true;
		}
		return false;
	}

	function inCurrentDomain( url ) {
		var currentDomain = document.domain.replace(/^www./,""); //delete www in the beginning
		return url.indexOf( currentDomain ) != -1;
	}

	$(document).ready( function() {	});
})(jQuery);