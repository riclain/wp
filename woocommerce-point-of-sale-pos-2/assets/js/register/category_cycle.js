(function ( $ ) {
 	var settings;
    $.fn.category_cycle = function( options ) {
    	if (options == 'destroy') {
    		return this.each(function() {
	    		if ( $(this).data('category_cycle') == 'init' ){
    				var $_this   = $(this);
    				var settings = $(this).data('settings');
		    		$_this.data('category_cycle', '');
		    		$_this.data('elems', '');
		    		$_this.find('.open_category').unbind();
		    		$_this.unbind();
		    		settings.breadcrumbs.unbind();
		    		settings.breadcrumbs_h.unbind();
		    		settings.breadcrumbs.find('*').unbind();
		    		settings.breadcrumbs.find('*').not(settings.breadcrumbs_h).remove();
		    		$('.next-grid-layout').unbind();
		    		$('.previous-grid-layout').unbind();
		    		$('#nav_layout_cycle').html('').unbind();
	    		}
    		});
    		return false;
    	};
    	settings = $.extend({
            count           : 25,
            hierarchy       : {},
            relationships   : {},
            parents         : {},
            archive_display : 'subcategories',
            breadcrumbs     : false,
            breadcrumbs_h   : false
        }, options );
    	return this.each(function() {
    		if ( $(this).data('category_cycle') == 'init' )  return false;

	    	var $_this  = $(this);
	    	var $_elems = $_this.find('.category_cycle');
	    	
	        $_this.data('elems', $_elems);
	        filter_hierarchy($_elems, 0);

	        $_this.find('.open_category').click(function(event) {
	        	var catid = parseInt($(this).data('catid'));
	        	var title = $(this).data('title');
	        	filter_hierarchy($_elems, catid, title);
	        });
	        $_this.on('click', '.open_variantion', function(event) {
	        	var id = parseInt($(this).data('id'));
	        	delete POS_TRANSIENT.grid_product_variations;
    			delete POS_TRANSIENT.grid_product_attributes;
    			delete POS_TRANSIENT.grid_product_image;
    			delete POS_TRANSIENT.grid_current_settings;
	        	filter_variantion($_elems, id, 0, settings.count, true);
	        });
	        $_this.on('click', '.open_variantion_attr', function(event) {
	        	var id = parseInt($(this).data('id'));
	        	if( typeof POS_TRANSIENT.grid_current_settings == 'undefined'){
		    		POS_TRANSIENT.grid_current_settings = {};
		    	}
		    	var tax  =  $(this).data('taxonomy');
		    	var term =  $(this).data('term');
		    	POS_TRANSIENT.grid_current_settings[tax] = term;
	        	filter_variantion($_elems, id, 0, settings.count, true);
	        });
	        settings.breadcrumbs_h.click( function(event) {

	        	settings.breadcrumbs.find('*').not(settings.breadcrumbs_h).remove();
	        	filter_hierarchy($_elems, 0);
	        });

	        $('.next-grid-layout').click(function(event) {
	        	var active_page = $('#nav_layout_cycle .activeSlide').data('page');
	        	var count_page  = $('#nav_layout_cycle a').length;
	        	if( (active_page+1) == count_page  )
	        		active_page = 0;
	        	else
	        		active_page++;

	        	$('#nav_layout_cycle .activeSlide').removeClass('activeSlide');
	        	$('#nav_layout_cycle a').eq(active_page).addClass('activeSlide');

	        	if(settings.breadcrumbs.find('span').last().hasClass('attr_title')){
	        		var id = parseInt(settings.breadcrumbs.find('span').last().data('id') );
	        		filter_variantion($_elems, id, (active_page*settings.count), (active_page*settings.count)+settings.count );
	        	}else{
	        		var parent = settings.breadcrumbs.find('.cat_title').last().data('parent');
	        		var filter = get_filter ($_elems, parent, '', (active_page*settings.count), (active_page*settings.count)+settings.count);
	        	}

	        	return false;
	        });
	        $('.previous-grid-layout').click(function(event) {
	        	var active_page = $('#nav_layout_cycle .activeSlide').data('page');
	        	var count_page  = $('#nav_layout_cycle a').length;
	        	
	        	if( (active_page-1) < 0  )
	        		active_page = count_page-1;
	        	else
	        		active_page--;

	        	$('#nav_layout_cycle .activeSlide').removeClass('activeSlide');
	        	$('#nav_layout_cycle a').eq(active_page).addClass('activeSlide');


	        	if(settings.breadcrumbs.find('span').last().hasClass('attr_title')){
	        		var id = parseInt(settings.breadcrumbs.find('span').last().data('id') );
	        		filter_variantion($_elems, id, (active_page*settings.count), (active_page*settings.count)+settings.count );
	        	}else{
		        	var parent = settings.breadcrumbs.find('.cat_title').last().data('parent');
	        		var filter = get_filter ($_elems, parent, '', (active_page*settings.count), (active_page*settings.count)+settings.count);
	        	}

	        	return false;
	        });
	        $('#nav_layout_cycle').on('click', 'a', function(event) {
	        	var active_page = $(this).data('page');
	        	$('#nav_layout_cycle .activeSlide').removeClass('activeSlide');
	        	$(this).addClass('activeSlide');

	        	if(settings.breadcrumbs.find('span').last().hasClass('attr_title')){
	        		var id = parseInt(settings.breadcrumbs.find('span').last().data('id') );
	        		filter_variantion($_elems, id, (active_page*settings.count), (active_page*settings.count)+settings.count );
	        	}else{
	        		var parent = settings.breadcrumbs.find('.cat_title').last().data('parent');
	        		var filter = get_filter ($_elems, parent, '', (active_page*settings.count), (active_page*settings.count)+settings.count);
	        	}
	        	return false;
	        });
	        settings.breadcrumbs.on('click', '.cat_title', function(event) {
	        	if(  !( $(this).is( settings.breadcrumbs_h ) ) ){

		        	var parent = $(this).data('parent');
		        	$(this).nextAll().remove();
		        	var filter = filter_hierarchy($_elems, parent);

	        	}
	        });

	        settings.breadcrumbs.on('click', '.attr_title', function(event) {
	        	var parent = $(this).data('parent');
	        	var tax    = $(this).data('taxonomy');
	        	if( typeof POS_TRANSIENT.grid_current_settings[tax] != 'undefined'){
        			delete POS_TRANSIENT.grid_current_settings[tax];
        		}
	        	$(this).nextAll('.attr_title').each(function(index, el) {
	        		var tax = $(this).data('taxonomy');
	        		if( typeof POS_TRANSIENT.grid_current_settings[tax] != 'undefined'){
	        			delete POS_TRANSIENT.grid_current_settings[tax];
	        		}
	        	});
	        	$(this).nextAll().remove();
	        	$(this).prev('span').remove();
	        	$(this).remove();
	        	filter_variantion($_elems, parent, 0, settings.count, true);
	        });

	        settings.breadcrumbs.on('click', '.prod_title', function(event) {
	        	var id = parseInt($(this).data('id'));
	        	delete POS_TRANSIENT.grid_product_variations;
    			delete POS_TRANSIENT.grid_product_attributes;
    			delete POS_TRANSIENT.grid_product_image;
    			delete POS_TRANSIENT.grid_current_settings;
    			$(this).nextAll().remove();
	        	$(this).prev('span').remove();
	        	$(this).remove();
	        	filter_variantion($_elems, id, 0, settings.count, true);
	        });

	        $_this.data('category_cycle', 'init');
	        $_this.data('settings', settings);
	    });
    };
    function get_filter (elems, parent, title, offset, limit) {
    	var filter  = '';
    	var count   = 0;
    	var f_count = 0;
    	var ul      = $('#grid_layout_cycle ul');
    	elems.hide();
    	$('.add_grid_tile, .open_variantion, .open_variantion_attr').remove();
    	if( pos_grid.grid_id != 'all' && pos_grid.grid_id != 'categories'){
    		count = pos_grid.products_sort.length;
    		var search_pr = pos_grid.products_sort.slice( offset, limit );
    		
	    	window.POS_APP.db.values("products", search_pr ).done(function(records) {
			    if(records){
				  	$.each(records, function(index, val) {
				  		if( typeof val.type != 'undefined' && val.type == 'variable' && val.variations.length > 0 && pos_grid.tile_variables == 'tiles' ){
				  			var $li = $('<li id="product_'+val.id+'" class="title_product open_variantion category_cycle" data-id="'+val.id+'"><span></span><span class="price"></span></li>');	
				  		}else{
					  		var $li = $('<li id="product_'+val.id+'" class="title_product add_grid_tile category_cycle" data-id="'+val.id+'"><span></span><span class="price"></span></li>');
					  	}
	    				$li.find('span').first().html(val.title);
	    				var price = pos_get_price_html(val);
	    				$li.find('span').last().html(price);

	    				if(typeof pos_grid.tile_styles[val.id] != 'undefined' && pos_grid.tile_styles[val.id].style == 'colour' && !pos_grid.hide_text ){
	    					$li.css({
		    					'background-color' : '#' + pos_grid.tile_styles[val.id].background,
		    					'color' : '#' + pos_grid.tile_styles[val.id].colour,
		    					'padding-top' : '20px',
		    				});
	    				}else{
	    					if( typeof val.featured_src != 'undefined' && val.featured_src ){
			    				$li.css({
			    					'background-image' : 'url('+val.featured_src+')'
			    				});
	    					}else{
			    				$li.css({
			    					'background-image' : 'url('+wc_pos_params.def_img+')'
			    				});
			    			}		    				
	    				}
	    				ul.append($li);
				  	});
			    }
			});
    	}
    	else if(pos_grid.grid_id == 'all'){
    		count = pos_grid.products_sort.length;
    		var search_pr = pos_grid.products_sort.slice( offset, limit );
    		
	    	window.POS_APP.db.values("products", search_pr ).done(function(records) {
			    if(records){
				  	$.each(records, function(index, val) {
				  		if(val.type == 'variable' && val.variations.length > 0 && pos_grid.tile_variables == 'tiles' ){
				  			var $li = $('<li id="product_'+val.id+'" class="title_product open_variantion category_cycle" data-id="'+val.id+'"><span></span><span class="price"></span></li>');	
				  		}else{
					  		var $li = $('<li id="product_'+val.id+'" class="title_product add_grid_tile category_cycle" data-id="'+val.id+'"><span></span><span class="price"></span></li>');
					  	}
	    				$li.find('span').first().html(val.title);
	    				var price = pos_get_price_html(val);
	    				$li.find('span').last().html(price);
	    				
	    				if( val.featured_src ){
		    				$li.data('title', val.title).css({
		    					'background-image' : 'url('+val.featured_src+')'
		    				});
	    				}else{
	    					$li.data('title', val.title).css({
		    					'background-image' : 'url('+wc_pos_params.def_img+')'
		    				});
	    				}
	    				ul.append($li);
				  	});
			    }
			});
    	}else{

    		var archive_display = settings.archive_display;
    	
	    	if(parent == 0){
	    		settings.breadcrumbs.find('*').not(settings.breadcrumbs_h).remove();
		    	$.each(settings.hierarchy, function(index, val) {
		    		if(typeof settings.parents[index] != 'undefined'){
			    		if(filter != '')
			    			filter += ', ';
			    		filter += "#category_"+index;
		    		}
		    	});
		    }else {
		    	
		    	if( typeof pos_grid.categories != 'undefined' && typeof pos_grid.categories[parent] != 'undefined' && pos_grid.categories[parent]['display_type'] != '' ){
		    		if( pos_grid.categories[parent]['display_type'] == 'products'){
		    			archive_display = '';		    			
		    		}else{
		    			archive_display = pos_grid.categories[parent]['display_type'];
		    		}
		    	}

		    	if(typeof title != 'undefined' && title != '')
		    		settings.breadcrumbs.append('<span class="sep"> → </span><span data-parent="'+parent+'" class="cat_title">'+title+'</span>');

		    	if( typeof settings.hierarchy[parent] != 'undefined' && archive_display != ''){
			    	$.each( settings.hierarchy[parent], function(index, val) {
			    		if(filter != '')
			    			filter += ', ';
			    		filter += "#category_"+val;
			    	});
		    	}
		    }
		    if( archive_display == 'both' || archive_display == 'subcategories' || ( parent == 0 && archive_display == '' ) ){
			    var $new_elems = elems.filter( filter ).slice( offset, limit );
			    	count      = elems.filter( filter ).length;
			    var f_count    = $new_elems.length;		    
			    $new_elems.show();
		    }
    		
		    var search_pr = []; 

		    if( archive_display == 'both' ){
			    if(parent == 0 && f_count < limit ){
			    	
			    	if( f_count > 0 ){
			    		search_pr = pos_grid.products_sort.slice( 0, limit-f_count );
			    	}else{
			    		var count_per_screen = limit - offset;
			    		var count_pages      = Math.floor(count/count_per_screen);
			    		var count_last_cats  = count - ( count_pages * count_per_screen );
			    		offset -= count_last_cats;
			    		limit  -= count_last_cats;
		    			search_pr = pos_grid.products_sort.slice( offset, limit );
			    	}
			    	count += pos_grid.products_sort.length;	    		
			    	
			    } else if(  typeof settings.relationships[parent] != 'undefined' && f_count < limit ){

			    	if( f_count > 0 ){
			    		search_pr = settings.relationships[parent].slice( 0, limit-f_count );
			    	}else{
			    		var count_per_screen = limit - offset;
			    		var count_pages      = Math.floor(count/count_per_screen);
			    		var count_last_cats  = count - ( count_pages * count_per_screen );
			    		offset -= count_last_cats;
			    		limit  -= count_last_cats;
		    			search_pr = settings.relationships[parent].slice( offset, limit );
			    	}
					count += settings.relationships[parent].length;

			    }
			}else if( archive_display == 'subcategories' && parent != 0 && ( typeof settings.hierarchy[parent] == 'undefined' || settings.hierarchy[parent].length == 0 ) ){
				search_pr = settings.relationships[parent].slice( offset, limit );
				count += settings.relationships[parent].length;
			}else if( archive_display == '' ){
				search_pr = settings.relationships[parent].slice( offset, limit );
				count += settings.relationships[parent].length;
			}


		    if( search_pr.length > 0 ){
		    	
			    window.POS_APP.db.values("products", search_pr ).done(function(records) {
				    if(records){
					  	$.each(records, function(index, val) {
					  		if( typeof val != 'undefined'){
						  		if(val.type == 'variable' && val.variations.length > 0 && pos_grid.tile_variables == 'tiles' ){
						  			var $li = $('<li id="product_'+val.id+'" class="title_product open_variantion category_cycle" data-id="'+val.id+'"><span></span><span class="price"></span></li>');	
						  		}else{
							  		var $li = $('<li id="product_'+val.id+'" class="title_product add_grid_tile category_cycle" data-id="'+val.id+'"><span></span><span class="price"></span></li>');
							  	}
			    				$li.find('span').first().html(val.title);
			    				var price = pos_get_price_html(val);
			    				$li.find('span').last().html(price);
			    				if( val.featured_src ){
				    				$li.data('title', val.title).css({
				    					'background-image' : 'url('+val.featured_src+')'
				    				});
			    				}else{
			    					$li.data('title', val.title).css({
				    					'background-image' : 'url('+wc_pos_params.def_img+')'
				    				});
			    				}
			    				ul.append($li);
		    				}
					  	});
				    }
				});
			}


	    }
	    return count;
    }
    function filter_variantion (elems, parent, offset, limit, pagination) {
    	
    	if( typeof POS_TRANSIENT.grid_product_variations == 'undefined' || typeof POS_TRANSIENT.grid_product_attributes == 'undefined' ){
    		POS_TRANSIENT.grid_product_variations = [];
    		POS_TRANSIENT.grid_product_attributes = {};
    		window.POS_APP.db.get("products", parent ).done(function(record) {
    			
    			if( record.images.length > 0 ){
    				POS_TRANSIENT.grid_product_image = record.images[0].src;
    			}else{
    				POS_TRANSIENT.grid_product_image = wc_pos_params.def_img;
    			}
    			$.each(record.variations, function(index, val) {

					var attributes = {};
					$.each(val.attributes, function(i, attr) {
						var slug = attr.slug;
						attributes[slug] = attr.option;
					});
					POS_TRANSIENT.grid_product_variations[index] = {attributes : attributes};
					POS_TRANSIENT.grid_product_variations[index]['variation_is_active'] = true;
					POS_TRANSIENT.grid_product_variations[index]['variation_id'] = val.id;

				});
				$.each(record.attributes, function(index, attr) {

					if( attr.variation === true){
						POS_TRANSIENT.grid_product_attributes[attr.slug] = {
							name    : attr.name,
							options : attr.options
						};
					}

				});

				if( pagination === true ){
		    		settings.breadcrumbs.append('<span class="sep"> → </span><span data-id="'+parent+'" class="prod_title">'+record.title+'</span>');
		    	}

				build_filter_variantion (elems, parent, offset, limit, pagination);
    			
    		});    		
    	}else{
    		build_filter_variantion (elems, parent, offset, limit, pagination);
    	}
    }

    function build_filter_variantion (elems, parent, offset, limit, pagination) {
    	if( typeof POS_TRANSIENT.grid_current_settings == 'undefined'){
    		POS_TRANSIENT.grid_current_settings = {};
    	}
    	var ul     = $('#grid_layout_cycle ul');
    	elems.hide();
    	$('.add_grid_tile, .open_variantion, .open_variantion_attr').remove();
    	var all_attributes_chosen  = true,
			some_attributes_chosen = false,
			current_settings       = POS_TRANSIENT.grid_current_settings,
			$form                  = $( this ),
			product_id             = parseInt( parent ),
			product_variations    = POS_TRANSIENT.grid_product_variations;
			
		if( sizeof(current_settings) != sizeof(POS_TRANSIENT.grid_product_attributes) ){
			all_attributes_chosen = false;
		}

		var matching_variations = APP.find_matching_variations( product_variations, current_settings );
		if ( all_attributes_chosen ) {

		    var variation = matching_variations.shift();

		    if ( variation ) {
		    	//console.log('end', variation);
		    	var variation_id = variation.variation_id;
		    	var product_id   = parseInt(parent);
		    	APP.addToCart(product_id, 1, variation_id, current_settings);

		    	settings.breadcrumbs.find('*').not(settings.breadcrumbs_h).remove();
		    	delete POS_TRANSIENT.grid_product_variations;
    			delete POS_TRANSIENT.grid_product_attributes;
    			delete POS_TRANSIENT.grid_product_image;
    			delete POS_TRANSIENT.grid_current_settings;
		    	filter_hierarchy(elems, 0);
		    }
		    
		}else{
			var build = false;
			$.each( POS_TRANSIENT.grid_product_attributes , function(tax, attr) {
				if( typeof current_settings[tax] == 'undefined' && !build ){
					var current_attr_name = tax;
					var variations        = matching_variations;
					var _options          = [];
					var all_opt           = false;
					// Loop through variations
			        for ( var num in variations ) {

			          if ( typeof( variations[ num ] ) !== 'undefined' ) {

			            var attributes = variations[ num ].attributes;

			            for ( var attr_name in attributes ) {
			              if ( attributes.hasOwnProperty( attr_name ) ) {
			                var attr_val = attributes[ attr_name ];

			                if ( attr_name === current_attr_name ) {

			                  var variation_active = '';

			                  if ( variations[ num ].variation_is_active ) {
			                    variation_active = 'enabled';
			                  }

			                  if ( attr_val != '' ) {
			                  	_options.push(attr_val)

			                  }else{
			                  	all_opt = true;
			                  }

			                }
			              }
			            }
			          }
			        }


					build = true;

					if( pagination === true ){
			    		settings.breadcrumbs.append('<span class="sep"> → </span><span data-id="'+parent+'" class="attr_title" data-taxonomy="'+tax+'">'+attr.name+'</span>');
			    	}
			    	var options   = attr.options;
		    		var count     = _options.length;
			    	var _options  = _options.slice( offset, limit );
			    	if( all_opt ){
			    		count     = options.length;
			    		options   = options.slice( offset, limit );
			    	}
			    	
					$.each( options, function(index, val) {
						if( in_array(val.slug, _options) || all_opt ){

							var title = val.name;
							var $li = $('<li id="attribute_'+val.slug+'" class="title_product open_variantion_attr category_cycle" data-id="'+parent+'" data-taxonomy="'+tax+'" data-term="'+val.slug+'"><span></span></li>');
		    				$li.find('span').html(title);
		    				$li.data('title', title).css({
		    					'background-image' : 'url('+POS_TRANSIENT.grid_product_image+')'
		    				});
		    				ul.append($li);

		    			}
					});

					if(pagination === true){
					    if(count > settings.count){
					    	var pages = Math.ceil( count/settings.count );
					    	$('.previous-next-toggles').show();
					    	var nav = '';
					    	for (var i = 1; i <= pages; i++) {
					    		nav += '<a href="#" data-page="'+(i-1)+'">'+i+'</a>';
					    	};
					    	$('#nav_layout_cycle').html(nav);
					    	$('#nav_layout_cycle a').first().addClass('activeSlide');
					    }else{
					    	$('.previous-next-toggles').hide();
					    	$('#nav_layout_cycle').html('');
					    }
					}
				}

			});
		}


    }


    function _filter_variantion (elems, parent, offset, limit, pagination) {
    	var filter = '';
    	var ul     = $('#grid_layout_cycle ul');
    	elems.hide();
    	$('.add_grid_tile, .open_variantion, .open_variantion_attr').remove();
//    	var search_pr = pos_grid.products_sort.slice( offset, limit );
    	window.POS_APP.db.get("products", parent ).done(function(record) {
    		
		    if(record && record.type == 'variable' && record.variations.length > 0){

		    }
		    if(record && record.type == 'variable' && count > 0){
		    	var variations = record.variations.slice( offset, limit );
		    	if( pagination === true ){
		    		settings.breadcrumbs.append('<span class="sep"> → </span><span data-id="'+parent+'" class="prod_title">'+record.title+'</span>');
		    	}

		    	if(variations.length){
				  	$.each(variations, function(index, val) {
				  		var title = '';
				  		if(val.attributes){
				  			$.each(val.attributes, function(i, attr) {
				  				if(title != ''){
				  					title += ', ';
				  				}
				  				if(attr.option == ''){
				  					title += attr.name + ' - any';
				  				}else{
				  					title += attr.name + ' - ' + attr.option;
				  				}
				  			});
				  		}
				  		var $li = $('<li id="product_'+val.id+'" class="title_product add_grid_tile category_cycle" data-id="'+parent+'" data-varid="'+val.id+'"><span></span></li>');
	    				$li.find('span').html(title);
	    				if( val.image.length ){
		    				$li.data('title', title).css({
		    					'background-image' : 'url('+val.image[0].src+')'
		    				});
	    				}else{
		    				$li.data('title', title).css({
		    					'background-image' : 'url('+wc_pos_params.def_img+')'
		    				});
	    				}
	    				
	    				ul.append($li);
				  	});
			  	}

		    }
		    if(pagination === true){
			    if(count > settings.count){
			    	var pages = Math.ceil( count/settings.count );
			    	$('.previous-next-toggles').show();
			    	var nav = '';
			    	for (var i = 1; i <= pages; i++) {
			    		nav += '<a href="#" data-page="'+(i-1)+'">'+i+'</a>';
			    	};
			    	$('#nav_layout_cycle').html(nav);
			    	$('#nav_layout_cycle a').first().addClass('activeSlide');
			    }else{
			    	$('.previous-next-toggles').hide();
			    	$('#nav_layout_cycle').html('');
			    }
			}
		});
    }
    function filter_hierarchy (elems, parent, title) {
    	var count = get_filter (elems, parent, title, 0, settings.count);

	    if(count > settings.count){
	    	var pages = Math.ceil( count/settings.count );
	    	$('.previous-next-toggles').show();
	    	var nav = '';
	    	for (var i = 1; i <= pages; i++) {
	    		nav += '<a href="#" data-page="'+(i-1)+'">'+i+'</a>';
	    	};
	    	$('#nav_layout_cycle').html(nav);
	    	$('#nav_layout_cycle a').first().addClass('activeSlide');
	    }else{
	    	$('.previous-next-toggles').hide();
	    	$('#nav_layout_cycle').html('');
	    }
    }
 
}( jQuery ));