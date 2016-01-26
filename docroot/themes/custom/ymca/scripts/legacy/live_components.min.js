// Make sure we have a Clockwork object initialized
Clockwork = typeof Clockwork !== 'undefined' ? Clockwork : {};

// Check if Prototype is loaded 
if ( typeof Prototype !== "undefined" ) {

	//Logger.log_level = CW_LOG_DEBUG;

	// Namespace for all live-side stuff
	Clockwork.Live  =  { };

	// Spot for components to put their live-side js
	Clockwork.Live.Components  = { };

	Clockwork.Live.component_events  = { };  // e custom events
	Clockwork.Live.page_components  = [];    // all the components on a page

	// Superclass for Live Components
	Clockwork.Live.Components.Component = Class.create( {

		initialize:function( args ) {

			this.component_id = args.component_id;

			this.component_el = $('component_'+this.component_id);

			this.component_type = args.component_type;

			Clockwork.Live.page_components.push( this );
		},


		update : function( action, parms, onComplete ) {

			parms = parms || {};

			parms['action_' + this.component_id] = action;
			parms.json = 1;

			var message_target = null;

			if ( parms.message_target ) {
				message_target = parms.message_target;
			}
			else {

				if ( $$('.message').length > 0 ) message_target = $$('.message')[0];

				if ( $('messages_' + this.component_id) ) message_target = $('messages_' + this.component_id );

			}

			onComplete  =  onComplete || Prototype.emptyFunction;

			var updateComplete = function( transport, object ) {
				this.component_el = $('component_'+this.component_id);
				onComplete( transport, object );
			}


			new CW.CompatibleUpdater( this.component_el,
				location.href.split( '#' )[0],
				{'method'         :  'post',
				'replace'         :  true,
				'parameters'      :  parms,
				'message_target'  :  message_target,
				'asynchronous'    :  true,
				'evalScripts'     :  true,
				'onComplete'      :  updateComplete.bind( this ) } );

		}

	} );


	Clockwork.Live.Components.TextpanderComponent = Class.create( Clockwork.Live.Components.Component, {

		initialize:function( $super, args ) {

			$super( args );

			this.component_el = $('component_'+this.component_id);
			this.headline_el = $$('#component_'+this.component_id+' .headline')[0];
			this.toggle_el = $$('#component_'+this.component_id+' .toggle')[0];
			this.toggle_open_el = $$('#component_'+this.component_id+' .toggle .open')[0];
			this.toggle_closed_el = $$('#component_'+this.component_id+' .toggle .closed')[0];
			this.content_el = $$('#component_'+this.component_id+' .content')[0];


			this.animate_images = null;

			if (args.animate_images) {
				this.animate_images = args.animate_images;
				this.animate_images.open_image_el = $$('#component_'+this.component_id+' .toggle .open img')[0];
				this.animate_images.closed_image_el = $$('#component_'+this.component_id+' .toggle .closed img')[0];
			}

			Event.observe(this.headline_el, 'click',this.toggle.bind(this));

		},


		toggle : function() {
			var d = this.content_el.getStyle('display');
			this.toggle_open_el.removeClassName( 'initial' );
			this.toggle_closed_el.removeClassName( 'initial' );

			if ( d == 'none' ) {
				this.toggle_open_el.hide();
				this.toggle_closed_el.show();
				if ( this.animate_images ) {
					this.animate_images.open_image_el.src = this.animate_images.closed;
					this.animate_images.closed_image_el.src = this.animate_images.opening;
				}
				
				CWjQuery(this.content_el).slideToggle();

			} else {
				this.toggle_open_el.show();
				this.toggle_closed_el.hide();

				if ( this.animate_images ) {
					this.animate_images.open_image_el.src = this.animate_images.closing;
					this.animate_images.closed_image_el.src = this.animate_images.open;
				}
				
				CWjQuery(this.content_el).slideToggle();
			}
		}


	} ) ;



	Clockwork.Live.Components.GallerySingleImageComponent = Class.create( Clockwork.Live.Components.Component, {

		initialize:function( $super, args ) {

			$super( args );
			this.gallery_id= args.gallery_id;
			this.component_el = $('component_'+this.component_id);
			
		},

		ajaxReload : function( gallery_asset_href ) {
			var parameters = {};
			parameters['action_' + this.component_id] = 'display';

			new CW.CompatibleUpdater( this.component_el,
										gallery_asset_href, 
										{'method':'get',
										'replace':true,
										parameters: parameters,
										asynchronous: true,
										evalScripts: false,
										onComplete:this.ajaxReloadComplete.bind( this ) } );

		},

		ajaxReloadComplete : function( rsp ) {
			this.component_el = $('component_'+this.component_id);
		}

	} );


	Clockwork.Live.Components.GalleryIndexComponent = Class.create( Clockwork.Live.Components.Component, {

		initialize : function( $super, args ) {

			$super( args );
			this.gallery_id= args.gallery_id;
			this.component_el = $( 'component_' + this.component_id );
			this.link_els = $$( '#component_' + this.component_id + ' a' );

			if ( ! args.is_lightbox_gallery ) {
				this.initialize_inline( );
			}
			
		},

		initialize_inline: function ( ) {
			this.link_els.each( function( link_el ) {

				var asset_href = link_el.readAttribute( 'href' );
				
				link_el.writeAttribute( {'href' : 'javascript:;' } );
				Event.observe( link_el, 'click', this.ajax_switch_asset.bind( this, asset_href, link_el ) );

			}.bind( this ) );
		},

		ajax_switch_asset : function( asset_href, current_link_el ) {

			// find a GallerySingleImageComponent on the page with the same gallery id
			var image_component = null;

			for ( var i=0 ; i< Clockwork.Live.page_components.length ; i++ ) {

				var c = Clockwork.Live.page_components[i];

				if ( ( c.component_type == 'gallery_single_image' || c.component_type == 'ace_gallery_single_image') && c.gallery_id == this.gallery_id ) {

					image_component = c;
					break;

				}

			}

			if ( image_component ) {

				image_component.ajaxReload( asset_href );

			}

			this.index_els = $$( '#component_' + this.component_id + ' li' );

			this.index_els.each( function( index_el ) {

				index_el.removeClassName( 'current' );

			} );

			$(current_link_el.parentNode).addClassName( 'current' );

		}

	} );





	Clockwork.Live.Components.PageRatingComponent = Class.create( Clockwork.Live.Components.Component, {

		initialize:function( $super, args ) {

			$super( args );

			this.rating_type = args.rating_type;

			this.allow_comment = ( args.allow_comment == '1' ) ? true : false;

			this.extra_fields  =  CWjQuery.parseJSON( args.extra_fields );
		},

		rate : function( value ) {

			this.current_value = value;

			if ( ! this.allow_comment ) {
				this.submit_rating( )
			}
			else {

				var rating_comment_el = this.component_el.down( '.rating_comment' );

				if ( rating_comment_el ) {
					rating_comment_el.show();
				}
				
			}

		},

		submit_rating : function( ) {

			var comment_el = this.component_el.down( 'textarea' );
			var comment = ( comment_el ) ? comment_el.value : null;

			var params = {};
			params.message_target = 'page_rating_message';

			// Get any dynamic fields, add the ones we're expecting
			var form_fields  =  CWjQuery( this.component_el.down('.rating_form') ).serializeArray();
			CWjQuery.each( form_fields, function(i, field) {
		    	params[field.name] = field.value;
			});

			params.rating = this.current_value;
			params.comment = comment;

			var onComplete = function( transport, object ) {
				if ( object.results.error ) {
					CWjQuery('#page_rating_message').show( );
				}
				else {
					CWjQuery('#page_rating_message').hide( );
				}
			};

			this.update( 'rate', params, onComplete );

			return false;
		}


	} );




	Clockwork.Live.Components.ASKSurveyComponent = Class.create( Clockwork.Live.Components.Component, {

		initialize:function( $super, args ) {

			$super( args );
			this.survey = args.survey;
			this.chart_color = '8cace9';
			this.progress = args.progress;
			this.total_questions = args.total_questions;
			this.questions = args.questions; // current questions to display
			this.survey_closed_signal = 'survey_closed';
			this.component_el = null;
			this.survey_contents_el = null;
			this.survey_messages_el = null;
			this.back_button_el = null;
			this.next_button_el = null;
			this.progress_bar_el = null;
			this.component_el = $('component_'+this.component_id);

			this.load_barebones_html( );
		},

		load_barebones_html: function ( ) {
			// Now that we're ready, ask the server for the bare-bones HTML to
			// load the JSON data into.
			var parameters  =  { };
			parameters['action_' + this.component_id]  =  'initialize';

			parameters  =  this.add_parameters_hook( parameters );

			// We call this.display( ) on completion to populate the HTML
			var options  =  {
				method        :  'post'
				, parameters  :  parameters
				, onComplete  :  this.display.bind( this )
			};
			
			// Split off any fragments
			var url  =  location.href.split( '#' )[0];

			new Ajax.Updater(
				{ success: 'component_' + this.component_id }
				, url
				, options
			);
		},

		add_parameters_hook: function ( parameters ) {
			// Override in subclasses to add parameters necessary for any POSTs
			return parameters;
		},

		display: function( ) {

			if ( this.component_el == null) {
				this.component_el = $('component_'+this.component_id);
			}

			if ( this.survey_contents_el == null) {
				this.survey_contents_el = $$('#component_'+this.component_id+' .survey_contents')[0];
			}

			if ( this.survey_messages_el == null) {
				this.survey_messages_el = $$('#component_'+this.component_id+' .survey_messages')[0];
			}

			if ( this.progress_bar_el == null) {
				this.progress_bar_el = $$('#component_'+this.component_id+' .progress')[0];
			}

			if ( this.back_button_el == null) {
				this.back_button_el = $('component_'+this.component_id+'_back_button');
			}

			if ( this.next_button_el == null) {
				this.next_button_el = $('component_'+this.component_id+'_next_button');
			}
			
			var show_back_button = ( this.questions.length && this.questions[0].sequence_index > 0 ) ? true: false;
			var show_next_button = true;


			if ( this.back_button_el != null ) {	
				if (show_back_button) {
					this.back_button_el.show();
				} else {
					this.back_button_el.hide();
				}
			}

			if ( this.next_button_el != null ) {	
				if (show_next_button) {

					var button_texts  =  this.button_texts || Clockwork.Live.Components.ASKSurveyComponent.button_texts;
					var next_button_text = button_texts[ 'next' ];
					if ( this.questions.length && this.questions[ this.questions.length-1 ].question_number >= this.survey.highest_question_number ) {
						next_button_text = button_texts[ 'submit' ];
					}
					this.next_button_el.update( next_button_text );
					this.next_button_el.show();
				} else {
					this.next_button_el.hide();
				}
			}

			this.update_survey_contents( );
			this.hookup_events();
			this.update_progress();
		},

		update_survey_contents : function ( ) {

			var question_contents = '';

			for (var i=0; i<this.questions.length; i++) {
				question_contents += this.questions[i].html;
			}

			var contents = question_contents;

			this.survey_contents_el.update( contents );
			CWjQuery(this.component_el).trigger("display.update"); //display trigger used after survey update
		},

		highlight_errors : function( messages ) {
			var question_ids = Object.keys( messages );
			for (var i=0;i<this.questions.length;i++) {

				var question_id = this.questions[i].question_id;

				field_id = 'component_' + this.component_id + '_question_' + question_id;

				field_message_id = field_id+'_messages';

				if ( ! $( field_message_id ) ) {
					continue;
				}

				if (question_ids.include(question_id)) {

					Element.addClassName(field_id, 'submission_error');

					var field_messages = messages[ question_id ];
					var field_messages_text = '';

					for (var j=0;j<field_messages.length;j++) {
						var message = field_messages[j];
						message = this.format_message( message );
						field_messages_text += message;
					}
					
					$( field_message_id ).update( field_messages_text );
					
				} else {

					Element.removeClassName(field_id, 'submission_error');
					$( field_message_id ).update( '' );

				}

			}
			
			CWjQuery(this.component_el).trigger("display.update"); //display trigger used after survey update

		},

		hide_messages : function(messages) {

			this.survey_messages_el.update( '' );
			this.survey_messages_el.hide();

		},
		
		format_message : function(message) {

			message = message.replace(/\[e\](.+)/g, function($str, $1) { return '<div class="message error"/>'+$1+'</div>';});
			message = message.replace(/\[i\](.+)/g, function($str, $1) { return '<div class="message info"/>'+$1+'</div>';});
			message = message.replace(/\[w\](.+)/g, function($str, $1) { return '<div class="message warn"/>"'+$1+'</div>';});
			
			return message;
		},

		get_show_messages_title_message : function( )  {
			return "Please correct the following errors:";
		},

		show_messages : function(messages) {

			var message_text = '<div class="message"/>'+ this.get_show_messages_title_message()  + '</div>';
			this.survey_messages_el.update( message_text );
			this.survey_messages_el.show();
			CWjQuery(this.component_el).trigger("display.update"); //display trigger used after survey update

		},

		hookup_events : function () {

			Event.stopObserving(this.back_button_el, 'click');
			Event.stopObserving(this.next_button_el, 'click');

			if ( this.back_button_el ) {
				Event.observe(this.back_button_el, 'click',this.go_back.bind(this));
			}
			if ( this.next_button_el ) {
				Event.observe( this.next_button_el, 'click',this.go_next.bind(this));
			} 

		},

		delete_file : function ( file_name, form ) {
			if ( ! confirm( "Delete the file?\n\n" + file_name ) ) {
				return;
			}
			this.microsave_answers( function( rsp ) {
				form.submit( );
			} );
			return false;
		},

		upload_file : function ( form ) {
			this.microsave_answers( function( rsp ) {
				form.submit( );
			} );
			return false;
		},

		microsave_answers : function ( callback ) {
			var answers = this.collect_answers();
			
			// send answers back, get next set of questions
			var parameters = {
				'survey_id' : this.survey.survey_id
				,'answers'  : Object.toJSON( answers )
				,'ajax'     : true
			};
			parameters[ 'action_' + this.component_id ]  = 'microsave';
			parameters  =  this.add_parameters_hook( parameters );
			this.hide_messages();

			var contents =  CWjQuery( this.survey_contents_el );
			contents.fadeTo( 300, 0, function( ) {
					new Ajax.Request( location.href.split( '#' )[0],  {
						method     : 'post',
						parameters : parameters,
						onSuccess  : callback
					} );
			} );
		},


		go_next : function() {
			// client-side validate

			var answers = this.collect_answers();
			
			// send answers back, get next set of questions
			var parameters = {
				'survey_id': this.survey.survey_id
				,'answers': Object.toJSON( answers )
				,'ajax': true
			};

			parameters['action_'+this.component_id] = 'get_next';

			parameters  =  this.add_parameters_hook( parameters );

			this.hide_messages();

			CWjQuery( this.survey_contents_el ).fadeTo( 300, 0 );

			new Ajax.Request( location.href.split( '#' )[0],  {
			method:'post',
			parameters:parameters,
			onSuccess: function(rsp){
				var response = rsp.responseText || "no response text";
				if (CWjQuery) {
					CWjQuery(document).trigger('ajaxComplete');
				}
				jsondata = eval( '(' + response + ')' );
				if (jsondata.success) {

					if (jsondata.survey_complete) {

						// Promotion messages callback.
						if ( jsondata.promotion_messages && CW && CW.promotion_messaging_handler ) {
							CW.promotion_messaging_handler( jsondata.promotion_messages );
						}
						
						// redirect using POST so that conditional content rules are followed
						var component_container = $( 'component_' + this.component_id );
						var redirect_form_el = new Element( 'form', { 
																		id: 'component_' + this.component_id + '_redirect_form',
																		name: 'component_' + this.component_id + '_redirect_form',
																		method: 'post'
																	} );
																	
						var post_value = new Element( 'input', { name: 'post_value_' + this.component_id, type: 'hidden', value: '1' } );
						redirect_form_el.insert( post_value );
						component_container.insert( redirect_form_el );

						if ( jsondata.redirect_url ) {
							
							redirect_form_el.action = jsondata.redirect_url;
							post_value.remove();
							redirect_form_el.submit( );
							
							return;
						}

						if ( this.survey.end_page_type == 'internal') {
							
							redirect_form_el.action = this.survey.end_page_url_internal;
							redirect_form_el.submit( );
							
							return;
						}
						else if ( this.survey.end_page_type == 'external' ) {
							window.location = this.survey.end_page_url;
							return;
						}
						else { // no end page set, show thankyou message
							this.component_el.replace(jsondata.complete_html);
							this.on_survey_complete();
							return;
						}


					}
					else {
						this.progress = jsondata.progress;
						this.questions = jsondata.questions;
						this.display();
						CWjQuery( this.survey_contents_el ).fadeTo( 500, 1 );

					}
				}
				else if (jsondata.message === this.survey_closed_signal) {
					this.handle_closed_signal(jsondata);
					return;
				}
				else {
					CWjQuery( this.survey_contents_el ).fadeTo( 500, 1 );
					this.show_messages(jsondata.messages);
					this.highlight_errors(jsondata.messages);
				}
			}.bind(this),
			onFailure: function(rsp){ log('Something went wrong: '+rsp) }
		  });
		},

		on_survey_complete : function( ) {
		},

		go_back : function() {

			var answers = this.collect_answers();

			// send answers back, get next set of questions
			var parameters = {
				'survey_id': this.survey.survey_id
				,'answers': Object.toJSON( answers )
			};

			parameters['action_'+this.component_id] = 'get_previous';

			parameters  =  this.add_parameters_hook( parameters );

			this.hide_messages();
			CWjQuery( this.survey_contents_el ).fadeTo( 300, 0 );

			new Ajax.Request( location.href.split( '#' )[0],  {
			method:'post',
			parameters:parameters,
			onSuccess: function(rsp){
				var response = rsp.responseText || "no response text";
				jsondata = eval('('+response+')');
				if (jsondata.success) {
					this.progress = jsondata.progress;
					this.questions = jsondata.questions;
					this.display();
					CWjQuery( this.survey_contents_el ).fadeTo( 500, 1 );
				}
				else if (jsondata.message === this.survey_closed_signal) {
					this.handle_closed_signal(jsondata);
					return;
				}
				else {
					CWjQuery( this.survey_contents_el ).fadeTo( 500, 1 );
					this.show_messages(jsondata.messages);
					this.highlight_errors(jsondata.messages);
				}
			}.bind(this),
			onFailure: function(rsp){ log('Something went wrong: '+rsp) }
		  });
		},

		update_progress : function() {
			// the progress bar may not be in the template, in which case return.
			if ( this.progress_bar_el == null ) return;

			if (this.total_questions <= 1 || this.questions.length == this.total_questions) {
				$$('#component_'+this.component_id+' .progress_bar')[0].hide();
				$$('#component_'+this.component_id+' .progress_label')[0].hide();
				return;
			}
			var outer_width = $$('#component_'+this.component_id+' .progress_bar')[0].getWidth();
			width = Math.floor(this.progress * outer_width);

			var post_morph_effect = function() {};
			
			var width_str = width + 'px';
			CWjQuery( this.progress_bar_el ).show( ).animate( { width: width_str}, 500 );
		},

		collect_answers : function() {
			var answers = [];
			for (var i=0;i<this.questions.length;i++) {
				var question = this.questions[i];
				var answer = {'question_id':question.question_id,'text':''};

				// Do not store answers to rich texts and sections
				if (question.question_type == 'rich_text' || question.question_type == 'section') {
					continue;
				}

				if ( question.answer_type == 'text' || question.answer_type == 'textarea' || question.answer_type == 'file' ) {
					answer.text = $('component_'+this.component_id+'_question_'+question.question_id+'_answer').value;
				} else if (question.answer_type == 'option') {
					var selected_answer = null;
					if ( question.option_type == 'dropdown') {
						selected_answer = this.get_selected($('component_'+this.component_id+'_question_'+question.question_id+'_options'));
					}

					var selected_values = [];
					var selected_option_ids = [];
					for (var j=0;j<question.options.length;j++) {
						var option = question.options[j];
						var option_id = 'component_'+this.component_id+'_question_'+question.question_id+'_option_'+option.option_id;

						if ( question.option_type == 'dropdown') {

							if ( $(option_id).value == selected_answer ) {
								selected_values.push($(option_id).value);
								selected_option_ids.push(option.option_id);
							}

						} else {
							if ( $(option_id) && $(option_id).checked) {
								selected_values.push($(option_id).value);
								selected_option_ids.push(option.option_id);
							}
						}
					}
					answer.text = selected_values.join(',');
					answer.option_id = selected_option_ids.join(',');
				}
				answers.push(answer);
			}
			return answers;
		},

		get_selected : function(el) {
			if (el.selectedIndex == -1) return '';
			return el.options[el.selectedIndex].value;
		},

		handle_closed_signal: function( jsondata ) {
			this.component_el.replace( jsondata.closedHTML );
		}



	} );




	Clockwork.Live.Components.ASKSurveyComponent.button_texts = {'back':'Back', 'next':'Next', 'submit':'Submit'};

	Clockwork.Live.Components.DCMDataCaptureComponent = function( args ) {
		this.component_id = args.component_id;
		this.step = args.step;
		this.component_el = $('component_'+this.component_id);

		this.update_decedent_info_options = function() {
			this.estate_will_be_filed_container_el.hide();
			this.family_atty_exists_container_el.hide();
			this.estate_atty_exists_container_el.hide();

			if (this.estate_filed_option_els[0].checked) {
				this.estate_atty_exists_container_el.show();
			}

			if (this.estate_filed_option_els[1].checked || this.estate_filed_option_els[2].checked) {
				this.estate_will_be_filed_container_el.show();
				this.family_atty_exists_container_el.show();
			}
		}

		this.estate_filed_container_el = $('estate_filed');

		if (this.estate_filed_container_el) {
			this.estate_filed_option_els = this.estate_filed_container_el.select('input');

			this.estate_atty_exists_container_el = $('estate_atty_exists');
			this.estate_atty_exists_option_els = this.estate_atty_exists_container_el.select('input');

			this.estate_will_be_filed_container_el = $('estate_will_be_filed');
			this.family_atty_exists_container_el = $('family_atty_exists');


			for (var i=0;i<this.estate_filed_option_els.length;i++) {
				Event.observe(this.estate_filed_option_els[i], 'change', this.update_decedent_info_options.bind(this));
				/* Needed for IE7 */
				Event.observe(this.estate_filed_option_els[i], 'mouseup', function(){setTimeout(this.update_decedent_info_options.bind(this),100)}.bind(this));
			}

			for (var i=0;i<this.estate_atty_exists_option_els.length;i++) {
				Event.observe(this.estate_atty_exists_option_els[i], 'change', this.update_decedent_info_options.bind(this));
				/* Needed for IE7 */
				Event.observe(this.estate_atty_exists_option_els[i], 'mouseup', function(){setTimeout(this.update_decedent_info_options.bind(this),100)}.bind(this));
			}

			this.update_decedent_info_options();
		}

	};
}

