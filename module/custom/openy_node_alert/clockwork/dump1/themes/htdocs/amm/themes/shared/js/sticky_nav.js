// *** The Super Sticky Nav *** 
// 
// Usage - To your init_template function add:
// 
//   prepare_navs(nav1, nav2, nav3, etc.)
// 
// 
// The arguments should be the names of the divs containing your navs,
// so if your navs are in <div id="main_nav"> and <div id="sub_nav">
// your prepare_navs function would look like this:
// 
//   prepare_navs('main_nav', 'sub_nav')
// 
// Sticky Nav expects that your navs are implemented as an unordered list:
// 
//   <div id="main_nav">
// 		<ul>
// 			<li><a href="/">Menu Item</a></li>
// 		</ul>
//   </div>
// 
// Stick Nav adds the current class to the list item (<li>) not the anchor (<a>) in case you
// need complex changes (like swapping out images). This behavior can be changed (see code).
// 
// Note: sticky nav does nothing in preview mode as hierarchical link recognition is not possible.

// Set these appropriately
CURRENT_CLASS = 'current'
PARENT_CLASS = 'parent'

// home is usually root in the sitemap, but acts as level 1 on the site
HOME_ACTS_AS_LEVEL_1 = true 	

function make_current(li) {
	//put in all the stuff that's required to make your current <li> properly styled
	li.addClass(CURRENT_CLASS)
}

function make_parent(li) {
	//put in all the stuff that's required to make your parent <li> properly styled
	// uncomment line below to do the same for parents as for current
	// make_current(li)
	li.addClass(PARENT_CLASS)
}


function prepare_navs() {
	if (location.href.match(/preview/)) {return}
	
	url_tree = parse_url(location.href)

	
	//find the current link and set it
	set_current(arguments, url_tree)
	
	// Remove home link if home is level 1
	if (HOME_ACTS_AS_LEVEL_1) {url_tree.pop();}
	
	// Remove current link
	url_tree.shift();
	
	// Set all the parent links
	set_parents(arguments, url_tree)
	
}

function set_current(divs, url_tree) {
	CWjQuery.each(divs,
		function(i, div) {
			li = find_list_item(div, url_tree[0])
			if (li) {
				make_current(li)
				//remove return statement if multiple divs may have current nav link
				return
			}
		})
}

function set_parents(divs, url_tree) {
	CWjQuery.each(url_tree,
		function(i, url) {
			CWjQuery.each(divs,
				function(i, div) {
					li = find_list_item(div, url)
					if (li) {
						make_parent(li)
					}
				})
		})
}

function parse_url(href) {
	//Divides URL into its hierarchy and returns an array
	arr = []
	function parse_branches(part) {
		arr.push(part)
		next = part.match("([^/]+///?.*?/)[^/]*/?$").pop();
		if (next == part) { return }
		parse_branches(next)
	}
	parse_branches(href)
	return arr
}

function find_list_item(div_name, match) {
	// Takes a div containing a menu and a URL and returns the <li>
	// containing the matching <a>. Returns false if nothing is found

	div = CWjQuery("#" + div_name)
	result = CWjQuery.grep(div.find("a"), function(a,i) { 
		if (a.href.match("[?#]")) {
			return a.href == match 
		} else return a.href == match.match("[^?#]+").pop()
	})
	if (result.length > 0) {
		return CWjQuery(result[0].parentNode)
	} else { return false }
}


