
/**
 * The following lines define some things in the Clockwork name space so this
 *  file can be used by it self (like on the AMM login screen) and not show
 *  any warnings.
**/
Clockwork = typeof Clockwork !== 'undefined' ? Clockwork : {};
Clockwork.forms = typeof Clockwork.forms !== 'undefined' ? Clockwork.forms : {};
CW.config = typeof CW.config !== 'undefined' ? CW.config : {};


/*
*	use with onKeyPress event to e.g. submit a form
*/
function do_on_enterkey( event, object, func ) {
	var code = 0;

	if (document.layers) {
		code = event.which; /* NS4 */
	}
	else {
		code = event.keyCode;
	}
	if (code==13) {
		if ( func ) {
			Event.stop( event );
			return func( );
		}
		else {
			object.form.submit( );
		}
	}
}


function submit_once(formObject, confMsg) {
	if ( formObject.alreadySubmitted ) {
		alert('Your request had already been submitted, please be patient.');
		window.location = document.URL;
	} else {
		formObject.alreadySubmitted = true;
		//  this onsubmit() is extremely necessary for the rich text fields: if omitted, their content will not be saved
		if ( formObject.onsubmit ) {
			formObject.onsubmit( );
		}

		formObject.submit();
	}
}


function pad_number( num, total_length ) {

	var new_number = num.toString( );

	while ( new_number.length < total_length ) {
		new_number = '0' + new_number;
	}

	return new_number;
}


function toggle_visibility ( element_id ) {

	var element  =  document.getElementById( element_id );

	if ( ! element ) {
		return false;
	}

	if ( element.style.display == "none" ) {
		element.style.display  =  "";
	}
	else {
		element.style.display  =  "none";
	}

	return false;

}

function get_selected(el) {
	if (el.selectedIndex == -1) return '';
	return el.options[el.selectedIndex].value;
}

function get_selected_text(el) {
	if (el.selectedIndex == -1) return '';
	return el.options[el.selectedIndex].text;
}

function set_selected(el, value) {
	if (!el || ! el.options ) return;

	for ( var i=0; i < el.options.length; i++ ) {
		if ( el.options[i].value == value ) {
			el.selectedIndex = i;
			el.options[i].selected = 'selected';
			break;
		}

	}
}


/* Javascript implementation of Logger */

var Logger  =  { };

if ( typeof Clockwork !== "undefined" ) {
	Logger.log_level  =  CW.config.logger_level ? CW.config.logger_level : CW_LOG_PRODUCTION_LEVEL;
}

Logger.log  =  function ( message, level ) {

	if ( ! level ) {
		level  =  CW_LOG_DEFAULT_LEVEL;
	}

	if ( Logger.log_level & level ) {
		var now  =  new Date( );
		message  =  '[' + now.toString( ) + '] ' + message;

		try {
			console.log( message );
		}
		catch ( e ) {
			alert( message );
		}
	}
};

Logger.set_checkpoint  =  function ( message, level ) {
	var new_checkpoint  =  ( new Date( ) ).getTime( );
	if ( message ) {
		Logger.log( message + '[elapsed: ' + ( ( new_checkpoint - Logger.checkpoint ) / 1000.0 ) +
					'; cumulative: ' + ( ( new_checkpoint - Logger.first_checkpoint ) / 1000.0 ) + ']', level );
	}
	Logger.checkpoint  =  new_checkpoint;
};


/* Removes a single GET parameter from an URL.  
* Useful for removing problematic parameters when making 
* an ajax POST request to the same URL.
*/


function removeParameter(url, parameter)
{
  var urlparts= url.split('?');

  if (urlparts.length>=2)
  {
      var urlBase=urlparts.shift(); //get first part, and remove from array
      var queryString=urlparts.join("?"); //join it back up

      var prefix = encodeURIComponent(parameter)+'=';
      var pars = queryString.split(/[&;]/g);
      for (var i= pars.length; i-->0;)               //reverse iteration as may be destructive
          if (pars[i].lastIndexOf(prefix, 0)!==-1)   //idiom for string.startsWith
              pars.splice(i, 1);
      url = urlBase+'?'+pars.join('&');
  }
  return url;
}