// Also check if jQuery installed 
if ( typeof CWjQuery !== "undefined" ) {

	/**
	 * Content Expander component output JavaScript.
	**/
	CW.ContentExpanderComponentOutput  =  CW.ComponentOutput.extend( {

		content   : null, // jQuery object for content area
		toggle_el : null, // jQuery object for toggle element

		/**
		 * Initialize component.
		**/
		init : function ( args ) {
			this._super( args );
			this.register( 'ContentExpanderComponentOutput' );
			this.bind_events( );
		},


		/**
		 * Add event listener for toggle action. Any element that has the 
		 * class "toggle" will expand the content area. If the component
		 * finds nothing with the class "toggle" it will fall back on
		 * everything with the "heading" class. This allows the component
		 * to be flexible and have areas in the heading that are targets
		 * for the toggle action and ones that aren't.
		**/
		bind_events : function ( ) {
			var toggle  =  this.component_el.find( '.heading .toggle' );

			if ( toggle.size( ) === 0 ) {
				toggle  =  this.component_el.find( '.heading' );
			}

			toggle.on( 'click', CWjQuery.proxy( this.toggle_content, this ) );
		},


		/**
		 * Toggle the content area open or closed. Adds "open" class on content
		 * div and calls the overridable show() and hide() functions to control
		 * animation and visual changes.
		**/
		toggle_content : function ( evt ) {

			this.toggle_el  =  CWjQuery( evt.target );
			this.content    =  this.component_el.find( '.content' );

			if ( this.component_el.hasClass('open') ) {
				this.component_el.removeClass('open');
				this.hide( );
			}
			else {
				this.component_el.addClass('open');
				this.show( );
			}
		},


		/**
		 * FEDs can override this show function when the object is created if 
		 * different animation behavior is desired.
		**/
		show : function ( ) {
			this.toggle_el.text('-');
			this.content.show( );
		},


		/**
		 * FEDs can override this hide function when the object is created if 
		 * different animation behavior is desired.
		**/
		hide : function ( ) {
			this.toggle_el.text('+');
			this.content.hide( );
		}

	});
}

