<?php
/*
Plugin Name: Sidebar Generator
Plugin URI: http://www.getson.info
Description: This plugin generates as many sidebars as you need. Then allows you to place them on any page you wish.
Version: 1.0.1
Author: Kyle Getson
Author URI: http://www.kylegetson.com
Copyright (C) 2009 Clickcom, Inc.
*/

/*
Copyright (C) 2009 Kyle Robert Getson, kylegetson.com and getson.info

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class sidebar_generator {
	
	function sidebar_generator(){
		add_action('init',array('sidebar_generator','init'));
		add_action('admin_menu',array('sidebar_generator','admin_menu'));
		add_action('admin_print_scripts', array('sidebar_generator','admin_print_scripts'));
		add_action('wp_ajax_add_sidebar', array('sidebar_generator','add_sidebar') );
		add_action('wp_ajax_remove_sidebar', array('sidebar_generator','remove_sidebar') );
			
		//edit posts/pages
		add_action('edit_form_advanced', array('sidebar_generator', 'edit_form'));
		add_action('edit_page_form', array('sidebar_generator', 'edit_form'));
		
		//save posts/pages
		add_action('edit_post', array('sidebar_generator', 'save_form'));
		add_action('publish_post', array('sidebar_generator', 'save_form'));
		add_action('save_post', array('sidebar_generator', 'save_form'));
		add_action('edit_page_form', array('sidebar_generator', 'save_form'));
		
		
		//add_action('get_sidebar',array('sidebar_generator', 'get_sidebar'));
	}
	
	function init(){
		//go through each sidebar and register it
	    $sidebars = sidebar_generator::get_sidebars();
	    

	    if(is_array($sidebars)){
			foreach($sidebars as $sidebar){
				$sidebar_class = sidebar_generator::name_to_class($sidebar);
				register_sidebar(array(
			    	'name'=>$sidebar,
			    	'before_widget' => '<li id="%1$s" class="widget scg_widget '.$sidebar_class.' %2$s">',
		   			'after_widget' => '</li>',
		   			'before_title' => '<h2 class="widgettitle scg_title">',
					'after_title' => '</h2>',
		    	));
			}
		}
	}
	
	function admin_print_scripts(){
		wp_print_scripts( array( 'sack' ));
		?>
			<script>
				function add_sidebar( sidebar_name )
				{
					
					var mysack = new sack("<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php" );    
				
				  	mysack.execute = 1;
				  	mysack.method = 'POST';
				  	mysack.setVar( "action", "add_sidebar" );
				  	mysack.setVar( "sidebar_name", sidebar_name );
				  	mysack.encVar( "cookie", document.cookie, false );
				  	mysack.onError = function() { alert('Ajax error. Cannot add sidebar' )};
				  	mysack.runAJAX();
					return true;
				}
				
				function remove_sidebar( sidebar_name,num )
				{
					
					var mysack = new sack("<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php" );    
				
				  	mysack.execute = 1;
				  	mysack.method = 'POST';
				  	mysack.setVar( "action", "remove_sidebar" );
				  	mysack.setVar( "sidebar_name", sidebar_name );
				  	mysack.setVar( "row_number", num );
				  	mysack.encVar( "cookie", document.cookie, false );
				  	mysack.onError = function() { alert('Ajax error. Cannot add sidebar' )};
				  	mysack.runAJAX();
					//alert('hi!:::'+sidebar_name);
					return true;
				}
			</script>
		<?php
	}
	
	function add_sidebar(){
		$sidebars = sidebar_generator::get_sidebars();
		$name = str_replace(array("\n","\r","\t"),'',$_POST['sidebar_name']);
		$id = sidebar_generator::name_to_class($name);
		if(isset($sidebars[$id])){
			die("alert('Sidebar already exists, please use a different name.')");
		}
		
		$sidebars[$id] = $name;
		sidebar_generator::update_sidebars($sidebars);
		
		$js = "
			var tbl = document.getElementById('sbg_table');
			var lastRow = tbl.rows.length;
			// if there's no header row in the table, then iteration = lastRow + 1
			var iteration = lastRow;
			var row = tbl.insertRow(lastRow);
			
			// left cell
			var cellLeft = row.insertCell(0);
			var textNode = document.createTextNode('$name');
			cellLeft.appendChild(textNode);
			
			//middle cell
			var cellLeft = row.insertCell(1);
			var textNode = document.createTextNode('$id');
			cellLeft.appendChild(textNode);
			
			//var cellLeft = row.insertCell(2);
			//var textNode = document.createTextNode('[<a href=\'javascript:void(0);\' onclick=\'return remove_sidebar_link($name);\'>Remove</a>]');
			//cellLeft.appendChild(textNode)
			
			var cellLeft = row.insertCell(2);
			removeLink = document.createElement('a');
      		linkText = document.createTextNode('remove');
			removeLink.setAttribute('onclick', 'remove_sidebar_link(\'$name\')');
			removeLink.setAttribute('href', 'javacript:void(0)');
        
      		removeLink.appendChild(linkText);
      		cellLeft.appendChild(removeLink);

			
		";
		
		
		die( "$js");
	}
	
	function remove_sidebar(){
		$sidebars = sidebar_generator::get_sidebars();
		$name = str_replace(array("\n","\r","\t"),'',$_POST['sidebar_name']);
		$id = sidebar_generator::name_to_class($name);
		if(!isset($sidebars[$id])){
			die("alert('Sidebar does not exist.')");
		}
		$row_number = $_POST['row_number'];
		unset($sidebars[$id]);
		sidebar_generator::update_sidebars($sidebars);
		$js = "
			var tbl = document.getElementById('sbg_table');
			tbl.deleteRow($row_number)

		";
		die($js);
	}
	
	function admin_menu(){
		add_submenu_page('themes.php', 'Sidebars', 'Sidebars', 'manage_options', __FILE__, array('sidebar_generator','admin_page'));
	}
	
	function admin_page(){
		?>
		<script>
			function remove_sidebar_link(name,num){
				answer = confirm("Are you sure you want to remove " + name + "?\nThis will remove any widgets you have assigned to this sidebar.");
				if(answer){
					//alert('AJAX REMOVE');
					remove_sidebar(name,num);
				}else{
					return false;
				}
			}
			function add_sidebar_link(){
				var sidebar_name = prompt("Sidebar Name:","");
				//alert(sidebar_name);
				add_sidebar(sidebar_name);
			}
		</script>
		<div class="wrap">
			<h2>Sidebar Generator</h2>
			<p>
				The sidebar name is for your use only. It will not be visible to any of your visitors. 
				A CSS class is assigned to each of your sidebar, use this styling to customize the sidebars.
			</p>
			<br />
			<div class="add_sidebar">
				<a href="javascript:void(0);" onclick="return add_sidebar_link()" title="Add a sidebar">+ Add Sidebar</a>
			</div>
			<br />
			<table class="widefat page" id="sbg_table" style="width:600px;">
				<tr>
					<th>Name</th>
					<th>CSS class</th>
					<th>Remove</th>
				</tr>
				<?php
				$sidebars = sidebar_generator::get_sidebars();
				//$sidebars = array('bob','john','mike','asdf');
				if(is_array($sidebars) && !empty($sidebars)){
					$cnt=0;
					foreach($sidebars as $sidebar){
						$alt = ($cnt%2 == 0 ? 'alternate' : '');
				?>
				<tr class="<?php echo $alt?>">
					<td><?php echo $sidebar; ?></td>
					<td><?php echo sidebar_generator::name_to_class($sidebar); ?></td>
					<td><a href="javascript:void(0);" onclick="return remove_sidebar_link('<?php echo $sidebar; ?>',<?php echo $cnt+1; ?>);" title="Remove this sidebar">remove</a></td>
				</tr>
				<?php
						$cnt++;
					}
				}else{
					?>
					<tr>
						<td colspan="3">No Sidebars defined</td>
					</tr>
					<?php
				}
				?>
			</table>
			<br /><br />
			<div class="donate">
				<h5>Donate</h5>
				<p>
					Support the further development of this plugin.
				</p>
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
				<input type="hidden" name="cmd" value="_s-xclick">
				<input type="hidden" name="hosted_button_id" value="9271055">
				<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
				<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
				</form>

			</div>
		</div>
		<?php
	}
	
	/**
	 * for saving the pages/post
	*/
	function save_form($post_id){
		$is_saving = $_POST['sbg_edit'];
		if(!empty($is_saving)){
			delete_post_meta($post_id, 'sbg_selected_sidebar');
			add_post_meta($post_id, 'sbg_selected_sidebar', $_POST['sidebar_generator']);
		}		
	}
	
	function edit_form(){
	    global $post;
	    $post_id = $post;
	    if (is_object($post_id)) {
	    	$post_id = $post_id->ID;
	    }
	    $selected_sidebar = get_post_meta($post_id, 'sbg_selected_sidebar', true);
		?>
	 
	<div id='sbg-sortables' class='meta-box-sortables'>
		<div id="sbg_box" class="postbox " >
			<div class="handlediv" title="Click to toggle"><br /></div><h3 class='hndle'><span>Sidebars</span></h3>
			<div class="inside">
				<div class="sbg_container">
					<input name="sbg_edit" type="hidden" value="sbg_edit" />
					
					<p>
						Select the sidebar you wish to display on this page. (leave unselected to use the default sidebar)
					</p>
					<p>
						<select name="sidebar_generator">
							<option value="0"<?php if($selected_sidebar == ''){ echo " selected";} ?>>Select A Sidebar</option>
						<?php
						$sidebars = sidebar_generator::get_sidebars();
						if(is_array($sidebars) && !empty($sidebars)){
							foreach($sidebars as $sidebar){
								if($selected_sidebar == $sidebar){
									echo "<option value='$sidebar' selected>$sidebar</option>\n";
								}else{
									echo "<option value='$sidebar'>$sidebar</option>\n";
								}
							}
						}
						?>
						</select>
					</p>
				</div>
			</div>
		</div>
	</div>

		<?php
	}
	
	/**
	 * called by the action get_sidebar. this is what places this into the theme
	*/
	function get_sidebar(){
		if(!is_singular()){
			dynamic_sidebar();
			return true;//dont do anything
		}
		global $wp_query;
		$post = $wp_query->get_queried_object();
		$selected_sidebar = get_post_meta($post->ID, 'sbg_selected_sidebar', true);
		if($selected_sidebar != '' && $selected_sidebar != "0"){
			echo "\n\n<!-- begin generated sidebar [$selected_sidebar] -->\n";
			//echo "<!-- selected: $selected_sidebar -->";
			dynamic_sidebar($selected_sidebar);
			echo "\n<!-- end generated sidebar -->\n\n";			
		}else{
			dynamic_sidebar();
		}
	}
	
	/**
	 * replaces array of sidebar names
	*/
	function update_sidebars($sidebar_array){
		$sidebars = update_option('sbg_sidebars',$sidebar_array);
	}	
	
	/**
	 * gets the generated sidebars
	*/
	function get_sidebars(){
		$sidebars = get_option('sbg_sidebars');
		return $sidebars;
	}
	function name_to_class($name){
		$class = str_replace(array(' ',',','.','"',"'",'/',"\\",'+','=',')','(','*','&','^','%','$','#','@','!','~','`','<','>','?','[',']','{','}','|',':',),'',$name);
		return $class;
	}
}
$sbg = new sidebar_generator;

function generated_dynamic_sidebar(){
	sidebar_generator::get_sidebar();	
	return true;
}
?>