/**
 * Remove all action parameters from a URL. This can be helpful 
 * for removing action paramters when you do not know their
 * source component ID.
 *
 * @author Jay  Haase <jay@clockwork.net>
 *
 * @param  string    url    The URL to be de-actioned.
 * @return string           The passed in URL free of opposing action parameters.
 */
function removeAllActionParameters(url)
{
	var urlparts= url.split('?');

	if ( urlparts.length >= 2 ) {

		var urlBase      =  urlparts.shift( );            //get first part, and remove from array
		var queryString  =  urlparts.join( "?" );         //join it back up
		var pars         =  queryString.split( /[&]/g );

		//reverse iteration as it may be destructive
		var i  =  pars.length;
		while ( i-- ) {

			if ( pars[i].lastIndexOf( 'action_', 0 ) !== -1 ) {    //idiom for string.startsWith
				pars.splice( i, 1 );
			}
		}

		url  =  urlBase + '?' + pars.join( '&' );
	}

	return url;
}


function setUrlParameter(key, value, url) {

	key = escape(key); value = escape(value);

	_url = typeof url !== 'undefined' ? url : document.location.href;

	var kvp = key + "=" + value;

	var r = new RegExp( "(&|\\?)" + key + "=[^\&]*" );

	if( ! r.test( _url ) ) {
		_url += ( document.location.search.length > 0 ? '&' : '?' ) + kvp;
	}
	else {
		_url = _url.replace( r, "$1" + kvp );
	}

	return _url;
}

/**
 * I think addParameter() is more robust then setUrlParameter.
 *
 * http://stackoverflow.com/questions/6953944/
**/

function addParameter(url, parameterName, parameterValue, atStart, replaceDuplicates){

	replaceDuplicates = true;

	replaceDuplicates = typeof replaceDuplicates !== 'undefined' ? replaceDuplicates : true;
	atStart = typeof atStart !== 'undefined' ? atStart : false;

	// Handle the fragment part of the URL.
	if ( url.indexOf('#' ) > 0 ) {
		var cl = url.indexOf('#');
		urlhash = url.substring(url.indexOf('#'),url.length);
    } else {
		urlhash = '';
		cl = url.length;
	}
	sourceUrl = url.substring(0,cl);

	var urlParts = sourceUrl.split("?");
	var newQueryString = "";

    if (urlParts.length > 1) {
		var parameters = urlParts[1].split("&");
		for (var i=0; (i < parameters.length); i++) {
			var parameterParts = parameters[i].split("=");
			if ( ! ( replaceDuplicates && parameterParts[0] == parameterName ) ) {
				if (newQueryString == "") {
					newQueryString = "?";
				}
				else {
					newQueryString += "&";
				}
				newQueryString += parameterParts[0] + "=" + (parameterParts[1]?parameterParts[1]:'');
			}
		}
	}
	if (newQueryString == ""){
		newQueryString = "?";
	}

	if (atStart) {
		newQueryString = '?'+ parameterName + "=" + parameterValue + (newQueryString.length>1?'&'+newQueryString.substring(1):'');
	}
	else {
		if (newQueryString !== "" && newQueryString != '?'){
			newQueryString += "&";
		}
		newQueryString += parameterName + "=" + (parameterValue?parameterValue:'');
	}
	return urlParts[0] + newQueryString + urlhash;
}


function getParameters ( ) {
	var searchString = window.location.search.substring(1);
	var params = searchString.split("&");
	var hash = {};

	for (var i = 0; i < params.length; i++) {
		var val = params[i].split("=");
		hash[unescape(val[0])] = unescape(val[1]);
	}
	return hash;
}



/* firebug-specific log & info shortcuts, for debugging only */
function log(txt) {try {console.log(txt);} catch (e) {}}
function info(txt) {try {console.info(txt);} catch (e) {}}