if ( window.CW ) {

CW.MapComponentOutput = CW.ComponentOutput.extend( {

	locations             :  null,          // Array of location data
	marker_image_url      :  null,      	// URL of marker image
	shadow_image_url      :  null,      	// URL of shadow image
	map                   :  null,          // The Map object
	tags                  :  {},			// Array of tag data, keyed by tag name
	distance_limit        :  null,       	// The distance filter limit, in miles
	center_point          :  null,         	// The center point of the map
	search_center_point   :  null,         	// The center point of a search location or distance limit 
	search_center_marker  :  null,			// Marker designating the center point
	n_list_columns        :  4,				// Number of columns in the list view

	init : function ( args ) {

		this._super( args );

		this.map_data           =  args.map_data;
		this.locations     =  this.map_data['locations'];

		this.marker_image_url        =  args.marker_image_url || null;
		this.shadow_image_url        =  args.shadow_image_url || null;

		this.search_center_marker = args.search_center_marker  || null;

		this.map_el = this.component_el.find( '.map_area' );
		this.location_list_el = this.component_el.find( '.location_list' );

		this.map_controls_el = this.component_el.find( '.map_controls' );
		this.search_field_el = this.map_controls_el.find( 'input.search_field' );
		this.distance_limit_el = this.map_controls_el.find( 'select.distance_limit_value' );
		this.locate_me_el = this.map_controls_el.find( '.locateme' );

		this.tags = {};

		this.init_map( );
		this.init_tags( );
		this.init_map_locations( );
		this.draw_map_controls( );
		this.hookup_map_controls_events( );
		this.update_tag_filters( );

		this.draw_map_locations( );
		this.draw_list_locations( );

	},

	// Normalizes a map-vendor specicific representation of 
	// a coordinate point to a {lat:x, lon:y} object.
	normalize_point: function( point ) {
	},


	// Initializes the base map 
	init_map : function( div_id ) {  
	},

	// executed every time a checkbox filter state changes
	filter_change : function( ) {
		this.update_tag_filters( );
		this.redraw_map_locations( );
		this.draw_list_locations( );
	},

	// Attaches events to various map controls
	hookup_map_controls_events : function() {

		this.map_controls_el.find( '.tag_filters input[type=checkbox]' ).on( 'change', CWjQuery.proxy( this.filter_change, this ) );
		this.search_field_el.on( 'change', CWjQuery.proxy( this.apply_search, this ) );
		this.distance_limit_el.on( 'change', CWjQuery.proxy( this.apply_distance_limit, this ) );
		this.locate_me_el.on( 'click', CWjQuery.proxy( this.locate_me_onclick, this ) );

	},

	apply_search : function( ) {
	},

	// Executed every time the viewer sets the distance limit to a new value
	apply_distance_limit : function( ) {

		if ( this.search_center == null ) {
			this.search_center = this.map.getCenter();
		}

		this.distance_limit = this.distance_limit_el.val();

		this.draw_search_center( );
		this.redraw_map_locations( );
		this.draw_list_locations( );

	},

	locate_me_onclick : function( evt ) {

		if ( ! navigator.geolocation ) {
			return;
		}

		this.search_field_el.val( '' );			

		this.geolocation_watcher = navigator.geolocation.watchPosition( CWjQuery.proxy( this.locate_me, this ) );

	},

	locate_me : function ( position ) {
	
	},

	// Extracts unique tag values from the map location data
	init_tags : function() {

		// Extract tags
		for ( i = 0; i < this.locations.length; i++ ) {

			var loc = this.locations[i];

			if ( ! loc.tags ) {
				loc.tags = [];
			}

			// Convert single-string tags to array
			if ( typeof( loc.tags ) == typeof( "" ) ) {
				loc.tags = [ loc.tags ];
			}

			for ( j = 0; j < loc.tags.length; j++ ) {

				var tag = loc.tags[j];

				if ( ! ( tag in this.tags ) ) {
					this.tags[ tag ] = { 'marker_icons' : [] };
				}

				if ( loc.icon && CWjQuery.inArray( loc.icon, this.tags[ tag ]['marker_icons'] ) == -1 ) {
					this.tags[ tag ]['marker_icons'].push( loc.icon );
				}
			}

		}

	},


	// Applies the current checkbox state of the tag filter controls
	// to the internal filters data structure.
	// Called at init time, and after every checkbox state change,
	update_tag_filters : function( ) {

		this.tag_filters = [];

		var self = this;

		var f = function( index ) {
			var el = CWjQuery(this);
			self.tag_filters.push( el.val() );
		}

		this.map_controls_el.find( '.tag_filters input[type=checkbox]:checked' ).each( f ); 

	},

	// Applies tag and distance filters to a list of locations
	// returns the filtered list
	apply_filters : function( locations ) {

		locations = this.apply_tag_filters( locations );

		locations = this.apply_distance_filters( locations );

		return locations;

	},

	// Applies tag filters to a list of locations
	// returns the filtered list
	apply_tag_filters : function( locations ) {

		if ( this.tag_filters.length == 0 ) {
			return locations;
		}

		var filtered_locations = [];

		for ( i = 0; i < locations.length; i++ ) {

			var loc = locations[i];
		
			for ( j = 0; j < this.tag_filters.length; j++ ) {

				var tag_filter = this.tag_filters[j];

				if ( CWjQuery.inArray( tag_filter, loc.tags ) >=0 ) {

					filtered_locations.push( loc );
					continue;  // If any tag matches, skip checking other tags
				}
			}

		}

		return filtered_locations;
	},

	// Applies distance filters to a list of locations
	// returns the filtered list
	apply_distance_filters : function( locations ) {

		if ( ! this.search_center ) {
			return locations;
		}

		if ( ! this.distance_limit || this.distance_limit == "" ) {
			return locations;
		}

		var search_center = this.normalize_point( this.search_center );

		filtered_locations = [];

		var lat1 = parseFloat( search_center.lat );
		var lon1 = parseFloat( search_center.lon );
		var rlat1 = this.toRad( lat1 );

		for ( i = 0; i < locations.length; i++ ) {

			var loc = locations[i];

			var R = 3963,
				lat2 = parseFloat(loc.latitude),
				lon2 = parseFloat(loc.longitude);

			var rlat = this.toRad( lat2 - lat1 );
			var rlon = this.toRad( lon2 - lon1 );
			var rlat2 = this.toRad( lat2 );

			var a = Math.sin(rlat/2) * Math.sin(rlat/2) + Math.sin(rlon/2) * Math.sin(rlon/2) * Math.cos(rlat1) * Math.cos(rlat2);
			var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
			var d = R * c;

			if ( d <= this.distance_limit ) {
				// Add the distance to the object
				loc.distance  =  d;
				filtered_locations.push( loc );
			}

		}	
		
		return filtered_locations;
	},

	// Populates an array of active tags from an URL parameter "map_tag_filter"
	init_active_tags : function( ) {

		if ( this.initial_active_tags ) {
			return this.initial_active_tags;
		}

		var active_tags = [];

		var url_parameters = getParameters();

		var tag_filter_url_value =  url_parameters["map_tag_filter"];

		var tag_filter_url_values = ( tag_filter_url_value ) ? tag_filter_url_value.split( "," ) : [];

		for ( tag in this.tags ) {

			if ( tag_filter_url_values.length == 0 ) {
				active_tags.push( tag );
			}
			else if ( CWjQuery.inArray( tag, tag_filter_url_values ) >= 0 ) {
				active_tags.push( tag );
			}

		}

		this.initial_active_tags = active_tags;

		return active_tags;

	},


	// Renders the map controls
	draw_map_controls : function( ) {

		this.init_active_tags( );

		var tag_filters_html = '';

		for ( var tag in this.tags ) {

			var filter_checked = '';

			if ( CWjQuery.inArray( tag, this.initial_active_tags ) >= 0 ) {
				filter_checked = 'checked="checked"';
			}

			tag_filter_html = '<label for="tag_'+tag+'">';

			tag_filter_html += '<input autocomplete="off" id="tag_'+tag+'" class="tag_'+tag+'" type="checkbox" value="'+tag+'" '+filter_checked+'/>'+tag;

			for ( var i=0; i< this.tags[tag]['marker_icons'].length;i++ ) {
				tag_filter_html += '<img class="tag_icon" src="' + this.tags[tag]['marker_icons'][i] + '"/>';
			}

			tag_filter_html += '</label>';

			tag_filters_html += tag_filter_html;
		}

		this.map_controls_el.find('.tag_filters').append( tag_filters_html );

	},


	// Update locations on the map by setting their visiblity
	// and refit the map bounds to the current set of visible locations
	draw_map_locations : function() {
	},

	// Updates locations on the map by setting their visibility
	// to false before drawing.
	redraw_map_locations : function() {
	},


	// Render the list of locations
	draw_list_locations : function() {

		var list_locations_html = '';

		column_index = 0;

		var locations = this.apply_filters( this.locations );

		for ( l = 0; l < locations.length; l++ ) {

			var list_item =  '<li class="col-' + column_index + '">';
			list_item += this.draw_list_location( locations[l] );
			list_item += '</li>';

			list_locations_html += list_item;

			column_index++;

			if ( column_index == this.n_list_columns ) column_index = 0;
		}

		this.location_list_el.hide().html( list_locations_html ).fadeIn();

	},

	// Generate the HTML for a single location in the list
	draw_list_location: function( loc ) {

		var results = '';
		if ( loc.name ) { results += '<h3>' + loc.name + '</h3>' };
		results += '<p>';
		if ( loc.address_1 ) { results += loc.address_1 };
		if ( loc.address_2 ) { results += '<br/>' + loc['address_2'] };
		if ( loc.city ) { results += '<br/>' + loc['city'] };
		if ( loc.state ) { results += ',' + loc['state'] };
		if ( loc.zip ) { results += ' ' + loc['zip'] }
		results += '</p>';
		if ( loc.phone ) { results += '<p>' + loc.phone + '</p>'}
		if ( loc.url ) {results += '<p><a href="' + loc.url + '">Visit Location Page</a></p>'}
		if ( loc.callout_image) {results += '<img src="' + loc.callout_image + '"/>'}
		if ( loc.callout_description) {results += "<p class='marker_tooltip_description'>" + loc.callout_description + '</p>'}
		return results;

	},

	// Generate the HTML for a single location's map detail view
	draw_map_location : function( loc ) {
		return this.draw_list_location( loc );
	},

	// Convert a number from degrees to radians
  	toRad : function( n ) {
    	return n * Math.PI / 180;
	}

} );

}

CW.ContentGroupComponentOutput  =  CW.ComponentOutput.extend({
	group_link_selector:     '.group a',
	group_content_selector:  '.group_content',
	active_group_class_name: 'current',
	group_links: [],
	group_content: [],

	init: function( args ) {
		this._super( args );

		this.group_links    =  this.component_el.find( this.group_link_selector );
		this.group_content  =  this.component_el.find( this.group_content_selector );

		this.component_el.on( 'click', this.group_link_selector, CWjQuery.proxy( this.handle_group_click, this ) );

		this.refresh_group_selection( );
	},

	get_hash_from_href : function( href ) {
		if ( ! href ) {
			return '';
		}
		var bang  =  href.indexOf( '#' );
		if ( bang < 0 ) {
			return '';
		}
		return href.substr( bang + 1 );
	},

	refresh_group_selection : function( ) {
		var active_class_name  =  this.active_group_class_name;
		var selected_group     =  this.get_hash_from_href( window.location.hash );
		var hash_from_href     =  this.get_hash_from_href;
		var content_found      =  false;

		this.group_links.each( function( i, tl ) {
			if ( hash_from_href( tl.href ) == selected_group ) {
				CWjQuery( tl ).addClass( active_class_name );
			}
			else {
				CWjQuery( tl ).removeClass( active_class_name );
			}
		} );

		this.group_content.each( function( i, tc ) {
			if ( tc.id == selected_group ) {
				CWjQuery( tc ).addClass( active_class_name );
				content_found  =  true;
			}
			else {
				CWjQuery( tc ).removeClass( active_class_name );
			}
		} );

		if ( ! content_found ) {
			this.group_links.first( ).addClass( active_class_name );
			this.group_content.first( ).addClass( active_class_name );
		}
	},

	handle_group_click: function( e ) {
		e.preventDefault( );
		var group_id  =  this.get_hash_from_href( e.target.href );
		window.location.hash  =  group_id;
		this.refresh_group_selection( );
		return false;
	}
});