if ( typeof Clockwork !== "undefined" ) {
	Clockwork.forms  =  {

		submit : function ( action ) {

		var form   =  $$('form').first( );

		form.action.value  =  action;
		return form.submit( );
	},
	download : function ( action ) {

			var form    =  $$('form').first( );
			var fclone  =  form.clone( );

			form.action.value  =  action;
			form.method.value  =  'get';
			form.target.value  =  '_blank';
			var result  =  form.submit( );

			form  =  fclone;
			return result;
		}
	};
}

/**
 * Clears out form field values.
**/
CWjQuery.fn.clear_form_fields  = function ( ) {
	CWjQuery(this).find( ':input' ).each( function( ) {
		var type  =  this.type;
		var tag   =  this.tagName.toLowerCase( );
		if ( type == 'text' || type == 'password' || type == 'textarea' ) {
			this.value  =  '';
			CWjQuery(this).change();
		}
		else if ( type == 'checkbox' || type == 'radio' ) {
			this.checked  =  false;
			CWjQuery(this).change();
		}
		else if ( tag == 'select' ) {
			this.selectedIndex = 0;
			CWjQuery(this).change();
		}
	} );
	return this;
};

/** 
 * Attaches event handling to html anchors with an 'action-link' CSS class.
 * When clicked, the element's parent form will be submitted, with the action
 * set as the value of the href of the link. For example,
 * <a href="#save">Save</a> will submit the form with an action of 'save'
**/
function handle_action_link_events ( ) {
	CWjQuery( 'a.action-link' ).click( function( ) {
		var link    =  CWjQuery( this );
		link.unbind( 'click' ).addClass( 'disabled' );

		var action  =  this.href.substr( this.href.lastIndexOf( '#' ) + 1 );
		var form    =  link.closest( 'form' );
		form.find( 'input[name^=action]' ).val( action );
		form.submit( );
		return false;
	} );
}

String.prototype.toCamelCase  =  function( ) {
	return this.replace( /(?:^|\s|\-|_)(\w)/g, function( match ) { return match.toUpperCase( ); } ).replace( /[^A-Za-z0-9]/g, '' );
};


function str_to_bool ( s ) {

	if ( typeof s !== 'string' ) {
		return undefined;
	}

	if ( s === '' ) {
		return false;
	}

	if ( s === '0' ) {
		return false;
	}

	if ( s === '1' ) {
		return true;
	}

	if ( s === 'false' ) {
		return false;
	}

	if ( s === 'true' ) {
		return true;
	}

	n = parseInt( s, 10 );
	return n > 0;
}



if (!String.format) {
	String.format = function(format) {
		var args = Array.prototype.slice.call(arguments, 1);
		return format.replace(/{(\d+)}/g, function(match, number) {
			return typeof args[number] != 'undefined' ? args[number] : match;
		});
	};
}


/**
 * Safely get the value from an HTML element, or
 * if the element does not exist, return the default
 * value.
 *
 * This function could probably be expanded to 
 * handle other element types.
 *
 *
 * @author Jay Haase <jay@clockwork.net>
 *
 *
 * @param  {string} selector        the selector for the element
 *                                  to query.
 *                                  
 * @param  {mixed} default_value    the default value to return
 *                                  if the selector's target does 
 *                                  not exist.
 *
 * @return {string}               The value of the input or the default value.
**/
function safe_element_value( selector, default_value ) {

	if ( typeof default_value === undefined ) {
		default_value  =  null;
	}

	// check if the selector's target element exists
	if ( $( selector ) ) {
		return $( selector ).value;
	}

	return default_value;
}


/**
 * Test for iframe access without throwing uncatchable error.
 * From http://stackoverflow.com/questions/12381334/foolproof-way-to-detect-if-iframe-is-cross-domain
**/
function can_access_iframe( iframe ) {
    var html = null;
    try {
      // deal with older browsers
      var doc = iframe.contentDocument || iframe.contentWindow.document;
      html = doc.body.innerHTML;
    } catch(err){
      // do nothing
    }

    return(html !== null);
}