CW.GoogleExperimentMediator  =  CWjQueryClass.extend({
	google_experiments: [],

	hasExperiment : function( id )
	{
		return (id in this.google_experiments);
	},

	registerExperiment : function( id )
	{
		this.google_experiments[id] = {
			variation_set	: false,
			variation_id	: null
		};
	},

	getVariation : function( id )
	{
		return this.google_experiments[id].variation_id;
	},

	registerChosenVariation : function( experiment_id, variation_id )
	{
		if( this.google_experiments[experiment_id].variation_set === false ) {
			this.google_experiments[experiment_id].variation_id = variation_id;
			this.google_experiments[experiment_id].variation_set = true;
			CWjQuery(document).trigger('cw_ab_test_'+experiment_id, [variation_id]);
		}
	}
});

CW.GoogleExperimentComponent  =  CW.ComponentOutput.extend({
	key_prefix				: 'group_',
	google_experiment_id	: null,
	chosen_variation		: null,
	ga_aware_exists			: false,
	ga_aware				: null,

	init : function(args)
	{
		this._super( args );
		this.google_experiment_id  =  args.google_experiment_id;

		if( typeof cwGA !== 'undefined' ) {
			this.ga_aware_exists = true;
			this.ga_aware = cwGA;

			this._hideAllVariations();

			if( typeof window.cw_google_exp_mediator === 'undefined' ) { // No mediator yet. Create it & poll google.
				window.cw_google_exp_mediator = new CW.GoogleExperimentMediator();
				window.cw_google_exp_mediator.registerExperiment( this.google_experiment_id );
				this._getVariation();
			}
			else if( window.cw_google_exp_mediator.hasExperiment(this.google_experiment_id) === false ) { // New exp id. Poll google.
				window.cw_google_exp_mediator.registerExperiment( this.google_experiment_id );
				this._getVariation();
			}
			else {
				var variation_id = window.cw_google_exp_mediator.getVariation(this.google_experiment_id);
				if( variation_id == null ) { // Variation not set. Create event listener
					var that = this;
					CWjQuery(document).on('cw_ab_test_'+this.google_experiment_id, function( event, variation_id ) {
						that.chosen_variation = variation_id;
						that._showVariation();
					});
				}
				else { // Assign variation carry on
					this.chosen_variation = variation_id;
					this._showVariation();
				}
			}
		}
		else { // fail silently
			this.chosen_variation = 0;
			this._showVariation();
		}
	},

	// Hide all content experiments in this component
	_hideAllVariations : function()
	{
		CWjQuery('.google_abtest_'+this.component_id).hide();
	},

	// Poll Google Experiments API & assign variation ID.
	_getVariation : function()
	{
		var that = this;
		CWjQuery.ajax({
			url 		: "//www.google-analytics.com/cx/api.js?experiment="+this.google_experiment_id,
			dataType 	: "script",
			cache 		: false
		})
		.done( function( script, textStatus ) {
			that.chosen_variation = cxApi.chooseVariation();
			window.cw_google_exp_mediator.registerChosenVariation(that.google_experiment_id, that.chosen_variation);
			that._showVariation();
			that._sendResults();
		})
		.fail( function( jqxhr, settings, exception ) {
			// Call to GA failed. Show original variation.
			that.chosen_variation = 0;
			that._showVariation();
		});
	},

	// Show selected variation.
	_showVariation : function()
	{
		var chosen_id = '#google_abtest_' + this.component_id + '_' + this.key_prefix + this.chosen_variation;
		CWjQuery(document).trigger("cw_ab_test.show_"+this.component_id, [ chosen_id, this.component_id, this.google_experiment_id, this.chosen_variation ]);
		CWjQuery(chosen_id).show();
	},

	// Make a non-interaction tracking call to GA, because load order cannot be guaranteed in the AMM.
	_sendResults : function()
	{
		if( this.ga_aware_exists === true ) {
			this.ga_aware.track_event('abtest', 'non-action', 'component', 0, true);
		}
	}
});


CW.LocationPostalCodeSearchComponent = CW.ComponentOutput.extend( {

	init : function ( args ) {

		this._super( args );

		this.register( 'LocationPostalCodeSearchComponent' );

		this.component_el.on(
			'click',
			'#search_button_' + this.component_id,
			CWjQuery.proxy( this.search_button_clicked, this )
		);

		this.component_el.on(
			'click',
			'.show_map',
			CWjQuery.proxy( this.show_map_clicked, this )
		);

		this.component_el.on(
			'click',
			'#current_location_button_' + this.component_id,
			CWjQuery.proxy( this.current_location_search_clicked, this )
		);
		
		this.component_el.on(
			'submit',
			'form',
			CWjQuery.proxy( this.search_button_clicked, this )
		);
	
	},

	search_button_clicked: function( event ) {

		var form       =  CWjQuery( event.currentTarget ).closest( 'form' );
		var form_data  =  form.serializeJSON( );

		this.ajax_search_for_zip( form_data );
		return false;
	},


	ajax_search_for_zip: function ( form_data ) {

		form_data['action_' + this.component_id]  =  'ajax_search';
		var me = this;

		CWjQuery.ajax({
			url       :  window.location.href,
			type      :  "post",
			data      :  form_data,
			complete  :  function( data){
				me.ajax_search_complete( data );
			}
		});

		this.clear_search_results( );
	},


	clear_search_results : function ( ) {

		var results_el  =  CWjQuery( '#search_results_' + this.component_id );
		results_el.empty( );
	},


	current_location_search_clicked : function( event ) {

		this.clear_search_results( );

		// Try HTML5 geolocation
		if ( ! navigator.geolocation ) {
			return false;
		}

		// Save this in a variable for use in the callback function.
		var form       =  CWjQuery( event.currentTarget ).closest( 'form' );
		var form_data  =  form.serializeJSON( );
		var that       =  this;

		navigator.geolocation.getCurrentPosition( function( position ) {

			var lat    =  position.coords.latitude;
			var lon    =  position.coords.longitude;
			var point  =  new google.maps.LatLng(lat, lon);

			new google.maps.Geocoder( ).geocode( {'latLng': point}, function (results, status) {  

				var address               =  results[0].address_components;
				var postal_code           =  address[address.length - 1].long_name;
				form_data['postal_code']  =  postal_code;

				that.ajax_search_for_zip( form_data );

				return false;
			});  

			return false;
		});
	},


	ajax_search_complete: function( ajax_results ) {

		var error_el  =  CWjQuery( '#search_error_' + this.component_id );

		if ( ajax_results.responseJSON.error ) {

			error_el.show( );
			error_el.html( ajax_results.responseJSON.extra_data[0] );
			return;
		}

		// Hide/clear any previous error messages.
		error_el.hide( );
		error_el.html( ' ' );

		var results_el  =  CWjQuery( '#search_results_' + this.component_id );
		results_el.html( ajax_results.responseJSON.output );
		
		var current_map  =  CWjQuery( '#current-map' );
		current_map.hide();

		this.component_el.trigger("CW.LocationPostalSearchReturned");
	},


	show_map_clicked: function( event ) {
		
		var clicked_el    =  CWjQuery( event.target );
		var address       =  clicked_el.attr( 'title' );
		var geocoder      =  new CW.geocoder( );
		var location_map  =  CWjQuery( '#location-map-canvas' );

		event.preventDefault();

		if (location_map.length > 0) {
			location_map.detach( );
			clicked_el.after( location_map );
			location_map.show( );
		}

		geocoder.request_latlng( address ).then( function( location ) {

			if ( ! location.length ) {
				// We could not find the location.
				return false;
			}

			// Configure the map based on lat and lon.
			var lat			=  location[0].lat;
			var lon			=  location[0].lng;
			var myLatlng 	=  new google.maps.LatLng( lat, lon );
			var map_el		= CWjQuery('#location-map-canvas')[0];

			var mapOptions  =  {
				zoom       :  8,
				center     :  myLatlng,
				mapTypeId  :  google.maps.MapTypeId.ROADMAP
			};

			var map			=  new google.maps.Map( map_el, mapOptions );
			var marker		=  new google.maps.Marker({
				position  :  myLatlng,
				map       :  map,
				title     :  'MN Lottery Provider'
			}); 
		} );
	}
});
