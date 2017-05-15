<?php
/*
BP Group Calendar
*/

//------------------------------------------------------------------------//

//---Config---------------------------------------------------------------//

//------------------------------------------------------------------------//

//include calendar class
require_once( WP_PLUGIN_DIR . '/bp-group-calendar/groupcalendar/calendar.class.php' );

//------------------------------------------------------------------------//

//---Hook-----------------------------------------------------------------//

//------------------------------------------------------------------------//

//check for activating

add_action( 'admin_init', 'bp_group_calendar_make_current' );

add_action( 'groups_group_after_save', 'bp_group_calendar_settings_save' );
add_action( 'bp_after_group_settings_creation_step', 'bp_group_calendar_settings' );
add_action( 'bp_after_group_settings_admin', 'bp_group_calendar_settings' );
add_action( 'wp_enqueue_scripts', 'bp_group_calendar_js' );
add_action( 'wp_head', 'bp_group_calendar_js_output' );
add_action( 'groups_screen_notification_settings', 'bp_group_calendar_notification_settings' );

//activity stream
add_action( 'groups_register_activity_actions', 'bp_group_calendar_reg_activity' );
add_action( 'groups_new_calendar_event', 'groups_update_last_activity' );
add_action( 'groups_edit_calendar_event', 'groups_update_last_activity' );


//widgets
add_action( 'widgets_init', create_function( '', 'return register_widget("BP_Group_Calendar_Widget");' ) );
add_action( 'widgets_init', create_function( '', 'return register_widget("BP_Group_Calendar_Widget_Single");' ) );
add_action( 'widgets_init', create_function( '', 'return register_widget("BP_Group_Calendar_Widget_User_Groups");' ) );

//------------------------------------------------------------------------//

//---Functions------------------------------------------------------------//

//------------------------------------------------------------------------//

/* **** as21 NEED FUTURE **** */
/* **** as21  my functions **** */

function as21_groups_action_join_group() {

	// echo "==as21_groups_action_join_group==";
	if ( !bp_is_groups_component() ) return false;

	// // Nonce check.
	// if ( !check_admin_referer( 'groups_join_group' ) )
	// 	return false;

	$bp = buddypress();

	// checking values
	// echo "<br> bp_loggedin_user_id=".bp_loggedin_user_id();
	// echo "<br> bp->groups->current_group->id=".$bp->groups->current_group->id;
	// var_dump( groups_is_user_member( bp_loggedin_user_id(), $bp->groups->current_group->id ) );
	// var_dump(groups_is_user_banned( bp_loggedin_user_id(), $bp->groups->current_group->id) );
	// var_dump( groups_join_group( $bp->groups->current_group->id ) );

	// Skip if banned or already a member.
	if ( !groups_is_user_member( bp_loggedin_user_id(), $bp->groups->current_group->id ) && !groups_is_user_banned( bp_loggedin_user_id(), $bp->groups->current_group->id ) ) {

		/*// User wants to join a group that is not public.
		if ( $bp->groups->current_group->status != 'public' ) {
			if ( !groups_check_user_has_invite( bp_loggedin_user_id(), $bp->groups->current_group->id ) ) {
				bp_core_add_message( __( 'There was an error joining the group.', 'buddypress' ), 'error' );
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
			}
		}
		*/

		/*// User wants to join any group.
		if ( !groups_join_group( $bp->groups->current_group->id ) )
			bp_core_add_message( __( 'There was an error joining the group.', 'buddypress' ), 'error' );
		else
			bp_core_add_message( __( 'You joined the group!', 'buddypress' ) );
		*/
		// bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
		groups_join_group( $bp->groups->current_group->id );
	}

}


function as21_bp_group_calendar_event_add_action_message( $user_id, $event_id, $task_title, $signup_time ) {

	global $wpdb;
	$event_id = (int)$event_id;
	$event = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bp_groups_calendars WHERE id = %d", $event_id ) );

	// echo "====as21_bp_group_calendar_event_add_action_message==\r\n";
	// echo $user_id."\r\n ";
	// echo $event_id."\r\n ";
	// echo $event_date."\r\n ";
	// echo $event_title."\r\n ";
	// echo $event->group_id."\r\n ";

	$url = bp_group_calendar_create_event_url( $event_id );
	$date = bgc_date_display( $event->event_time );
	$group = groups_get_group( array( 'group_id' => $event->group_id) );

	// echo $url."\r\n ";
	// echo $date."\r\n ";
	// echo $group->name."\r\n ";

	/* Record this in group activity stream */
	$action  = sprintf( __( '%s Signed up to event task %s:', 'groupcalendar' ), bp_core_get_userlink( $user_id ), '<a href="' . bp_get_group_permalink( $event->group_id ) .$group->slug. '">' . esc_attr( $group->name ) . '</a>' );
	$content = '<a href="' . $url . '" title="' . __( 'View Event', 'groupcalendar' ) . '">' . $date . ': ' . stripslashes( $event->event_title ) . '</a> Task: '.$task_title.' Time: '.$signup_time;

	// echo $action."\r\n ";
	// echo $content."\r\n ";

	$activity_id = bp_activity_add( array(
		'action'            => $action,
		'content'           => $content,
		'primary_link'      => $url,
		'type'              => 'joined_cal_event_task',
		'item_id'           => $event->group_id,
		'component' => 'groups',
		'secondary_item_id' => $event_id
	) );
	// var_dump($activity_id);
	// exit;
}

/* **** as21  my functions **** */



// add_action("a21_bgc_message_thankyou","a21_bgc_message_thankyou");

function a21_bgc_message_thankyou(){

	global $wpdb,$bp;
	$user_id_isnotlogin = $bp->displayed_user->id;

	// echo "<h3>TESTING a21_bgc_output_thankyou <br>today:".date( 'Y-m-d H:i:s' )."</h3>";
	// event_time >= '" . date( 'Y-m-d H:i:s' )
	$events = $wpdb->get_results( $wpdb->prepare(
		"SELECT * 
		FROM {$wpdb->prefix}bp_groups_calendars
		WHERE event_time <= %s
		ORDER BY id ASC",
		date( 'Y-m-d H:i:s' )
	) );
	// alex_debug(1,1,"",$events);

	$i = 1;
	$event_to_timeline = array();

	if( !empty($events) ){

		foreach ($events as $event) {

			// echo $i."- ".$event->event_title." ".$event->event_time; echo "<br>";
			// $get_tasks_by_event_id = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bp_groups_bgc_tasks WHERE event_id = %d AND ids_vols REGEXP
			// 	'^2,|,2,|,2$|^2:|:2:|:2$|^2$'|:2,|,2: ", (int)$event->id ) );
			// get all events where user was volunteer and date expired
			$get_tasks_by_event_id = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bp_groups_bgc_tasks WHERE event_id = %d AND ids_vols REGEXP
				'^{$user_id_isnotlogin},|,{$user_id_isnotlogin},|,{$user_id_isnotlogin}$|^{$user_id_isnotlogin}:|:{$user_id_isnotlogin}:|:{$user_id_isnotlogin}$|^{$user_id_isnotlogin}$|:{$user_id_isnotlogin},|,{$user_id_isnotlogin}:' LIMIT 1", (int)$event->id ) );
			// deb_last_query();
			// alex_debug(0,1,"",$get_tasks_by_event_id,1);

			if( !empty($get_tasks_by_event_id) ) {

				// $event_time = bgc_date_display( $event->event_time, get_option( 'date_format' ) . __( ' \a\t ', 'groupcalendar' ) . get_option( 'time_format' ) );
				if( empty($event->thank_you )) return false;

				$get_event_image = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM " . $wpdb->base_prefix . "bp_groups_groupmeta
            	WHERE group_id=%d AND meta_key=%s LIMIT 1", (int)$event->id, 'a21_bgc_event_image') );

				$event_time = strtotime($event->event_time);
				$event_time = date("d M Y",$event_time);
				$group = groups_get_group(array( 'group_id' => $event->group_id ));
				$group_permalink =  'http://'.$_SERVER['HTTP_HOST'] . '/' . bp_get_groups_root_slug() . '/' . $group->slug . '/';
				$avatar_options = array ( 'item_id' => $group->id, 'object' => 'group', 'type' => 'full', 'avatar_dir' => 'group-avatars', 'alt' => 'Group avatar', 'css_id' => 1234, 'class' => 'avatar', 'width' => 50, 'height' => 50, 'html' => false );
				$gr_avatar = bp_core_fetch_avatar($avatar_options);

				// echo $debug = $event->thank_you."..".$event->event_title."___".$event_time." ".$html."<br>";
				/*
				$event_to_timeline[]['title'] = $event->event_title;
				$event_to_timeline[]['group_id'] = $event->group_id;
				$event_to_timeline[]['thank_you'] = $event->thank_you;
				$event_to_timeline[]['event_time'] = $event->event_time;
				*/

				?>
			      <li>
			          <div class="timeliner_element is_event_thank_you">
						<!-- <span class="timeliner_element2 <?php // echo $group->name;?>"></span> -->						        
			              <div class="timeliner_title">
			                  <span class="timeliner_label"><?php echo stripslashes($event->event_title);?></span>
			                  <span class="timeliner_date"><?php echo $event_time;?></span>
			              </div>
			              <div class="content">
			              	   <?php if( !empty($get_event_image) ) {
			              	    echo "<a href='".$group_permalink."/callout/".$event->event_slug."' class='event_image' target='_blank'><img src='".$get_event_image."' /></a>";
			              	    echo "<p>".stripslashes($event->thank_you)."</p>";
			              	    }else echo stripslashes($event->thank_you);
			              	    ?>
			              </div>
			              <div class="readmore">
			              	  <?php if($gr_avatar):?> <div id="alex_gr_avatar"><?php echo $gr_avatar;?></div><?php endif;?>
			              	  <?php if($group_permalink):?> <div id="alex_gr_link"><?php echo $group_permalink;?></div><?php endif;?>
			              	  <?php if($group->name):?> 
			              	  	 <div id="alex_gr_name_select"><?php echo $group->name;?></div>
			              	  <?php endif;?>

<!-- 			              <?php if($group->id):?> <div id="alex_gr_id_select"><?php echo $group->id;?></div><?php endif;?>
			              	  <span class="alex_item_id"><?php echo $field->ID;?></span>
			                  <a class="btn btn-primary" href="javascript:void(0);" ><i class="fa fa-pencil fa fa-white"></i></a>
			                  <a class="btn btn-bricky" href="javascript:void(0);" ><i class="fa fa-trash fa fa-white"></i></a>
			                  <a href="#" class="btn btn-info">
			                      Read More <i class="fa fa-arrow-circle-right"></i>
			                  </a>
 -->			    
 				          </div>
			          </div>
			      </li>
			<?php
			}
			// echo "<br>";
			$i++;
		}
	}

}

add_action("a21_bgc_message_thankyou","a21_bgc_message_thankyou_in_timeline");

function a21_bgc_message_thankyou_in_timeline(){

	global $wpdb,$bp;
	$user_id_isnotlogin = $bp->displayed_user->id;

	// echo "<h3>TESTING a21_bgc_output_thankyou <br>today:".date( 'Y-m-d H:i:s' )."</h3>";
	// event_time >= '" . date( 'Y-m-d H:i:s' )
	$events = $wpdb->get_results( $wpdb->prepare(
		"SELECT * 
		FROM {$wpdb->prefix}bp_groups_calendars
		WHERE event_time <= %s
		ORDER BY id ASC",
		date( 'Y-m-d H:i:s' )
	) );
	// alex_debug(1,1,"",$events);

	$i = 1;
	$event_to_timeline = array();

	if( !empty($events) ){

		foreach ($events as $event) {

			// echo $i."- ".$event->event_title." ".$event->event_time; echo "<br>";
			// $get_tasks_by_event_id = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bp_groups_bgc_tasks WHERE event_id = %d AND ids_vols REGEXP
			// 	'^2,|,2,|,2$|^2:|:2:|:2$|^2$'|:2,|,2: ", (int)$event->id ) );
			// get all events where user was volunteer and date expired
			$get_tasks_by_event_id = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bp_groups_bgc_tasks WHERE event_id = %d AND ids_vols REGEXP
				'^{$user_id_isnotlogin},|,{$user_id_isnotlogin},|,{$user_id_isnotlogin}$|^{$user_id_isnotlogin}:|:{$user_id_isnotlogin}:|:{$user_id_isnotlogin}$|^{$user_id_isnotlogin}$|:{$user_id_isnotlogin},|,{$user_id_isnotlogin}:' LIMIT 1", (int)$event->id ) );
			// deb_last_query();
			// alex_debug(0,1,"get_tasks_by_event_id",$get_tasks_by_event_id);

			if( !empty($get_tasks_by_event_id) ) {

				// $event_time = bgc_date_display( $event->event_time, get_option( 'date_format' ) . __( ' \a\t ', 'groupcalendar' ) . get_option( 'time_format' ) );
				if( empty($event->thank_you )) return false;

				$get_event_image = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM " . $wpdb->base_prefix . "bp_groups_groupmeta
            	WHERE group_id=%d AND meta_key=%s LIMIT 1", (int)$event->id, 'a21_bgc_event_image') );

				$event_time = strtotime($event->event_time);
				$event_time = date("d M Y",$event_time);
				$group = groups_get_group(array( 'group_id' => $event->group_id ));
				$group_permalink =  'http://'.$_SERVER['HTTP_HOST'] . '/' . bp_get_groups_root_slug() . '/' . $group->slug . '/';
				$avatar_options = array ( 'item_id' => $group->id, 'object' => 'group', 'type' => 'full', 'avatar_dir' => 'group-avatars', 'alt' => 'Group avatar', 'css_id' => 1234, 'class' => 'avatar', 'width' => 50, 'height' => 50, 'html' => false );
				$gr_avatar = bp_core_fetch_avatar($avatar_options);

				$class="purple";
				$alex_tl_grp_id = "99999";
				// if in wp_posts not exsist current id event then it insert in table

				$is_cur_thankyou = $wpdb->get_var($wpdb->prepare("SELECT guid FROM {$wpdb->posts} WHERE guid=%d AND post_type=%s",$event->id,'alex_timeline'));
				// deb_last_query();
				// var_dump($is_cur_thankyou[0]);

				if( empty($is_cur_thankyou[0])) {
					echo "exist ".$is_cur_thankyou[0];
					$last_post_id = $wpdb->get_var( "SELECT MAX(`ID`) FROM {$wpdb->posts}");
					/*
					$wpdb->insert(
						$wpdb->posts,
						array( 'post_date'=>$event->event_time ,'ID' => $last_post_id+1, 'post_title' => $event->event_title, 'post_name' => $class , 'post_content'=> $event->thank_you, 'post_excerpt'=>$event_time, 'post_type' => 'alex_timeline', 'post_parent'=> $member_id, 'menu_order'=>$alex_tl_grp_id,'guid'=>$event->id),
						array( '%s','%d','%s','%s','%s','%s','%s','%d','%d','%d' )
					);
					*/
					$wpdb->insert(
						$wpdb->posts,
						array( 'post_date'=>$event->event_time ,'ID' => $last_post_id+1, 'post_type' => 'alex_timeline', 'post_parent'=> $user_id_isnotlogin,'guid'=>$event->id),
						array( '%s','%d','%s','%d','%d')
					);
					// deb_last_query();
				}
			}
			// echo "<br>";
			$i++;
		}
		// exit;
	}

}
/* **** as21 NEED FUTURE **** */

/* **** as21 **** */

function as21_get_user_link_and_avatar($user_id, $iter = false){

	// $debug .= "<br>function as21_get_user_link_and_avatar<br>";
  	if( ($iter && $iter == 1) or !$iter) $debug .= "<p class='attendees'>Attendees: ";
  	// $member_name .= "<p class='a21-system-message'>id ".$user_id."-".bp_core_get_username($user_id)."</p>";
	$member_link = bp_core_get_userlink($user_id,false,true);	
	$member_avatar = bp_core_fetch_avatar( array('item_id'=>$user_id, 'width'=>"30",'html'=>true));
	$debug .= "<a class='link-user-id-".$user_id."' href='".$member_link."' >".$member_avatar."</a>";
	// $debug .= "</p>";
	return $debug;
}

add_action('wp_ajax_a21_cancel_my_attandance', 'a21_cancel_my_attandance');
function a21_cancel_my_attandance(){

	// alex_debug(0,1,"",$_POST);
	if( !empty($_POST['user_id']) ) $user_id = (int)$_POST['user_id'];
	if( !empty($_POST['task_id']) ) $task_id = (int)$_POST['task_id'];
	$i = (int)$_POST['i'];

	// if( !empty($_POST['i']) ) $i = (int)$_POST['i'];
	// var_dump($i);
	// var_dump($user_id);
	// var_dump(empty($i));
	// var_dump( is_int($i) );
	// var_dump(empty($task_id));

	// if in vols not exist (:) but have (,) then delete one vol
	function as21_del_vol_from_arr( $user_id, $ids_vols, $i = false ){

		// echo "===start not (:) ids===";	echo "\r\n";
		// echo " i=".$i;	echo "\r\n";
		// echo "ids_vols="; var_dump($ids_vols);

		if( is_array($ids_vols) ) {
			$ids_vols = $ids_vols[$i];
		}

		// var_dump($ids_vols);

		if( strpos($ids_vols, ",") !== false ) {
			$ids_vols = explode(",", $ids_vols);
			foreach ($ids_vols as $k => $v) {
				if($v==$user_id) { unset($ids_vols[$k]); }
			}
			$str_ids_vol_one_del = implode(",", $ids_vols);

			// $ids_vols = array();
			// echo "\r\n ids_vols "; var_dump($ids_vols);   echo "\r\n";
			// echo "user_id=".$user_id; 	echo "\r\n";
			// echo "str ".$str_ids_vol_one_del;echo "\r\n";
			// echo "==== end pard=====\r\n";

			return $str_ids_vol_one_del;
		}
		// if in vols not exist (:) but have (,) then delete one vol
	}


	// 0 is empty value,so use min 1
	if( !empty($user_id) && !empty($task_id) && is_int($i)){
		global $wpdb;
		// $ids_vols = $wpdb->get_var( $wpdb->prepare( "SELECT ids_vols FROM {$wpdb->prefix}bp_groups_bgc_tasks WHERE id = %d", $task_id ) );
		$event_task = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bp_groups_bgc_tasks WHERE id = %d", $task_id ) );
		$ids_vols = $event_task->ids_vols;
    	$str_del_vol = array();
		
		// alex_debug(0,1,"event_task",$event_task);

		if( strpos($ids_vols, ":") !== false ){

			$cnt_vols = explode(",",$event_task->cnt_vols);
			$cnt_primary_vols = $cnt_vols[$i];
			$ids_vols = explode(":",$ids_vols);
			$str_del_vol[$i] = as21_del_vol_from_arr($user_id, $ids_vols, $i);
			$ids_vols[$i] = $str_del_vol[$i];
			$str_ids_vols = implode(":", $ids_vols);
			// echo "str_ids_vols ";var_dump($str_ids_vols);

			// $ids_vols[$i] = str_replace($user_id, "", $ids_vols[$i]);
			// $ids_vols[$i] = preg_replace("/^,/i", "", $ids_vols[$i]); // delete first ,
			// $ids_vols[$i] = preg_replace("/,$/i", "", $ids_vols[$i]); // delete last ,

		}else{
	    	$cnt_primary_vols = $event_task->cnt_vols;
	    	
			$str_del_vol[$i] = as21_del_vol_from_arr($user_id, $ids_vols);
	    	// $str_del_vol[$i] = as21_del_vol_from_arr($user_id, $ids_vols);
	  //   	echo $str_del_vol[$i]; echo "\r\n";
			// echo "ids_vols[i]="; var_dump($str_del_vol[$i]); 	echo "\r\n";
	    }

	    /* important for debug
		$debug = "<p class='a21-system-message'>cnt_primary_vols=".$cnt_primary_vols."</p>";
		*/

		// var_dump($ids_vols);
		// echo "arr vols cur task & cur time column =";
		// echo "ids_vols[i]="; $ids_vols[$i]; 	echo "\r\n";
		// exit;

		// if many vols then it array, if one vol then it string
		// if( preg_match("#,#i", $ids_vols[$i]) ) {
		if( preg_match("#,#i", $str_del_vol[$i]) ) {
			$cnt_vols_signup_now = explode(",", $str_del_vol[$i] );
			$cnt_vols_signup_now = count($cnt_vols_signup_now);
		}else { 
			if( !empty($str_del_vol[$i]) ) $cnt_vols_signup_now = 1;
			else $cnt_vols_signup_now = 0;
		}
		// $js['cnt_vols_signup_now'] = $cnt_vols_signup_now;
		
		/* important for debug
		$debug .= "<p class='a21-system-message'>cnt_vols_signup_now =".$cnt_vols_signup_now."</p>";
		*/

		$still_need_count = $cnt_primary_vols - $cnt_vols_signup_now;
		$js['cnt_vols_signup_now'] = $cnt_vols_signup_now;
		$need = "Needed";
		$debug .= '<p class="still_need"><span class="vol_cnt">'.$still_need_count.'</span> '.$need.'</p><button class="a21_add_new_volunteer" data-s-need-cnt="'.$still_need_count.'" data-i="'.$i.'" data-id="'.$user_id.'" data-nick="test">i want to attend</button>';
		// echo $debug;
		// exit;
		// echo "ids_vols[i]= "; var_dump($ids_vols[$i]);
	  	// $member_name = '';
		 // echo "preg_match , " ; var_dump( preg_match("#,#i", $ids_vols[$i]) );
		 // var_dump($ids_vols[$i]);
  		// if many vols then it array, if one vol then it string
		// if( preg_match("#,#i", $ids_vols[$i]) ) {
		if( strripos($str_del_vol[$i],",") !== false ) {
			 $ii = 1;
			  $arr_ids_vols = explode(",", $str_del_vol[$i] );
			  foreach ($arr_ids_vols as $member_id) {
		        if(!empty($member_id) ) {
					$debug .= as21_get_user_link_and_avatar($member_id, $ii);
					$ii++;  	        		     
				 }
			  }
		}else{
			// if only one vol
			if( !empty($str_del_vol[$i]) ) {
			    $debug .= as21_get_user_link_and_avatar($str_del_vol[$i], false);
			}
		}  

		// echo " after delete user_id from ids_vols= ";
		if( strpos($ids_vols, ":") !== false ) $ids_vols = $str_ids_vols;
		else $ids_vols = $str_del_vol[$i];
		// exit;

		$query = $wpdb->update( $wpdb->prefix."bp_groups_bgc_tasks",
			array( 'ids_vols' => $ids_vols ),
			array( 'id' => $task_id),
			array( '%s' ),
			array( '%d' )
		);
		// deb_last_query();
		// exit;
		// if($query) return true;

		 // $debug .= "<p class='a21-system-message'>new markup from ajax</p>";
		 $js['html'] = $debug;
		 echo json_encode($js);
	}

	exit;
}

add_action('wp_ajax_a21_bgc_add_new_volunteer', 'a21_bgc_add_new_volunteer');
function a21_bgc_add_new_volunteer(){

	if( !empty($_POST['user_id']) ) $user_id = (int)$_POST['user_id'];
	if( !empty($_POST['task_id']) ) $task_id = (int)$_POST['task_id'];
	if( !empty($_POST['event_id']) ) $event_id = (int)$_POST['event_id'];
	$i = (int)$_POST['i'];
	$num_cur_column = $i;

	// echo "\r\n ===wp ajax success! get POST === \r\n";
	// echo "<br>user_id ".$_POST['user_id'];
	// echo "<br>task_id ".$_POST['task_id'];
	// $debug .= "<br>event_id ".$_POST['event_id'];
	// $debug .= "<br>i ".$num_cur_column;
	// $debug .= "<br>\r\n"; var_dump($i);
	// exit;

	if( empty($user_id) or empty($task_id) or empty($event_id)) return false;

	global $wpdb;
	$event_task = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bp_groups_bgc_tasks WHERE id = %d", $task_id ) );
	// deb_last_query();
    $times = $wpdb->get_col( "SELECT `time` FROM {$wpdb->prefix}bp_groups_bgc_time WHERE event_id='".$event_id."'");
    $signup_time = $times[$i];
    $column_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}bp_groups_bgc_time WHERE event_id='".$event_id."'");
	// print_r($times);
	// exit;
	// print_r($event_task);
	// var_dump($event_task->ids_vols);

	if(empty($event_task->ids_vols)) {
		// if ids_vols is empty,then insert current memeber_id 
		// $debug .= "\r\n ids_vols-empty";
	    // $debug .= "\r\n column_count=".$column_count;

	    if( $column_count > 1 ){
		    for($qq=0; $qq<$column_count; $qq++){
		    	// format record ids_vols 2,3:3,4,2:2,1,2 	// ::1 or 1:: or :1:
		    	if($qq==$num_cur_column) { $val .= $user_id;}
		    	else { $val .= ""; if($qq < $column_count) $val .= ":";}
		    	// if($i==$num_cur_column) { $val .= $_POST['user_id']; if($i == 0) $val .= ":";}
		    	// else { $val .= ""; if($i < $column_count) $val .= "0:";}
		    }
		}
		if($column_count == 1) $val = $user_id;

	    // echo $debug .= "\r\n if empty val ids_vols=".$val;
	    // exit;

		$wpdb->update( $wpdb->prefix."bp_groups_bgc_tasks",
			array( 'ids_vols' => $val ),
			array( 'id' => $task_id ),
			array( '%s' ),
			array( '%d' )
		);
		// echo "deb last query = "; deb_last_query();

	}
	else{
	    // echo $debug .= "\r\n if !empty val ids_vols=".$val;
	    if( strpos($event_task->ids_vols, ":") !== false ){
	    	// $debug .= "\r\n search char (:) ";
			$ids_vols = explode(":",$event_task->ids_vols);
			// $debug .= "ids_vols is not empty!";
			// $debug .= "arr vols cur task=".$ids_vols[$_POST['i']];
			if( !empty($ids_vols[$i]) ) $ids_vols[$i] .= ",".$user_id;
			else $ids_vols[$i] = $user_id;
			// print_r($ids_vols);
			 $ids_vols = implode(":", $ids_vols);
			// exit;
		}else{
	    	// $debug .= "\r\n if search char (:) not found ";
		    // if( strpos($event_task->ids_vols, ",") === false )  $ids_vols = $_POST['user_id'];
		    // else $ids_vols = $event_task->ids_vols.",".$_POST['user_id'];
		    $ids_vols = $event_task->ids_vols.",".$user_id;
		    // echo "ids vosl "; var_dump($ids_vols); echo "\r\n";
		}

		$wpdb->update( $wpdb->prefix."bp_groups_bgc_tasks",
			array( 'ids_vols' => $ids_vols ),
			array( 'id' => $task_id ),
			array( '%s' ),
			array( '%d' )
		);
		// на этом шаге обновление происходит правильно!!!

		// echo $debug;
		// echo "\r\n =====deb_last_query==== \r\n";
		// echo "deb last query = "; deb_last_query();
		// exit;


	}

		// exit;


		////////////////// upgrade

		// join current user to cur group
		as21_groups_action_join_group();

		// counter current actyally count voluteers
		$event_task = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bp_groups_bgc_tasks WHERE id = %d", $task_id ) );
		// echo $debug .= "\r\n repeat query to bd tasks get actyally task \r\n";
		// alex_debug(0,1,"",$event_task);

		$ids_vols = $event_task->ids_vols;
		// print_r($ids_vols);
		// exit;
	    if( strpos($event_task->ids_vols, ":") !== false ){
			$cnt_vols = explode(",",$event_task->cnt_vols);
			$cnt_primary_vols = $cnt_vols[$i];
			$ids_vols = explode(":",$ids_vols);
	    }
	    // $debug .= "\r\n<br> qq cnt_primary_vols".$cnt_primary_vols. "\r\n";
	    // $debug .= "\r\n<br> cnt vols"; var_dump($cnt_vols);echo "\r\n";
	    // $debug .= "\r\n<br> i=".$i. "\r\n";

	    if( strpos($event_task->ids_vols, ":") === false && !empty($event_task->ids_vols)){
	    	$cnt_primary_vols = $event_task->cnt_vols;
	    	$str_ids_vols = $event_task->ids_vols;
	    	// echo "=== str_ids_vols=".$str_ids_vols; echo "\r\n";
	    }else $str_ids_vols = $ids_vols[$i];

		/* important for debug
		$debug .= "<p class='a21-system-message'>cnt_primary_vols=".$cnt_primary_vols."</p>";
		*/

		// if many vols then it array, if one vol then it string
		if( preg_match("#,#i", $str_ids_vols) ) {
			$cnt_vols_signup_now = explode(",", $str_ids_vols );
			$cnt_vols_signup_now = count($cnt_vols_signup_now);
		}else { 
			if( !empty($str_ids_vols) ) $cnt_vols_signup_now = 1;
			else $cnt_vols_signup_now = 0;
		}

		// echo $ids_vols;

		// print_r($ids_vols);
		// echo "arr vols cur task & cur time column =";
		// echo $ids_vols[$i]; 	echo "\r\n";
		// $ids_vols[$i] = str_replace($user_id, "", $ids_vols[$i]);
		// $ids_vols[$i] = preg_replace("/^,/i", "", $ids_vols[$i]); // delete first ,
		// $ids_vols[$i] = preg_replace("/,$/i", "", $ids_vols[$i]); // delete last ,
		// var_dump($ids_vols[$i]);
		// exit;
		// $js['cnt_vols_signup_now'] = $cnt_vols_signup_now;

		/* important for debug
		$debug .= "<p class='a21-system-message'>cnt_vols_signup_now =".$cnt_vols_signup_now."</p>";
		*/

		$still_need_count = $cnt_primary_vols - $cnt_vols_signup_now;
		// $debug .= '<button class="a21_add_new_volunteer" data-s-need-cnt="'.$still_need_count.'" data-i="'.$i.'" data-id="'.$user_id.'" data-nick="test">signup</button><p><span class="vol_cnt">'.$still_need_count.'</span> Needed</p>';

		if($cnt_primary_vols == $cnt_vols_signup_now ) { $js['full'] = true;  $debug .= "<p>FULL</p>"; }
		else $debug .= "<p class='still_need'><span class='vol_cnt'>".$still_need_count."</span> Needed</p>";

		$debug .= "<button class='a21_cancel_my_attandance' data-s-need-cnt='".$still_need_count."'' data-i='".$i."' data-task-id='".$task_id."' data-user-id='".$user_id."'>cancel my attandance</button>";


		// echo "ids_vols[i]= "; var_dump($ids_vols[$i]);
	  	// $member_name = '';
		 // echo "preg_match , " ; var_dump( preg_match("#,#i", $ids_vols[$i]) );
		// var_dump($str_ids_vols);
		//  var_dump($ids_vols[$i]);
		//  var_dump($ids_vols);
		//  exit;

  		// if many vols then it array, if one vol then it string
		// if( preg_match("#,#i", $ids_vols[$i]) ) {
		if( strpos($str_ids_vols,",") !== false ) {
			 $ii = 1;
			  $arr_ids_vols = explode(",", $str_ids_vols );
			  foreach ($arr_ids_vols as $member_id) {
		        if(!empty($member_id) ) {
					$debug .= as21_get_user_link_and_avatar($member_id, $ii);
					$ii++;  	        		     
				 }
			  }
		}else{
			// if only one vol
			if( !empty($str_ids_vols) ) {
			    $debug .= as21_get_user_link_and_avatar($str_ids_vols, false);
			}
		}

		as21_bp_group_calendar_event_add_action_message( $user_id, $event_id, $event_task->task_title, $signup_time );
		// $debug .= as21_get_user_link_and_avatar($user_id, false);

		 // $debug .= "<p class='a21-system-message'>new markup from ajax after click signup</p>";

		 // echo $debug;
		 $js['html'] = $debug;
		 echo json_encode($js);
	///////////////////

	// $ids_ = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bp_groups_bgc_tasks WHERE event_id='11'");
	// $wpdb->update( $wpdb->posts,
	// 	array( 'post_title' => $title, 'post_name' => $class , 'post_content'=> $content, 'post_excerpt'=>$date,'menu_order'=>$alex_tl_grp_id ),
	// 	array( 'ID' => $id ),
	// 	array( '%s', '%s', '%s', '%s','%d' ),
	// 	array( '%d' )
	// );


	exit;
}




/* **** as21 **** */


function bp_group_calendar_make_current() {

	global $wpdb, $bp_group_calendar_current_version;

	if ( get_site_option( "bp_group_calendar_version" ) == '' ) {
		add_site_option( 'bp_group_calendar_version', '0.0.0' );
	}

	if ( get_site_option( "bp_group_calendar_version" ) == $bp_group_calendar_current_version ) {
		// do nothing
	} else {
		//update to current version
		update_site_option( "bp_group_calendar_installed", "no" );
		update_site_option( "bp_group_calendar_version", $bp_group_calendar_current_version );
	}
	bp_group_calendar_global_install();
}


function bp_group_calendar_global_install() {

	global $wpdb, $bp_group_calendar_current_version;

	if ( get_site_option( "bp_group_calendar_installed" ) == '' ) {
		add_site_option( 'bp_group_calendar_installed', 'no' );
	}

	if ( get_site_option( "bp_group_calendar_installed" ) == "yes" ) {
		// do nothing
	} else {
		// ALTER TABLE `wp8k_bp_groups_calendars` ADD `thank_you` TEXT NOT NULL AFTER `last_edited_stamp`, ADD `total_vols` SMALLINT UNSIGNED NOT NULL AFTER `thank_you`;

		$bp_group_calendar_table1 = "CREATE TABLE IF NOT EXISTS `" . $wpdb->base_prefix . "bp_groups_calendars` (
                                  `id` bigint(20) unsigned NOT NULL auto_increment,
                                  `group_id` bigint(20) NOT NULL default '0',
                                  `user_id` bigint(20) NOT NULL default '0',
                                  `event_time` DATETIME NOT NULL,
                                  `event_slug` VARCHAR(200) NOT NULL,
                                  `event_title` TEXT NOT NULL,
                                  `event_description` TEXT,
                                  `event_location` TEXT,
                                  `event_map` BOOL NOT NULL,
                                  `created_stamp` bigint(30) NOT NULL,
                                  `last_edited_id` bigint(20) NOT NULL default '0',
                                  `last_edited_stamp` bigint(30) NOT NULL,
                                  `thank_you` TEXT NOT NULL,
                                  `total_vols` SMALLINT UNSIGNED NOT NULL,
                                  PRIMARY KEY  (`id`)
                                ) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;";

		$wpdb->query( $bp_group_calendar_table1 );
/*
        $a21_bgc_table2 = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "bp_groups_bgc_tasks` (
                          `id` bigint(20) unsigned NOT NULL auto_increment,
                          `event_id` bigint(20) UNSIGNED NOT NULL,
                          `task_title` text NOT NULL,
                          `task_time` varchar(15) NOT NULL,
                          `count_vol` smallint(5) UNSIGNED NOT NULL,
                          `ids_vol` varchar(255) NOT NULL,
                          PRIMARY KEY  (`id`)
                        ) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;";
		$wpdb->query( $a21_bgc_table2 );
*/
		$a21_bgc_table3 = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "bp_groups_bgc_tasks` (
						  `id` bigint(20) UNSIGNED NOT NULL auto_increment,
						  `event_id` bigint(20) UNSIGNED NOT NULL,
						  `task_title` varchar(255) NOT NULL,
						  `cnt_vols` varchar(255) NOT NULL,
						  `ids_vols` varchar(255) NOT NULL,
						  PRIMARY KEY  (`id`)
						) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;";

		$wpdb->query( $a21_bgc_table3 );

		$a21_bgc_table4 = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "bp_groups_bgc_time` (
						  `id` int(20) UNSIGNED NOT NULL auto_increment,
						  `event_id` bigint(20) NOT NULL,
						  `time` varchar(20) NOT NULL,
						  PRIMARY KEY  (`id`)
						) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;";
		$wpdb->query( $a21_bgc_table4 );

		update_site_option( "bp_group_calendar_installed", "yes" );
	}
}

//extend the group
if ( class_exists( 'BP_Group_Extension' ) ) {
	class BP_Group_Calendar_Extension extends BP_Group_Extension {

		var $visibility = 'private'; // 'public' will show your extension to non-group members, 'private' means you have to be a member of the group to view your extension.
		var $enable_create_step = false; // If your extension does not need a creation step, set this to false
		var $enable_nav_item = false; // If your extension does not need a navigation item, set this to false
		var $enable_edit_item = false; // If your extension does not need an edit screen, set this to false

		function bp_group_calendar_extension() {
			global $bp;

			$this->name            = __( 'Callout', 'groupcalendar' );
			$this->slug            = 'callout';
			$this->enable_nav_item = isset( $bp->groups->current_group->user_has_access ) ? $bp->groups->current_group->user_has_access : false;

			//$this->create_step_position = 21;
			$this->nav_item_position = 36;

		}

		function display( $group_id = NULL ) {
			global $bp;
			$event_id = bp_group_calendar_event_url_parse();

			$edit_event = bp_group_calendar_event_is_edit();

			bp_group_calendar_event_is_delete();

			if ( bp_group_calendar_event_save() === false ) {
				$edit_event = true;
			}

			$date = bp_group_calendar_url_parse();

			do_action( 'template_notices' );

			/**** a21 display()******/
			// echo '===MAIN======a21 display()';
			// echo "<br>";
			// var_dump($event_id);
			// exit;
			// var_dump($edit_event);
			/**** a21 display()******/

			if ( $edit_event ) {

				//show edit event form
				if ( ! bp_group_calendar_widget_edit_event( $event_id ) ) {

					//default to current month view
					bp_group_calendar_widget_month( $date );
					bp_group_calendar_widget_upcoming_events();
					bp_group_calendar_widget_my_events();
					bp_group_calendar_widget_create_event( $date );

				}

			} else if ( $event_id ) {

				//display_event
				bp_group_calendar_widget_event_display( $event_id );

				//current month view
				bp_group_calendar_widget_month( $date );

			} else if ( isset( $date['year'] ) && ! isset( $date['month'] ) && ! isset( $date['day'] ) ) {

				//year view
				bp_group_calendar_widget_year( $date );

				bp_group_calendar_widget_create_event( $date );

			} else if ( isset( $date['year'] ) && isset( $date['month'] ) && ! isset( $date['day'] ) ) {

				//month view
				bp_group_calendar_widget_month( $date );

				bp_group_calendar_widget_create_event( $date );

			} else if ( isset( $date['year'] ) && isset( $date['month'] ) && isset( $date['day'] ) ) {

				//day view
				bp_group_calendar_widget_day( $date );

				bp_group_calendar_widget_create_event( $date );

			} else {

				//default to current month view
				bp_group_calendar_widget_month( $date );

				bp_group_calendar_widget_upcoming_events();

				bp_group_calendar_widget_my_events();

				bp_group_calendar_widget_create_event( $date );

			}
		}

		function widget_display() {
			bp_group_calendar_widget_upcoming_events();
		}
	}

	bp_register_group_extension( 'BP_Group_Calendar_Extension' );
}

/**
 * Takes a GMT time-date string or timestamp and converts for localized display output in local WP timezone
 *
 * @param int|string $time Timestamp
 * @param string $format PHP datetiem format string. Optional, defaults to WP preference
 *
 * @return string Localized time
 */
function bgc_date_display( $time, $format = false ) {
	if ( ! $format ) {
		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	}

	$timestamp = is_numeric( $time ) ? (int) $time : strtotime( $time );

	//convert GMT time stored in db to local timezone setting
	$localtime = $timestamp + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

	return date_i18n( $format, $localtime );
}


function bp_group_calendar_settings_save( $group ) {
	global $wpdb;

	if ( isset( $_POST['group-calendar-moderator-capabilities'] ) ) {
		groups_update_groupmeta( $group->id, 'group_calendar_moderator_capabilities', $_POST['group-calendar-moderator-capabilities'] );
	}

	if ( isset( $_POST['group-calendar-member-capabilities'] ) ) {
		groups_update_groupmeta( $group->id, 'group_calendar_member_capabilities', $_POST['group-calendar-member-capabilities'] );
	}

	if ( isset( $_POST['group-calendar-send-email'] ) ) {
		groups_update_groupmeta( $group->id, 'group_calendar_send_email', $_POST['group-calendar-send-email'] );
	}
}


function bp_group_calendar_event_save() {
	global $wpdb, $current_user;

	$calendar_capabilities = bp_group_calendar_get_capabilities();

	if ( isset( $_POST['create-event'] ) ) {

		/**** a21 create event ******/
		
		// alex_debug(1,1,"",$_REQUEST);
		// alex_debug(1,1,"",$_FILES);
		// alex_debug(1,1,"",$_POST);
		// echo "===as21_save===";
		// exit;


		// exit("=====bp_group_calendar_event_save()====");

		/**** a21 ******/
	// echo "ev-tasks ".count($_POST['new_event_tasks']);



		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bp_group_calendar' ) ) {
			bp_core_add_message( __( 'There was a security problem', 'groupcalendar' ), 'error' );

			return false;
		}

		//reject unqualified users
		if ( $calendar_capabilities == 'none' ) {
			bp_core_add_message( __( "You don't have permission to edit events", 'groupcalendar' ), 'error' );

			return false;
		}

		//prepare fields
		$group_id    = (int) $_POST['group-id'];
		$event_title = esc_attr( strip_tags( trim( $_POST['event-title'] ) ) );

		//check that required title isset after filtering
		if ( empty( $event_title ) ) {
			bp_core_add_message( __( "An event title is required", 'groupcalendar' ), 'error' );

			return false;
		}

		$tmp_date = $_POST['event-date'] . ' ' . $_POST['event-hour'] . ':' . $_POST['event-minute'] . $_POST['event-ampm'];

		$tmp_date = strtotime( $tmp_date );



		//check for valid date/time
		if ( $tmp_date && strtotime( $_POST['event-date'] ) && strtotime( $_POST['event-date'] ) != - 1 ) {
			$event_date = gmdate( 'Y-m-d H:i:s', ( $tmp_date - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ); //assumed date entered in timezone offset. Subtract the offset to get GMT for storage.
		} else {
			bp_core_add_message( __( "Please enter a valid event date.", 'groupcalendar' ), 'error' );

			return false;
		}
		
		/* **** as21 **** */

		// event now not double
		// $check_dbl_event = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}bp_groups_calendars WHERE event_time=%s AND event_title=%s",$event_date,$event_title));

		// var_dump($check_event);

		// when event exist
		// if(!is_null($check_dbl_event)) return false;


		/* **** as21 **** */

		$event_description = wp_filter_post_kses( wpautop( $_POST['event-desc'] ) );
		$event_location    = esc_attr( strip_tags( trim( $_POST['event-loc'] ) ) );
		$event_map         = ( isset( $_POST['event-map'] ) && $_POST['event-map'] == 1 ) ? 1 : 0;
		/* **** as21 **** */
		$thank_you = sanitize_text_field($_POST['thank_you']);
		$total_volunteers =(int)$_POST['total-volunteers'];


		//editing previous event
		if ( isset( $_POST['event-id'] ) ) {

			if( !empty( $_POST['new_event_tasks']) && $_POST['new_event_tasks'] > 1){

				// sanitizing input data
				foreach ($_POST['new_event_tasks']['time'] as $k => $time) {
					$esc_post['new_event_tasks']['time'][$k] = sanitize_text_field($time);
				}

				foreach ($_POST['new_event_tasks'] as $k => $item) {
					// echo "k-".$k."<br>";
					// print_r($item);
					if($k !== "time") {
						// echo "<br>item task= ".$item['task']."<br>";
						$esc_post['new_event_tasks'][$k]['task'] = sanitize_text_field($item['task']);
						foreach ($item as $k2 => $v) {
							if($k2 !== "task") { $esc_post['new_event_tasks'][$k][$k2] = (int)$v;}
						}
					}
				}
			}

			$times = $esc_post['new_event_tasks']['time'];
			unset($esc_post['new_event_tasks']['time']);

			foreach ($times as $k => $v) {
				$wpdb->update( $wpdb->prefix."bp_groups_bgc_time",
					array( 'time' => $v),
					array( 'id' => $k ),
					array( '%s'),
					array( '%d' )
				);
			}

			// echo "after parsing";
			// alex_debug(0,1,"times",$times);
			// alex_debug(0,1,"new_event_tasks",$esc_post['new_event_tasks']);
			foreach ($esc_post['new_event_tasks'] as $k => $item) {
				// var_dump($item);
				$str_cnt_vols = "";
				foreach ($item as $k2 => $v) {
					// echo $v. " ";
					// echo $k2. " ";
					if($k2 === "task") $title_task = $v;
					else $str_cnt_vols .= $v.",";
					// echo $k2." ";
				}
				$str_cnt_vols = substr($str_cnt_vols, 0,-1);
				// echo $title_task." id-".$k.": ".$str_cnt_vols."<br>";

				$wpdb->update( $wpdb->prefix."bp_groups_bgc_tasks",
					array( 'task_title' => $title_task, "cnt_vols"=> $str_cnt_vols),
					array( 'id' => $k ),
					array( '%s','%s' ),
					array( '%d' )
				);

			}
			// end edit custom event fields


			//can user modify this event?
			if ( $calendar_capabilities == 'limited' ) {
				$creator_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM " . $wpdb->base_prefix . "bp_groups_calendars WHERE id = %d AND group_id = %d", (int) $_POST['event-id'], $group_id ) );

				if ( $creator_id != $current_user->ID ) {
					bp_core_add_message( __( "You don't have permission to edit that event", 'groupcalendar' ), 'error' );

					return false;
				}
			}
			$event_slug = strtolower($event_title);
			// $event_slug = str_replace("-", "", $event_slug);
			// $event_slug = str_replace("- ", "", $event_slug);
			$event_slug = str_replace(" ", "-", $event_slug);

			$query = $wpdb->prepare( "UPDATE " . $wpdb->base_prefix . "bp_groups_calendars
                            	SET event_time = %s,event_slug = %s, event_title = %s, event_description = %s, event_location = %s, event_map = %d, last_edited_id = %d, last_edited_stamp = %d,thank_you = %s, total_vols = %d
                            	WHERE id = %d AND group_id = %d LIMIT 1",
				$event_date, $event_slug, $event_title, $event_description, $event_location, $event_map, $current_user->ID, current_time( 'timestamp', true ), $thank_you, $total_volunteers, (int) $_POST['event-id'], $group_id );

			if ( $wpdb->query( $query ) ) {
				bp_core_add_message( __( "Event saved", 'groupcalendar' ) );

				//record activity
				bp_group_calendar_event_add_action_message( false, (int) $_POST['event-id'], $event_date, $event_title );

				/**** a21 ******/
				if ( ! function_exists( 'wp_handle_upload' ) ) {
				    require_once( ABSPATH . 'wp-admin/includes/file.php' );
				}

				$uploadedfile = $_FILES['a21_image_upload'];
				$mimes = array('png' => 'image/png','gif'=> 'image/gif','jpg'=> 'image/jpeg');
				$movefile = wp_handle_upload($uploadedfile, array('test_form' => false, 'mimes' => $mimes));

				if ( $movefile && ! isset( $movefile['error'] ) ) {
				    // echo "File is valid, and was successfully uploaded.\n";
				    // echo "<hr>".$movefile['url']."<hr>";
				    // var_dump( $movefile );
				    // $group_id    = (int) $_POST['group-id'];

				    // group_id will be insert new_event_id
					$query = $wpdb->prepare( "UPDATE " . $wpdb->base_prefix . "bp_groups_groupmeta
				                	SET meta_value=%s WHERE group_id=%d AND meta_key=%s LIMIT 1
				                	",$movefile['url'],(int) $_POST['event-id'], 'a21_bgc_event_image' );
					$wpdb->query( $query );
					// deb_last_query();
					// exit;

				} else {
				    // echo $movefile['error'];
				}

				/**** a21 ******/


				return true;
			} else {
				bp_core_add_message( __( "There was a problem saving to the DB", 'groupcalendar' ), 'error' );

				return false;
			}

		} else { //new event

			$event_slug = strtolower($event_title);
			$event_slug = str_replace(" ", "-", $event_slug);


			$query = $wpdb->prepare( "INSERT INTO " . $wpdb->base_prefix . "bp_groups_calendars
                            	( group_id, user_id, event_time, event_slug, event_title, event_description, event_location, event_map, created_stamp, last_edited_id, last_edited_stamp,thank_you,total_vols )
                            	VALUES ( %d, %d, %s, %s, %s, %s, %s, %d, %d, %d, %d, %s, %d)",
				$group_id, $current_user->ID, $event_date, $event_slug, $event_title, $event_description, $event_location, $event_map, current_time( 'timestamp', true ), $current_user->ID, time(),$thank_you,$total_volunteers );

			if ( $wpdb->query( $query ) ) {

				$new_id = $wpdb->insert_id;
				bp_core_add_message( __( "Event saved", 'groupcalendar' ) );
				bp_group_calendar_event_add_action_message( true, $new_id, $event_date, $event_title );

				//email group members
				bp_group_calendar_event_email( true, $new_id, $event_date, $event_title );


				/**** a21 image post ******/

				if ( ! function_exists( 'wp_handle_upload' ) ) {
				    require_once( ABSPATH . 'wp-admin/includes/file.php' );
				}

				$uploadedfile = $_FILES['a21_image_upload'];
				$mimes = array('png' => 'image/png','gif'=> 'image/gif','jpg'=> 'image/jpeg');
				$movefile = wp_handle_upload($uploadedfile, array('test_form' => false, 'mimes' => $mimes));

				if ( $movefile && ! isset( $movefile['error'] ) ) {
				    // echo "File is valid, and was successfully uploaded.\n";
				    // echo "<hr>".$movefile['url']."<hr>";
				    // var_dump( $movefile );
				    // $group_id    = (int) $_POST['group-id'];

				    // group_id will be insert new_event_id
					$query = $wpdb->prepare( "INSERT INTO " . $wpdb->base_prefix . "bp_groups_groupmeta
				                	( group_id,meta_key,meta_value)
				                	VALUES ( %d,%s, %s )", $new_id, 'a21_bgc_event_image',$movefile['url'] );
					$wpdb->query( $query );

				} else {
				    // echo $movefile['error'];
				}

				/**** a21 image post ******/

				/**** a21 parse, array new_event_tasks to string for insert/update in db ******/

				if( !empty( $_POST['new_event_tasks']) && $_POST['new_event_tasks'] > 1){

					// sanitizing input data
					foreach ($_POST['new_event_tasks']['time'] as $k => $time) {
						$esc_post['new_event_tasks']['time'][$k] = sanitize_text_field($time);
					}

					foreach ($_POST['new_event_tasks'] as $k => $item) {
						// echo "k-".$k."<br>";
						// print_r($item);
						if($k !== "time") {
							// echo "<br>item task= ".$item['task']."<br>";
							$esc_post['new_event_tasks'][$k]['task'] = sanitize_text_field($item['task']);
							foreach ($item as $k2 => $v) {
								if($k2 !== "task") { $esc_post['new_event_tasks'][$k][$k2] = (int)$v;}
							}
						}
					}

					/* **** option 3 **** */
					$times = $esc_post['new_event_tasks']['time'];
					unset($esc_post['new_event_tasks']['time']);

				    // alex_debug(0,1,"",$times); 
				    // alex_debug(0,1,"",$esc_post); 
				    // $new_id = 11;

				    foreach ($esc_post['new_event_tasks'] as $task):
				    	$i = 0;  // counter time
			            $cnts = '';
				         foreach ($task as $k => $title_and_cnt):
				         	  if($k === 'task') $title_task = $title_and_cnt;
				         	  else { $cnts .= $title_and_cnt.",";  $i++;}
				         	  // $new_id - id last add event
				         	  //  (task_title,task_time,count_vol) VALUES ($title_task, $times[$i], $title_and_cnt);
				          endforeach;
				          // $sql .= $title_task.$cnts;
      				     $cnts = substr($cnts,0, -1);
				          $sql .= $wpdb->prepare("(%d,%s,%s),", $new_id, $title_task, $cnts); 
				     endforeach;

				     $sql = substr($sql,0, -1);
				     $tasks_sql = "INSERT INTO {$wpdb->prefix}bp_groups_bgc_tasks (event_id,task_title,cnt_vols) VALUES ".$sql;
				     // echo $tasks_sql;
				     $wpdb->query($tasks_sql);

				     foreach ($times as $v) {
				     	// $times_str .= $new_id.",".$v.",";
 			            $values .= $wpdb->prepare("(%d,%s),", $new_id, $v);
				     }
				    $values = substr($values,0, -1);
		            //$sql = "INSERT INTO {$wpdb->prefix}bp_groups_bgc_tasks (event_id,time) VALUES ".$values;
		            $times_sql = "INSERT INTO {$wpdb->prefix}bp_groups_bgc_time (`event_id`,`time`) VALUES ".$values;
				     $wpdb->query($times_sql);
		            // echo "<br>".$sql;

					/**** a21 parse, array new_event_tasks to string for insert/update in db ******/

				     /*
					if(!empty($_POST['thank_you'])) {

						$thank_you = sanitize_text_field($_POST['thank_you']);
						$query = $wpdb->prepare( "INSERT INTO " . $wpdb->prefix . "bp_groups_groupmeta
							( group_id,meta_key,meta_value)
							VALUES ( %d,%s, %s )", $new_id, 'a21_bgc_event_thank_you',$thank_you );
						$wpdb->query( $query );
					}

					if(!empty($_POST['total-volunteers'])) {

						$total_volunteers =(int)$_POST['total-volunteers'];
						$query = $wpdb->prepare( "INSERT INTO " . $wpdb->prefix . "bp_groups_groupmeta
							( group_id,meta_key,meta_value)
							VALUES ( %d,%s, %d )", $new_id, 'a21_bgc_event_total_volunteers',$total_volunteers );
						$wpdb->query( $query );
					}
					*/

					/* **** option 3 **** */

					/* **** as21 option 1**** *
					if(!empty($_POST['total-volunteers'])) $esc_post['new_event_tasks']['total-volunteers'] = (int)$_POST['total-volunteers'];


					$ser_str = serialize($esc_post['new_event_tasks']);
					// alex_debug(0,1,"",unserialize($ser));
					$query = $wpdb->prepare( "INSERT INTO " . $wpdb->prefix . "bp_groups_groupmeta
						( group_id,meta_key,meta_value)
						VALUES ( %d,%s, %s )", $new_id, 'a21_bgc_event_tasks',$ser_str );
					$wpdb->query( $query );
					* **** as21 option 1**** */

					/* **** as21 option 2**** */

				   //  foreach ($event_tasks['time'] as $k => $time):
				   //     echo $time;
				   // endforeach;

					/* main func
					$times = $esc_post['new_event_tasks']['time'];
					unset($esc_post['new_event_tasks']['time']);

				    // alex_debug(0,1,"",$times); 
				    // alex_debug(0,1,"",$esc_post); 
				    foreach ($esc_post['new_event_tasks'] as $task):
				    	$i = 0;  // counter time
				         foreach ($task as $k => $title_and_cnt):
				         	  if($k === 'task') $title_task = $title_and_cnt;
				         	  else { $sql .= $wpdb->prepare("(%d,%s,%s,%d),", $new_id, $title_task, $times[$i], $title_and_cnt);  $i++;}
				         	  // $new_id - id last add event
				         	  //  (task_title,task_time,count_vol) VALUES ($title_task, $times[$i], $title_and_cnt);
				          endforeach;
				     endforeach;
				     $sql = substr($sql,0, -1);
				     $sql = "INSERT INTO {$wpdb->prefix}bp_groups_bgc_tasks (event_id,task_title,task_time,count_vol) VALUES ".$sql;
				     $wpdb->query($sql);
					*/

				     // echo "as21 option 2";
				    // exit;
					// alex_debug(0,1,"",$_POST);
					// header("Location: http://ya.ru");
				     // wp_redirect( "http://ya.ru", 303 );
					// exit;
					// echo '<script type="text/javascript">location.reload();</script>';					
					// header("Location: ".$_SERVER['HTTP_REFERER']);	

					/* **** as21 option 2**** */

				}
				/**** a21 tasks post ******/




				/**** a21 ******/

				return true;
			} else {
				bp_core_add_message( __( "There was a problem saving to the DB", 'groupcalendar' ), 'error' );

				return false;
			}

		}

	} 	// end if isset ($_POST['create-event'] ) 
}

//register activities
function bp_group_calendar_reg_activity() {
	global $bp;
	bp_activity_set_action( $bp->groups->id, 'new_calendar_event', __( 'New group event', 'groupcalendar' ) );
	bp_activity_set_action( $bp->groups->id, 'edit_calendar_event', __( 'Modified group event', 'groupcalendar' ) );
}

//adds actions to the group recent actions
function bp_group_calendar_event_add_action_message( $new, $event_id, $event_date, $event_title ) {
	global $bp;

	$url = bp_group_calendar_create_event_url( $event_id );

	if ( $new ) {
		$created_type     = __( 'created', 'groupcalendar' );
		$component_action = 'new_calendar_event';
	} else {
		$created_type     = __( 'modified', 'groupcalendar' );
		$component_action = 'edit_calendar_event';
	}

	$date = bgc_date_display( $event_date );

	/* Record this in group activity stream */
	$action  = sprintf( __( '%s %s an event for the group %s:', 'groupcalendar' ), bp_core_get_userlink( $bp->loggedin_user->id ), $created_type, '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . esc_attr( $bp->groups->current_group->name ) . '</a>' );
	$content = '<a href="' . $url . '" title="' . __( 'View Event', 'groupcalendar' ) . '">' . $date . ': ' . stripslashes( $event_title ) . '</a>';

	$activity_id = groups_record_activity( array(
		'action'            => $action,
		'content'           => $content,
		'primary_link'      => $url,
		'type'              => $component_action,
		'item_id'           => $bp->groups->current_group->id,
		'secondary_item_id' => $event_id
	) );

	//update group last updated
	groups_update_last_activity( $bp->groups->current_group->id );
	do_action( 'bp_groups_posted_update', $content, get_current_user_id(), $bp->groups->current_group->id, $activity_id );

}

//send the email
function bp_group_calendar_event_email( $new, $event_id, $event_date, $event_title ) {
	global $wpdb, $current_user, $bp, $bgc_email_default;

	//check if email is disabled for group
	$email = groups_get_groupmeta( $bp->groups->current_group->id, 'group_calendar_send_email' );
	if ( empty( $email ) ) {
		$email = BGC_EMAIL_DEFAULT;
	}
	if ( $email == 'no' ) {
		return;
	}

	//prepare fields
	$date              = bgc_date_display( $event_date );
	$url               = bp_group_calendar_create_event_url( $event_id );
	$site_name         = get_blog_option( BP_ROOT_BLOG, 'blogname' );
	$email_subject     = sprintf( __( '%s created a new event for your group %s', 'groupcalendar' ), bp_core_get_userlink( $bp->loggedin_user->id, true ), $bp->groups->current_group->name );
	$event_description = strip_tags( stripslashes( $_POST['event-desc'] ) );
	$location          = strip_tags( trim( stripslashes( $_POST['event-loc'] ) ) );

	//send emails
	$group_link = trailingslashit( bp_get_group_permalink( $bp->groups->current_group ) );

	$user_ids = BP_Groups_Member::get_group_member_ids( $bp->groups->current_group->id );

	foreach ( $user_ids as $user_id ) {
		//skip opt-outs
		if ( 'no' == get_user_meta( $user_id, 'notification_groups_calendar_event', true ) ) {
			continue;
		}

		$ud = get_userdata( $user_id );

		// Set up and send the message
		$to = $ud->user_email;

		$settings_link = bp_core_get_user_domain( $user_id ) . 'settings/notifications/';

		$message = sprintf( __(
			"%s:
---------------------

%s
%s

Description:
%s

Location:
%s

---------------------
View this Event: %s
View this Group: %s

%s
", 'groupcalendar' ), $email_subject, stripslashes( $event_title ), $date, $event_description, $location, $url, $group_link, $site_name );

		$message .= sprintf( __( 'To unsubscribe from these emails please login and visit: %s', 'groupcalendar' ), $settings_link );

		$message = wp_specialchars_decode( $message, ENT_QUOTES ); //decode quotes for email

		// Send it
		wp_mail( $to, $email_subject, $message );

		unset( $message, $to );
	}

}

function bp_group_calendar_get_capabilities() {
	global $bp;

	if ( bp_group_is_admin() ) {
		return 'full';
	} else if ( bp_group_is_mod() ) {

		$group_calendar_moderator_capabilities = groups_get_groupmeta( $bp->groups->current_group->id, 'group_calendar_moderator_capabilities' );
		if ( empty( $group_calendar_moderator_capabilities ) ) {
			return BGC_MODERATOR_DEFAULT;
		} else {
			return $group_calendar_moderator_capabilities;
		}

	} else if ( bp_group_is_member() ) {

		$group_calendar_member_capabilities = groups_get_groupmeta( $bp->groups->current_group->id, 'group_calendar_member_capabilities' );
		if ( empty( $group_calendar_member_capabilities ) ) {
			return BGC_MEMBER_DEFAULT;
		} else {
			return $group_calendar_member_capabilities;
		}

	} else {
		return 'none';
	}

}

function bp_group_calendar_url_parse() {

	global $wpdb, $current_site;

	$calendar_url = $_SERVER['REQUEST_URI'];

	$calendar_url_clean = explode( "?", $calendar_url );

	$calendar_url = $calendar_url_clean[0];

	$calendar_url_sections = explode( "/callout/", $calendar_url );

	$calendar_url = $calendar_url_sections[1];

	$calendar_url = ltrim( $calendar_url, "/" );

	$calendar_url = rtrim( $calendar_url, "/" );

	$base = $calendar_url_sections[0] . '/callout/';

	$base = ltrim( $base, "/" );

	$base = rtrim( $base, "/" );

	if ( ! empty( $calendar_url ) ) {

		$calendar_url          = ltrim( $calendar_url, "/" );
		$calendar_url          = rtrim( $calendar_url, "/" );
		$calendar_url_sections = explode( "/", $calendar_url );

		//check for valid dates.
		if ( isset( $calendar_url_sections[0] ) && $calendar_url_sections[0] >= 2000 && $calendar_url_sections[0] < 3000 ) {
			$date['year'] = (int) $calendar_url_sections[0];
		}

		if ( isset( $date['year'] ) && isset( $calendar_url_sections[1] ) && $calendar_url_sections[1] >= 1 && $calendar_url_sections[1] <= 12 ) {
			$date['month'] = (int) $calendar_url_sections[1];
		}

		if ( isset( $date['month'] ) && isset( $calendar_url_sections[2] ) && $calendar_url_sections[2] >= 1 && $calendar_url_sections[2] <= 31 ) {
			$date['day'] = (int) $calendar_url_sections[2];
		}

	}

	$date['base'] = $base;

	return $date;

}

function bp_group_calendar_event_url_parse() {

	$url = $_SERVER['REQUEST_URI'];
	$full_url = $url; // a21
	// if ( strpos( "/calendar/event/", $url ) !== false ) {
	// 	return false;
	// }

	/**** a21 ******/
	// echo 'strpos( "/calendar/event/", $url )= ';
	// var_dump(strpos( "/calendar/event/", $url ));
	// echo "<br>";
	// echo 'strpos( $url, "/huddle/")=';
	// var_dump(strpos( $url, "/huddle/"));
	// var_dump(preg_match("#\/huddle\/$#i", $url));
	// echo "<br>";
	// echo $url;
	// echo "<br>";
	// exit;
	/**** a21 ******/

	$url_clean    = explode( "?", $url );
	$url          = $url_clean[0];
	$url_sections = explode( "/callout/", $url );
	$url          = isset( $url_sections[1] ) ? $url_sections[1] : '';
	$url          = ltrim( $url, "/" );
	$slug          = rtrim( $url, "/" ); // a21
	$url          = rtrim( $url, "edit/" );
	$url          = rtrim( $url, "delete/" );
	$url          = rtrim( $url, "/" );


	/**** a21 ******/
	// echo "<br><br>";
	// echo "=after parsing url: ";
	// echo $url;
	// echo "<br>";
	// echo $slug;
	// echo "<br>";
	// if ( strpos( $full_url,"/huddle/event/" ) !== false && strpos( $full_url,"/edit/" ) === false && strpos( $full_url,"delete/" ) === false) {
	if ( !preg_match("#\/callout\/$#i", $full_url) && strpos( $full_url,"/edit/" ) === false && strpos( $full_url,"delete/" ) === false) {
		// echo "======================";
		global $wpdb;
		$url = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}bp_groups_calendars WHERE event_slug='%s'",sanitize_text_field($slug)));
		// echo "get id form bd ".$slug;
		// echo "<br>";
		// var_dump($url);
		// exit;
	}
	// echo "<br>";
	// echo $url;
	// echo "<br>";
	// echo strpos( "/calendar/event/",$url);
	/**** a21 ******/


	if ( is_numeric( $url ) ) {
		return (int) $url;
	} else {
		return false;
	}


}

function bp_group_calendar_event_is_edit() {

	$url = $_SERVER['REQUEST_URI'];
	if ( strpos( $url, "/edit/" ) ) {
		return true;
	} else {
		return false;
	}

}

function bp_group_calendar_event_is_delete() {
	global $wpdb, $current_user, $bp;

	$calendar_capabilities = bp_group_calendar_get_capabilities();
	$event_id              = bp_group_calendar_event_url_parse();
	$group_id              = $bp->groups->current_group->id;
	$url                   = $_SERVER['REQUEST_URI'];
	if ( strpos( $url, "/delete/" ) && $event_id ) {

		//check nonce
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'event-delete-link' ) ) {
			bp_core_add_message( __( 'There was a security problem', 'groupcalendar' ), 'error' );

			return false;
		}

		if ( $calendar_capabilities == 'none' ) {
			bp_core_add_message( __( 'You do not have permission to delete events', 'groupcalendar' ), 'error' );

			return false;
		}

		//can user modify this event?
		if ( $calendar_capabilities == 'limited' ) {
			$creator_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM " . $wpdb->base_prefix . "bp_groups_calendars WHERE id = %d AND group_id = %d", $event_id, $group_id ) );

			if ( $creator_id != $current_user->ID ) {
				bp_core_add_message( __( "You don't have permission to delete that event", 'groupcalendar' ), 'error' );

				return false;
			}
		}

		//delete event
		$result = $wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "bp_groups_calendars WHERE id = %d AND group_id = %d LIMIT 1", $event_id, $group_id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "bp_groups_bgc_tasks WHERE event_id = %d", $event_id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "bp_groups_bgc_time WHERE event_id = %d", $event_id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "bp_groups_groupmeta WHERE group_id = %d AND meta_key=%s", $event_id,'a21_bgc_event_image' ) );

		if ( ! $result ) {
			bp_core_add_message( __( "There was a problem deleting", 'groupcalendar' ), 'error' );

			return false;
		}

		//delete activity tied to event
		$result = $wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->base_prefix . "bp_activity WHERE item_id = %d AND secondary_item_id = %d", $group_id, $event_id ) );

		//success!
		bp_core_add_message( __( 'Event deleted successfully', 'groupcalendar' ) );

		return true;

	} else {
		return false;
	}

}


function bp_group_calendar_create_event_url( $event_id, $edit = false ) {
	global $bp,$wpdb;

	$url = bp_get_group_permalink( $bp->groups->current_group );
	/***** a21 ******/
	// echo "====event_id=".$event_id;
	if(!$edit){
		$event_title = $wpdb->get_var( "SELECT event_title FROM {$wpdb->prefix}bp_groups_calendars WHERE id='{$event_id}'");
		$event_title = strtolower($event_title);
		$event_title = str_replace(" ", "-", $event_title);
		// echo "<br><br>bp_group_calendar_create_event_url<br>";
		// $url .= 'huddle/event/' . $event_title . '/';
		$url .= 'callout/' . $event_title . '/';
	}else{
		$url .= 'callout/' . $event_id . '/';
	}
	// exit;
	/***** a21 ******/

	// $url .= 'huddle/event/' . $event_id . '/';

	if ( $edit ) {
		$url .= "edit/";
	}

	return $url;

}
// function a21_bp_group_calendar_create_event_url( $event_id, $edit = false ) {
// 	global $bp,$wpdb;

// 	$url = bp_get_group_permalink( $bp->groups->current_group );
// 	/***** a21 ******/
// 	$event_title = $wpdb->get_var( "SELECT event_title FROM {$wpdb->prefix}bp_groups_calendars` WHERE id='{$event_id}'");
// 	$event_title = strtolower($event_title);
// 	$event_title = str_replace(" ", "_", $event_title);

// 	$url .= 'huddle/event/' . $event_title . '/';
// 	/***** a21 ******/

// 	// $url .= 'huddle/event/' . $event_id . '/';

// 	if ( $edit ) {
// 		$url .= "edit/";
// 	}

// 	return $url;

// }

//------------------------------------------------------------------------//

//---Output Functions-----------------------------------------------------//

//------------------------------------------------------------------------//


function bp_group_calendar_settings() {
	global $wpdb, $current_site, $groups_template;

	if ( ! empty( $groups_template->group ) ) {
		$group = $groups_template->group;
	}

	if ( ! empty( $group ) ) {
		$group_calendar_moderator_capabilities = groups_get_groupmeta( $group->id, 'group_calendar_moderator_capabilities' );
	}

	if ( empty( $group_calendar_moderator_capabilities ) ) {
		$group_calendar_moderator_capabilities = BGC_MODERATOR_DEFAULT;
	}

	if ( ! empty( $group ) ) {
		$group_calendar_member_capabilities = groups_get_groupmeta( $group->id, 'group_calendar_member_capabilities' );
	}

	if ( empty( $group_calendar_member_capabilities ) ) {
		$group_calendar_member_capabilities = BGC_MEMBER_DEFAULT;
	}

	if ( ! empty( $group ) ) {
		$email = groups_get_groupmeta( $group->id, 'group_calendar_send_email' );
	}
	if ( empty( $email ) ) {
		$email = BGC_EMAIL_DEFAULT;
	}

	?>
	<h4><?php _e( 'Group Calendar Settings', 'groupcalendar' ) ?></h4>
	<label for="group-calendar-moderator-capabilities"><?php _e( 'Moderator Capabilities', 'groupcalendar' ) ?></label>

	<select name="group-calendar-moderator-capabilities" id="group-calendar-moderator-capabilities">

		<option value="full" <?php if ( $group_calendar_moderator_capabilities == 'full' ) {
			echo 'selected="selected"';
		} ?>><?php _e( 'Create events / Edit all events', 'groupcalendar' ); ?></option>

		<option value="limited" <?php if ( $group_calendar_moderator_capabilities == 'limited' ) {
			echo 'selected="selected"';
		} ?>><?php _e( 'Create events / Edit own events', 'groupcalendar' ); ?></option>

		<option value="none" <?php if ( $group_calendar_moderator_capabilities == 'none' ) {
			echo 'selected="selected"';
		} ?>><?php _e( 'No capabilities', 'groupcalendar' ); ?></option>

	</select>

	<label for="group-calendar-member-capabilities"><?php _e( 'Member Capabilities', 'groupcalendar' ) ?></label>

	<select name="group-calendar-member-capabilities" id="group-calendar-member-capabilities">

		<option value="full" <?php if ( $group_calendar_member_capabilities == 'full' ) {
			echo 'selected="selected"';
		} ?>><?php _e( 'Create events / Edit all events', 'groupcalendar' ); ?></option>

		<option value="limited" <?php if ( $group_calendar_member_capabilities == 'limited' ) {
			echo 'selected="selected"';
		} ?>><?php _e( 'Create events / Edit own events', 'groupcalendar' ); ?></option>

		<option value="none" <?php if ( $group_calendar_member_capabilities == 'none' ) {
			echo 'selected="selected"';
		} ?>><?php _e( 'No capabilities', 'groupcalendar' ); ?></option>

	</select>

	<label><?php _e( 'Enable Email Notification of New Events', 'groupcalendar' ) ?><br/>
		<small><?php _e( 'Individual users can disable notifications', 'groupcalendar' ) ?></small>
	</label>
	<label><input value="yes" name="group-calendar-send-email"
	              type="radio"<?php echo ( $email == 'yes' ) ? ' checked="checked"' : ''; ?> /> <?php _e( 'Yes', 'groupcalendar' ) ?>
	</label>
	<label><input value="no" name="group-calendar-send-email"
	              type="radio"<?php echo ( $email == 'no' ) ? ' checked="checked"' : ''; ?> /> <?php _e( 'No', 'groupcalendar' ) ?>
	</label><br/>
	<hr/>
	<?php
}

function bp_group_calendar_notification_settings() {
	if ( ! $notification_groups_calendar_event = bp_get_user_meta( bp_displayed_user_id(), 'notification_groups_calendar_event', true ) ) {
		$notification_groups_calendar_event = 'yes';
	}
	?>
	<tr>
		<td></td>
		<td><?php _e( 'A new group event is created', 'groupcalendar' ) ?></td>
		<td class="yes"><input type="radio" name="notifications[notification_groups_calendar_event]"
		                       value="yes" <?php checked( $notification_groups_calendar_event, 'yes', true ) ?>/></td>
		<td class="no"><input type="radio" name="notifications[notification_groups_calendar_event]"
		                      value="no" <?php checked( $notification_groups_calendar_event, 'no', true ) ?>/></td>
	</tr>
	<?php
}

//enqeue js on product settings screen
function bp_group_calendar_js() {

	if ( ! is_admin() && strpos( $_SERVER['REQUEST_URI'], '/callout/' ) !== false ) {
		global $bgc_locale;

		wp_enqueue_style( 'groupcalendar-css', plugins_url( '/bp-group-calendar/groupcalendar/group_calendar.css' ), false );
		wp_enqueue_style( 'jquery-datepicker-css', plugins_url( '/bp-group-calendar/groupcalendar/datepicker/css/smoothness/jquery-ui-1.10.3.custom.min.css' ), '1.10.3' );
		wp_enqueue_script( 'jquery-ui-datepicker' );

		//only load languages for datepicker if not english (or it will show Chinese!)
		if ( $bgc_locale['code'] != 'en' ) {
			wp_enqueue_script( 'jquery-datepicker-i18n', plugins_url( '/bp-group-calendar/groupcalendar/datepicker/js/jquery-ui-i18n.min.js' ), array( 'jquery-ui-datepicker' ) );
		}
	}
}

function bp_group_calendar_js_output() {
	//display css
	if ( ! is_admin() && strpos( $_SERVER['REQUEST_URI'], '/callout/' ) !== false ) {
		global $bgc_locale;
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				jQuery.datepicker.setDefaults(jQuery.datepicker.regional['<?php echo $bgc_locale['code']; ?>']);
				jQuery('#event-date').datepicker({
					dateFormat: 'yy-mm-dd',
					changeMonth: true,
					changeYear: true,
					firstDay: <?php echo $bgc_locale['week_start']; ?>
				});
				jQuery('a#event-delete-link').click(function () {
					var answer = confirm("<?php _e('Are you sure you want to delete this event?', 'groupcalendar'); ?>")
					if (answer) {
						return true;
					} else {
						return false;
					}
					;
				});
			});
		</script>
		<?php
	}
}

function bp_group_calendar_highlighted_events( $group_id, $date = '' ) {
	global $wpdb;

	if ( $date ) {
		$start_date = date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-', strtotime( $date ) ) . '01 00:00:00' ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
		$end_date   = date( 'Y-m-d H:i:s', strtotime( "+1 month", strtotime( $date ) ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
	} else {
		$start_date = date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-' ) . '01 00:00:00' ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
		$end_date   = date( 'Y-m-d H:i:s', strtotime( "+1 month", strtotime( $start_date ) ) );
	}

	$filter = $wpdb->prepare( " WHERE group_id = %d AND event_time >= %s AND event_time < %s", $group_id, $start_date, $end_date );

	$events = $wpdb->get_col( "SELECT event_time FROM " . $wpdb->base_prefix . "bp_groups_calendars" . $filter . " ORDER BY event_time ASC" );

	if ( $events ) {

		$highlighted_events = array();
		foreach ( $events as $event ) {
			$tmp_date             = strtotime( $event ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
			$highlighted_events[] = date( 'Y-m-d', $tmp_date );
		}

		return $highlighted_events;

	} else {
		return array();
	}

}

function bp_group_calendar_list_events( $group_id, $range, $date = '', $calendar_capabilities, $show_all = 0 ) {
	global $wpdb, $current_user;

	$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	$group_id    = (int) $group_id;//cast it just in case

	if ( $range == 'all' ) {

		$filter        = " WHERE group_id = $group_id";
		$empty_message = __( 'There are no scheduled events', 'groupcalendar' );

	} else if ( $range == 'month' ) {

		if ( $date ) {
			$start_date = date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-', strtotime( $date ) ) . '01 00:00:00' ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
			$end_date   = date( 'Y-m-d H:i:s', strtotime( "+1 month", strtotime( $date ) ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
		} else {
			$start_date = date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-' ) . '01 00:00:00' ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
			$end_date   = date( 'Y-m-d H:i:s', strtotime( "+1 month", strtotime( $start_date ) ) );
		}
		$filter        = " WHERE group_id = $group_id AND event_time >= '$start_date' AND event_time < '$end_date'";
		$empty_message = __( 'There are no events scheduled for this month', 'groupcalendar' );

	} else if ( $range == 'day' ) {

		$start_date    = date( 'Y-m-d H:i:s', strtotime( $date ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
		$end_date      = date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d', strtotime( $date ) ) . ' 23:59:59' ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
		$filter        = " WHERE group_id = $group_id AND event_time >= '$start_date' AND event_time <= '$end_date'";
		$empty_message = __( 'There are no events scheduled for this day', 'groupcalendar' );
		$date_format   = get_option( 'time_format' );

	} else if ( $range == 'upcoming' ) {

		$filter        = " WHERE group_id = $group_id AND event_time >= '" . date( 'Y-m-d H:i:s' ) . "'";
		$empty_message = __( 'There are no upcoming events', 'groupcalendar' );

	} else if ( $range == 'mine' ) {

		$filter        = " WHERE group_id = $group_id AND event_time >= '" . date( 'Y-m-d H:i:s' ) . "' AND user_id = " . $current_user->ID;
		$empty_message = __( 'You have no scheduled events', 'groupcalendar' );

	}

	if ( ! $show_all ) {
		$limit = " LIMIT 10";
	}

	$events = $wpdb->get_results( "SELECT * FROM " . $wpdb->base_prefix . "bp_groups_calendars" . $filter . " ORDER BY event_time ASC" . $limit );

	if ( $events ) {

		$events_list = '<ul class="events-list">';
		//loop through events
		foreach ( $events as $event ) {
			$class = ( $event->user_id == $current_user->ID ) ? ' class="my_event"' : '';

			$event_image = $wpdb->get_var( "SELECT meta_value FROM {$wpdb->prefix}bp_groups_groupmeta WHERE group_id='".$event->id."' AND meta_key='a21_bgc_event_image'");
			// deb_last_query();
			$img = (!empty($event_image)) ? "<img class='as21_event_image' src='".$event_image."' />" : '';

			$events_list .= "\n<li" . $class . ">".$img;

			$event_url = bp_group_calendar_create_event_url( $event->id);
			// $link_start .= '<a href="' . bp_group_calendar_create_event_url( $event->id ) . '" title="'. __( 'View Event', 'groupcalendar' ) . '" >';
			// $events_list .= '<a href="' . bp_group_calendar_create_event_url( $event->id ) . '" title="'. __( 'View Event', 'groupcalendar' ) . '" class="event_title">' . bgc_date_display( $event->event_time, $date_format ) . ': ' . stripslashes( $event->event_title ) .'</a>';
			$events_list .= '<p class="link_title_edit">
			<a href="' . $event_url . '" title="'. __( 'View Event', 'groupcalendar' ) . '" class="event_title">'.stripslashes( $event->event_title ) .'</a>';

			//add edit link if allowed
			if ( $calendar_capabilities == 'full' || ( $calendar_capabilities == 'limited' && $event->user_id == $current_user->ID ) ) {
				$events_list .= ' | <a href="' . bp_group_calendar_create_event_url( $event->id, true ) . '" title="' . __( 'Edit Event', 'groupcalendar' ) . '">' . __( 'Edit', 'groupcalendar' ) . ' &raquo;</a>';
			}
			$events_list .= '</p>
			 <a href="' . $event_url . '" title="'. __( 'View Event', 'groupcalendar' ) . '" class="">'.bgc_date_display( $event->event_time, $date_format ).'</a>';

			$events_list .= " <span>Volunteers Needed: ".$event->total_vols."</span>
							</li>";
		}
		$events_list .= "</ul>";

	} else { //no events for query
		$events_list = '<div id="message" class="info"><p>' . $empty_message . '</p></div>';
	}

	echo $events_list;
}

//------------------------------------------------------------------------//

//---Page Output Functions------------------------------------------------//

//------------------------------------------------------------------------//

//widgets

function bp_group_calendar_widget_day( $date ) {
	global $bp, $bgc_locale;

	$calendar_capabilities = bp_group_calendar_get_capabilities();

	$day = $date['year'] . '-' . $date['month'] . '-' . $date['day'];

	$cal                    = new Calendar( $date['day'], $date['year'], $date['month'] );
	$cal->week_start        = $bgc_locale['week_start'];
	$first_day              = $cal->year . "-" . $cal->month . "-01";
	$cal->highlighted_dates = bp_group_calendar_highlighted_events( $bp->groups->current_group->id, $first_day );
	$url                    = bp_get_group_permalink( $bp->groups->current_group ) . 'callout';
	$url                    = rawurldecode( $url );
	$cal->formatted_link_to = $url . '/%Y/%m/%d/';
	?>
	<div class="bp-widget">
		<h4><?php _e( 'Day View', 'groupcalendar' ); ?>
			: <?php echo date_i18n( get_option( 'date_format' ), strtotime( $day ) ); ?></h4>
		<table class="calendar-view">
			<tr>
				<td class="cal-left">
					<?php print( $cal->output_calendar() ); ?>
				</td>
				<td class="cal-right">
					<h5 class="events-title"><?php _e( "Callout Event Helpers For", 'groupcalendar' ); ?> <?php echo date_i18n( get_option( 'date_format' ), strtotime( $day ) ); ?>
						:</h5>
					<?php bp_group_calendar_list_events( $bp->groups->current_group->id, 'day', $day, $calendar_capabilities ); ?>
				</td>
			</tr>
		</table>
	</div>
	<?php
}

function bp_group_calendar_widget_month( $date ) {
	global $bp, $bgc_locale;

	$calendar_capabilities = bp_group_calendar_get_capabilities();

	if ( isset( $date['month'] ) ) {
		//show selected month
		$cal = new Calendar( '', $date['year'], $date['month'] );
	} else {
		//show current month
		$cal = new Calendar();
	}

	$cal->week_start        = $bgc_locale['week_start'];
	$url                    = bp_get_group_permalink( $bp->groups->current_group ) . 'callout';
	$url                    = rawurldecode( $url );
	$cal->formatted_link_to = $url . '/%Y/%m/%d/';

	//first day of month for calulation previous and next months
	$first_day              = $cal->year . "-" . $cal->month . "-01";
	$cal->highlighted_dates = bp_group_calendar_highlighted_events( $bp->groups->current_group->id, $first_day );
	$previous_month         = $url . date( "/Y/m/", strtotime( "-1 month", strtotime( $first_day ) ) );
	$next_month             = $url . date( "/Y/m/", strtotime( "+1 month", strtotime( $first_day ) ) );
	$this_year              = $url . date( "/Y/", strtotime( $first_day ) );
	?>
	<div class="bp-widget">
		<h4>
			<?php _e( 'Month View', 'groupcalendar' ); ?>:
			<?php echo date_i18n( __( 'F Y', 'groupcalendar' ), strtotime( $first_day ) ); ?>
			<span>
        <a title="<?php _e( 'Previous Month', 'groupcalendar' ); ?>"
           href="<?php echo $previous_month; ?>">&larr; <?php _e( 'Previous', 'groupcalendar' ); ?></a> |
        <a title="<?php _e( 'Full Year', 'groupcalendar' ); ?>"
           href="<?php echo $this_year; ?>"><?php _e( 'Year', 'groupcalendar' ); ?></a> |
        <a title="<?php _e( 'Next Month', 'groupcalendar' ); ?>"
           href="<?php echo $next_month; ?>"><?php _e( 'Next', 'groupcalendar' ); ?> &rarr;</a>
      </span>
		</h4>
		<table class="calendar-view">
			<tr>
				<td class="cal-left">
					<?php print( $cal->output_calendar() ); ?>
				</td>
				<td class="cal-right">
					<h5 class="events-title"><?php _e( 'Event Callouts For', 'groupcalendar' ); ?> <?php echo date_i18n( 'F Y', strtotime( $first_day ) ); ?>
						:</h5>
					<?php bp_group_calendar_list_events( $bp->groups->current_group->id, 'month', $first_day, $calendar_capabilities ); ?>
				</td>
			</tr>
		</table>

	</div>
	<?php
}

function bp_group_calendar_widget_year( $date ) {
	global $bp, $bgc_locale;

	$calendar_capabilities = bp_group_calendar_get_capabilities();

	$year  = $date['year'];
	$month = 1;

	$url = bp_get_group_permalink( $bp->groups->current_group ) . 'callout';
	//first day of month for calulation previous and next years
	$first_day     = $year . "-01-01";
	$previous_year = $url . date( "/Y/", strtotime( "-1 year", strtotime( $first_day ) ) );
	$next_year     = $url . date( "/Y/", strtotime( "+1 year", strtotime( $first_day ) ) );

	?>
	<div class="bp-widget">
		<h4>
			<?php _e( 'Year View', 'groupcalendar' ); ?>: <?php echo date_i18n( 'Y', strtotime( $first_day ) ); ?>
			<span>
        <a title="<?php _e( 'Previous Year', 'groupcalendar' ); ?>"
           href="<?php echo $previous_year; ?>">&larr; <?php _e( 'Previous', 'groupcalendar' ); ?></a> |
        <a title="<?php _e( 'Next Year', 'groupcalendar' ); ?>"
           href="<?php echo $next_year; ?>"><?php _e( 'Next', 'groupcalendar' ); ?> &rarr;</a>
      </span>
		</h4>
		<?php
		//loop through years
		for ( $i = 1; $i <= 12; $i ++ ) {
			$cal                    = new Calendar( '', $year, $month );
			$cal->week_start        = $bgc_locale['week_start'];
			$cal->formatted_link_to = $url . '/%Y/%m/%d/';
			$first_day              = $cal->year . "-" . $cal->month . "-01";
			$cal->highlighted_dates = bp_group_calendar_highlighted_events( $bp->groups->current_group->id, $first_day );
			echo '<div class="year-cal-item">';
			print( $cal->output_calendar() );
			echo '</div>';

			//first day of month for calulation of next month
			$first_day  = $year . "-" . $month . "-01";
			$next_stamp = strtotime( "+1 month", strtotime( $first_day ) );
			$year       = date( "Y", $next_stamp );
			$month      = date( "m", $next_stamp );
		}
		?>
		<div class="clear"></div>
	</div>
	<?php
}

function bp_group_calendar_widget_upcoming_events() {
	global $bp;

	$calendar_capabilities = bp_group_calendar_get_capabilities();

	?>
	<div class="bp-widget">
		<h4><?php _e( 'Upcoming Events', 'groupcalendar' ); ?></h4>
		<?php bp_group_calendar_list_events( $bp->groups->current_group->id, 'upcoming', '', $calendar_capabilities ); ?>
	</div>
	<?php
}

function bp_group_calendar_widget_my_events() {
	global $bp;

	if ( ! is_user_logged_in() ) {
		return;
	}

	$calendar_capabilities = bp_group_calendar_get_capabilities();

	?>
	<div class="bp-widget">
		<h4><?php _e( 'My Events', 'groupcalendar' ); ?></h4>
		<?php bp_group_calendar_list_events( $bp->groups->current_group->id, 'mine', '', $calendar_capabilities ); ?>
	</div>
	<?php
}


function bp_group_calendar_widget_event_display( $event_id ) {
	global $wpdb, $current_user, $bp, $bgc_locale;

	$group_id = $bp->groups->current_group->id;

	$calendar_capabilities = bp_group_calendar_get_capabilities();

	$event = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "bp_groups_calendars WHERE group_id = %d AND id = %d", $group_id, $event_id ) );

	/****** a21 event details*******/

	// var_dump($bgc_locale);
	// echo 'bp_group_calendar_widget_event_display()';
	// echo "<br>";
	// echo "event id=".$event_id;
	// exit;
	/****** a21 event details*******/

	//do nothing if invalid event_id
	if ( ! $event ) {
		return;
	}

	//creat edit link if capable
	if ( $calendar_capabilities == 'full' || ( $calendar_capabilities == 'limited' && $current_user->ID == $event->user_id ) ) {
		$edit_link = '<span><a href="' . bp_group_calendar_create_event_url( $event_id, true ) . '" title="' . __( 'Edit Event', 'groupcalendar' ) . '">' . __( 'Edit', 'groupcalendar' ) . ' &rarr;</a></span>';
	}

	$map_url = 'http://maps.google.com/maps?hl=' . $bgc_locale['code'] . '&q=' . urlencode( stripslashes( $event->event_location ) );

	$event_created_by    = bp_core_get_userlink( $event->user_id );
	$event_created       = bgc_date_display( $event->created_stamp, get_option( 'date_format' ) . __( ' \a\t ', 'groupcalendar' ) . get_option( 'time_format' ) );
	$event_time       = bgc_date_display( $event->event_time, get_option( 'date_format' ) . __( ' \a\t ', 'groupcalendar' ) . get_option( 'time_format' ) );
	$event_modified_by   = bp_core_get_userlink( $event->last_edited_id );
	$event_last_modified = bgc_date_display( $event->last_edited_stamp, get_option( 'date_format' ) . __( ' \a\t ', 'groupcalendar' ) . get_option( 'time_format' ) );

	$event_meta = '<span class="event-meta">' . sprintf( __( 'Created by %1$s on %2$s. Last modified by %3$s on %4$s.', 'groupcalendar' ), $event_created_by, $event_created, $event_modified_by, $event_last_modified ) . '</span>';

	?>
	<div class="bp-widget">
		<h5>
			<?php _e( 'Event Details', 'groupcalendar' ); ?>
			<?php echo $edit_link; ?>
		</h5>
		<div class="bgc-event-details-left">
			<h5 class="events-title"><?php echo stripslashes( $event->event_title ); ?></h5>
			<h6 class="event-label"><?php _e( 'Date/Time:', 'groupcalendar' ); ?></h6>
			<?php echo $event_time; ?>
			<span
				class="activity"><?php echo bgc_date_display( $event->event_time, get_option( 'date_format' ) . __( ' \a\t ', 'groupcalendar' ) . get_option( 'time_format' ) ); ?></span>

			<?php if ( $event->event_description ) : ?>
				<h6 class="event-label"><?php _e( 'Description:', 'groupcalendar' ); ?></h6>
				<div class="event-description">
					<?php echo stripslashes( $event->event_description ); ?>
				</div>
			<?php endif; ?>

		<?php if ( $event->event_location ) : ?>
			<h6 class="event-label"><?php _e( 'Location:', 'groupcalendar' ); ?></h6>
			<div class="event-location">

				<?php echo stripslashes( $event->event_location ); ?>

				<?php if ( $event->event_map ) : ?>
					<span class="event-map">
    	    <a href="<?php echo $map_url; ?>" target="_blank"
	           title="<?php _e( 'View Google Map of Event Location', 'groupcalendar' ); ?>"><?php _e( 'Map', 'groupcalendar' ); ?> &raquo;</a>
    	  </span>
				<?php endif; ?>
			</div>
		<?php endif; ?>


		</div>

		<?php
		/**** a21_display******/
		$event_image = $wpdb->get_var( "SELECT meta_value FROM {$wpdb->prefix}bp_groups_groupmeta WHERE group_id='{$event_id}' AND meta_key='a21_bgc_event_image'");
		// $event_tasks = $wpdb->get_var( "SELECT meta_value FROM {$wpdb->prefix}bp_groups_groupmeta WHERE group_id='{$event_id}' AND meta_key='a21_bgc_event_tasks'");
		// $event_tasks = unserialize($event_tasks);

		// $event_tasks = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bp_groups_bgc_tasks WHERE event_id='{$event_id}'");
		$event_tasks = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bp_groups_bgc_tasks WHERE event_id='{$event_id}' ORDER BY id");
		$event_times = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bp_groups_bgc_time WHERE event_id='{$event_id}'");

	    // foreach ($esc_post['new_event_tasks'] as $task):
	    // 	$i = 0;  // counter time
	    //      foreach ($task as $k => $title_and_cnt):
	    //      	  if($k === 'task') $title_task = $title_and_cnt;
	    //      	  else { $sql .= $wpdb->prepare("(%d,%s,%s,%d),", $new_id, $title_task, $times[$i], $title_and_cnt);  $i++;}
	    //      	  // $new_id - id last add event
	    //      	  //  (task_title,task_time,count_vol) VALUES ($title_task, $times[$i], $title_and_cnt);
	    //       endforeach;
	    //  endforeach;
	    //  $sql = substr($sql,0, -1);
	    //  $sql = "INSERT INTO {$wpdb->prefix}bp_groups_bgc_tasks (event_id,task_title,task_time,count_vol) VALUES ".$sql;
	    //  $wpdb->query($sql);

		echo "<div class='image-social bgc-event-image'>";
		if($event_image) {?> 
			<img class="" src="<?php echo $event_image;?>" alt="">
		<?php }
		$event_full_link = "http://".$_SERVER['HTTP_HOST']."/".$_SERVER['REQUEST_URI'];
?>
		<div class="share"
			<h4>Helping recruit Volunteers? If so, please share link:</h4>
			<ul>
			<li><a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $event_full_link;?>" title="Share this post on Facebook" target="_blank" class="facebook">Facebook</a></li>
			<li><a href="https://plus.google.com/share?url=<?php echo $event_full_link;?>" title="Share this on Google+" target="_blank" class="googleplus">Google+</a></li>
			<li><a href="http://twitter.com/share?url=<?php echo $event_full_link;?>&text=<?php echo stripslashes( $event->event_title ); ?>&via=dugoodr" title="Share this post on Twitter" target="_blank" class="twitter">Twitter</a></li>
			<!-- https://www.linkedin.com/shareArticle?mini=true&url=http%3A//dugoodr2.dev/causes/ottawa-mission/callout/new-event-3x3-19-april--ottawa-mission-s4/&title=dfsdfdf&summary=&source= -->
			<li><a href="http://www.linkedin.com/shareArticle?mini=true&url=<?php echo $event_full_link;?>&title=<?php echo stripslashes( $event->event_title ); ?>" title="Share this post on LinkedIn" target="_blank" class="linkedin">LinkedIn</a></li>
			<li><a href="http://www.pinterest.com/pin/create/button/?url=<?php if($event_image) echo $event_image;?>&media=<?php if($event_image) echo $event_image;?>&description=<?php echo stripslashes( $event->event_title ); ?>" target="_blank" class="pinterest" data-pin-do='buttonPin' data-pin-config='above'>Pinterest</a></li>
			<li><a href="mailto:?subject=<?php echo stripslashes( $event->event_title ); ?>&amp;body=<?php echo $event_full_link;?>" title="Share this with a friend!" class="email">Email</a></li>
			</ul>
		</div>
		</div> <!-- end .image-social -->

		<div class="clearfix"></div>



<?php

		/* **** as21 parse ids_vols & count vols**** */

		// echo "as21 parse ids_vols & count vols<hr>";

		// alex_debug(0,1,"orig arr tasks",$event_tasks);
		// alex_debug(0,1,"",$event_times);

		foreach ($event_tasks as $k => $task ) {
			$cnt_vols = explode(",",$task->cnt_vols);
			$event_tasks[$k]->cnt_vols = $cnt_vols;

			// $ids_vols = explode(":",$task->ids_vols);
			// $event_tasks[$k]->ids_vols = $ids_vols;
			// echo "==== inside loop as21 parse ids_vols & count vols ====<br>";
			// print_r($task);

			if( !empty($task->ids_vols) ) {

				$ids_vols = explode(":",$task->ids_vols);
				$event_tasks[$k]->ids_vols = $ids_vols;
				// print_r($cnt_vols);
				// alex_debug(0,1,"ids_vols=",$ids_vols);
				foreach ($ids_vols as $k2 => $v) {
					// if(!$v) break;  // comment today (когда 1 ряд и много колонок ids_vols не парсятся)
					// echo "v==".$v;
					$ids_arr = explode(",",$v);
					$event_tasks[$k]->ids_vols[$k2] = $ids_arr;
					// print_r($event_tasks[$k]->cnt_vols);
					// $event_tasks[$k]->ids_cnt[$k2] = $ids_arr;
					// $event_tasks[$k]->ids_cnt[$k2]['cnt'] = $event_tasks[$k]->cnt_vols[$k2];
					// $event_tasks[$k]->ids_cnt[$k2]['cnt'] = $event_tasks[$k]->cnt_vols[$k2];
					// echo "<br>";
					// echo "====k2-".$k2." v-".$v."<br>";
				}

			}else { unset($task->ids_vols);}

		}
		// alex_debug(0,1,"arr tasks after parse",$event_tasks);

		/* **** as21 parse ids_vols & count vols**** */



		// echo "<br>is_login "; var_dump(is_user_logged_in());
		if(is_user_logged_in()){
			$cur_user = wp_get_current_user();
			// echo "<br>".$cur_user->ID;
			// echo "<br>".$cur_user->data->user_login;
		}

		// alex_debug(0,1,"",$cur_user);

		$need = " Needed";
		if( !empty($event_tasks)):?>

		    <h6 class="event-label">Total DuGoodrs Needed:</h6> 
		    <?php if( !empty($event->total_vols) ) echo $event->total_vols;?>
		    <!-- <h6 class="event-label">Thank-you Message:</h6>  -->
		    <!-- <p class='a21-system-message'>for testing</p> -->
		    <?php //if( !empty($event->thank_you) ) echo stripslashes($event->thank_you); ?>
				
			<h6 class="event-label">Event Tasks & Shifts:</h6><p class="a21-system-box">*NOTE* Event Callout/Shifts in Test mode, still under development</p>
<?php /*
			<table id="a21_bgc_tasks_shifts" style="margin-bottom: 5px;">
	        	<tr class="title_columns">
	        	   <th class="a21_dinam_th_coll"> Task item </th>
	        	<?php foreach ($event_tasks['time'] as $k => $time):?>
	        		<th class="a21_dinam_th_coll"> <?php echo $time;?> </th>
		        <?php endforeach;?>
		       </tr>
		       <?php unset($event_tasks['time']);  // alex_debug(0,1,"",$event_tasks);  ?>
	        	<?php foreach ($event_tasks as $task):?>
		        <tr class="a21_dinam_row">
	        		<?php foreach ($task as $k => $task_cnt_vol):?>
	        		 <td class="a21_dinam_coll"> 
	        		 <?php if($k !== 'task'){?> 
	        		 <button class="a21_add_new_volunteer" data-id="<?php echo $cur_user->ID;?>" data-nick="<?php echo $cur_user->data->user_nicename;?>">signup</button><?php }?>
	        		 <p>
	        		  <?php echo "<span class='vol_cnt'>".$task_cnt_vol."</span> "; if($k !== 'task') echo $need;?> 
	        		  </p>
	        		 </td>
		      		 <?php endforeach;?>
    	        </tr>
		        <?php endforeach;?>
			</table>
 */
?>
			<?php /* **** as21 parse ids_vols & count vols - output **** */ ?>

			<table id="a21_bgc_tasks_shifts" data-event-id="<?php echo $event_id;?>" style="margin-bottom: 5px;">

	        	<tr class="title_columns">
	        	   <th class="a21_dinam_th_coll"> Task item </th>
	        	<?php foreach ($event_times as $k => $v):?>
	        		<th class="a21_dinam_th_coll"> <?php echo $v->time;?> </th>
		        <?php endforeach;?>
		       </tr>

	        	<?php foreach ($event_tasks as $k => $task):?>

		        <tr class="a21_dinam_row" data-task_id="<?php echo $task->id;?>">
		        	<td class="a21_dinam_coll"> <?php echo $task->task_title;?></td>

	        		<?php foreach ($task->cnt_vols as $k2 => $cnt):?>
	        			<?php
	        			// this array can have empty values ""
		        		 $arr_vols_cur_task = $event_tasks[$k]->ids_vols[$k2]; 			        	
 			        	 // alex_debug(0,1,"",$arr_vols_cur_task);

							$cnt_fill_el_arr_vols = 0;
							foreach ($arr_vols_cur_task as $v) {
								if( !empty($v)) $cnt_fill_el_arr_vols++;
							};
							
		        		  // $cur_count = count($arr_vols_cur_task);
		        		  $cur_count = $cnt_fill_el_arr_vols;
		        		  $still_need_count = $cnt - $cur_count;

	        			?>
		        		 <td class="a21_dinam_coll <?php if($cur_count >= $cnt) echo 'red-cell'; if($cur_count > 0 && $cur_count != $cnt) echo 'yellow-cell';?>"> 
		        		 <?php 
		        		 // var_dump($arr_vols_cur_task);

		        		 // echo "k2=".$k2."<br>";
		        		 // echo " cnt ".$cnt." == task->ids_vols[k2] $k2, ".count($task->ids_vols[$k2]);
		        		 // echo "<br>";
							
						 if($cur_count >= $cnt) echo "<p>FULL</p>";
 		        		 if( $still_need_count != 0 ) { echo "<p class='still_need'><span class='vol_cnt'>".$still_need_count."</span> "; echo $need."</p>"; }


						/* **** hide/show btn signup **** */

						 $vol_hide_btn = false;
						 $show_cancel_my_attandance = false;
   				 		 $cancel_my_attandance_html = "<button class='a21_cancel_my_attandance' data-s-need-cnt='".$still_need_count."'' data-i='".$k2."' data-task-id='".$task->id."' data-user-id='".$cur_user->ID."'>cancel my attendance</button>";


		        		 if( !empty($task->ids_vols[$k2]) ){
		        			 // echo "-----IDS VOLS---<br>";
		        		  	 // var_dump($task->ids_vols[$k2]);
		        		  // 	 echo "cnt=".$cnt."<br>";
		        			 // echo 'count($task->ids_vols[$k2] ='; var_dump( count($task->ids_vols[$k2]));
		        			 // echo "<br>";
		        			 // echo '$task->ids_vols[$k2] ='; var_dump( $task->ids_vols[$k2]);
		        			 // echo "<br>";

		        		 	// total count vols signup to event task
		        		 	$cnt_cur_vol_signup = 0;
		        		 	foreach ($task->ids_vols[$k2] as $v) {
		        		 		if( !empty($v) ) $cnt_cur_vol_signup++;
		        		 	}

			        		 // if( $cnt == count($task->ids_vols[$k2]) ) {
			        		 if( $cnt == $cnt_cur_vol_signup ) {
			        		 	$vol_hide_btn = true;
				        		 foreach ($task->ids_vols[$k2] as $vol_id) {	
				   				 	if($cur_user->ID == $vol_id) $show_cancel_my_attandance = $cancel_my_attandance_html;					   				 	
		        				 }
			        		 }else{
			        		 	 // if( !empty($task->ids_vols[$k2]) ):
					        		 foreach ($task->ids_vols[$k2] as $vol_id) {	
	 		 	 			        	 // echo "<br>cur_user=".$cur_user->ID.", vol_id=".$vol_id;
				 		        		 // echo "<br>";
		        		 	
					        		 	// hide if cur_user sign up to event task in cur time
					   				 	if( !empty($cur_user->ID) && $cur_user->ID == $vol_id) {
					   				 		// echo "---".$cur_user->ID; echo $vol_id;
					   				 		$vol_hide_btn = true; 
					   				 		$show_cancel_my_attandance = $cancel_my_attandance_html;					   				 	
					   				 	}
					   				 	// else $vol_hide_btn = false;
					        		 }
				        		 // endif;
			        	 	}
			        	 } else $vol_hide_btn = false;

 		        		  // if only one volunteer..e.g. 1:: or :5:
		        		  // if( !is_array($arr_vols_cur_task) && !empty($arr_vols_cur_task)) $vol_hide_btn = true;
		        		  if( !is_array($arr_vols_cur_task) && !empty($arr_vols_cur_task) && $arr_vols_cur_task == $cur_user->ID) $vol_hide_btn = true;

						/* **** hide/show btn signup **** */



		        		 // echo '<br>vol_hide_btn ='; var_dump($vol_hide_btn);
		        		 if(!$vol_hide_btn && is_user_logged_in() ):
		        		 ?>
			        		 <button class="a21_add_new_volunteer" data-s-need-cnt="<?php echo $still_need_count;?>" data-i="<?php echo $k2;?>" data-id="<?php echo $cur_user->ID;?>" data-nick="<?php echo $cur_user->data->user_nicename;?>">i will volunteer</button>
		        		 <?php endif;?>
		        		 <?php 
		        		 if(!$vol_hide_btn && !is_user_logged_in() ):
		        		 ?>
			        		 <button class="a21_bgc_user_signup">signup</button>
		        		 <?php endif;?>

		        		  <?php 
		        		  // alex_debug(0,1,"array vols cur task",$arr_vols_cur_task);

/* important for debug
		        		  echo "<p class='a21-system-message'>current count vols=".$cur_count."</p>";
		        		  if($cur_count >= $cnt) echo "<p class='a21-system-message'>full</p>";
		        		  echo "<p class='a21-system-message'>total for cur task & time=".$cnt."</p>";
*/
		        		  // echo "<br>";


		        		  // alex_debug(0,1,"event_tasks[k]->ids_vols",$event_tasks[$k]->ids_vols);
		        		  // echo "event_tasks[k]->ids_vols[k2]=".$arr_vols_cur_task;
		        		  // echo "<br>";
		        		  // var_dump($arr_vols_cur_task);
		        		  // echo "<br>";

		        		  // if only one volunteer..e.g. 1:: or :5:
		        		  // if( !is_array($arr_vols_cur_task) && !empty($arr_vols_cur_task) ) echo "id + ".$member_id."-".$member_name = bp_core_get_username($arr_vols_cur_task)."<br>";

		        		  if( !empty($show_cancel_my_attandance) ) echo $show_cancel_my_attandance;
		        		  $member_name = '';
		        		  $i = 1;
		        		  foreach ($arr_vols_cur_task as $member_id) {
	  	        		      if(!empty($member_id) ) {
	  	        		      	if($i == 1) echo "<p class='attendees'>Attendees: ";
	  	        		      	$member_name .= "<p class='a21-system-message'>id ".$member_id."-".bp_core_get_username($member_id)."</p>";
								$member_link = bp_core_get_userlink($member_id,false,true);	
								$member_avatar = bp_core_fetch_avatar( array('item_id'=>$member_id, 'width'=>"30",'html'=>true));
								echo "<a class='link-user-id-".$member_id."' href='".$member_link."' >".$member_avatar."</a>";
								if($i == count($arr_vols_cur_task)) echo "</p>";
								$i++;  	        		     
							 }
		        		  }
		        		  // echo $member_name;
		        		  ?> 
		        		 </td>
		      		 <?php endforeach;?>
    	        </tr>
		        <?php endforeach;?>
			</table>

			<?php /* **** as21 parse ids_vols & count vols - output **** */ ?>

		<?php 
		endif;

		/*
		if( !empty($event_tasks)):
			echo '<h6 class="event-label">Total DuGoodrs Needed:</h6>'.$event_tasks['total-volunteers'].$need;
			unset($event_tasks['total-volunteers']);
		 ?>
<!-- 
			 <h6 class="event-label">Event Tasks & Shifts:</h6>
			<table id="a21_bgc_tasks_shifts">
			<tr>
				<th>Task item</th>
				<th>SHIFT #1 (edit time details)</th>
				<th>SHIFT #2 (edit time details)</th>
			</tr>
 -->			
		<?php
			foreach ($event_tasks as $v):
				echo "<tr>";
				echo "<td>".$v['task']."</td>";
				echo "<td>".$v['shift_1'].$need."</td>";
				echo "<td>".$v['shift_2'].$need."</td>";
				echo "</tr>";
			endforeach;
			echo "</table>";
		endif;
		*/
		// alex_debug(0,1,"",$event_tasks);
		 /**** a21 ******/ 
		?>

		
		
		<p class="a21-system-box">Additional custom fields is still under development. Coming soon. Now you can only add or edit the image</p>


		<?php echo $event_meta; ?>

	</div>
	<?php
}


function bp_group_calendar_widget_create_event( $date ) {
	global $bp, $bgc_locale;

	$calendar_capabilities = bp_group_calendar_get_capabilities();

	//don't display widget if no capabilities
	if ( $calendar_capabilities == 'none' ) {
		return;
	}

	//if date given and valid, default form to it
	$default_date = '';
	if ( ! empty( $date['year'] ) && ! empty( $date['month'] ) && ! empty( $date['day'] ) ) {
		$timestamp = strtotime( $date['month'] . '/' . $date['day'] . '/' . $date['year'] );
		if ( $timestamp >= time() ) {
			$default_date = date( 'Y-m-d', $timestamp );
		}
	}

	$url = bp_get_group_permalink( $bp->groups->current_group ) . 'callout/';

	?>
	<div class="bp-widget">
		<h4><?php _e( 'Create Event', 'groupcalendar' ); ?></h4>

		<form action="<?php echo $url; ?>" name="add-event-form" id="add-event-form" class="standard-form" method="post"
		      enctype="multipart/form-data">
			<label for="event-title"><?php _e( 'Title', 'groupcalendar' ); ?> *</label>
			<input name="event-title" id="event-title" value="" type="text">

			<label for="event-date"><?php _e( 'Date', 'groupcalendar' ); ?> *
				<input name="event-date" id="event-date" value="<?php echo $default_date; ?>" type="text"></label>

			<label for="event-time"><?php _e( 'Time', 'groupcalendar' ); ?> *
				<select name="event-hour" id="event-hour">
					<?php
					if ( $bgc_locale['time_format'] == 24 ) {
						$max_hour = 23;
						$default_hour = date( 'G' );//default to current hour for new events
					} else {
						$max_hour = 12;
						$default_hour = date( 'g' );//default to current hour for new events
					}
					for ( $i = 1; $i <= $max_hour; $i ++ ) {
						$hour_check = ( $i == $default_hour ) ? ' selected="selected"' : '';
						echo '<option value="' . $i . '"' . $hour_check . '>' . $i . "</option>\n";
					}
					?>
				</select>
				<select name="event-minute" id="event-minute">
					<option value="00">:00</option>
					<option value="15">:15</option>
					<option value="30">:30</option>
					<option value="45">:45</option>
				</select>
				<?php if ( $bgc_locale['time_format'] == 12 ) : ?>
					<select name="event-ampm" id="event-ampm">
						<option value="am">am</option>
						<option value="pm">pm</option>
					</select>
				<?php endif; ?>
			</label>

			<label for="event-desc"><?php _e( 'Description', 'groupcalendar' ); ?></label>
			<?php
			if ( function_exists( 'wp_editor' ) ) {
				wp_editor( '', 'event-desc', array( 'media_buttons' => false, 'dfw' => false ) );
			} else {
				the_editor( '', 'event-desc', 'event-desc', false );
			}
			?>

			<label for="event-loc"><?php _e( 'Location', 'groupcalendar' ); ?></label>
			<input name="event-loc" id="event-loc" value="" type="text">
<!--
			<label for="event-map"><?php _e( 'Show Map Link?', 'groupcalendar' ); ?>
				<input name="event-map" id="event-map" value="1" type="checkbox" checked="checked"/>
				<small><?php _e( '(Note: Location must be an address)', 'groupcalendar' ); ?></small>
			</label>
-->

			
			<?php /**** a21 ******/?>
			<?php
			 // global $wp_rewrite;
			 // alex_debug(1,1,"",$wp_rewrite);
			?>
			<label for="a21_image_upload"><?php _e( 'Event image', 'groupcalendar' ); ?> </label>
			<input type="file" name="a21_image_upload" id="a21_image_upload" />
			<br>
			<br>
			<h4><?php _e( 'Create Schedule', 'groupcalendar' ); ?></h4>

			<label for="total-volunteers"><?php _e( 'Total Event Volunteers Needed', 'groupcalendar' ); ?></label>
			<input name="total-volunteers" id="total-volunteers" value="" type="text">

			<label for="event_tasks"><?php _e( 'Event Tasks & Shifts', 'groupcalendar' ); ?></label>
			<!-- <input name="event_tasks" id="event_tasks" value="" type="text"> -->
			<!--
			<table id="a21_bgc_tasks_shifts" style="margin-bottom: 5px;">
			<tr class="title_columns">
				<th>Task item</th>
			</tr>
			</table>
			-->
			<table id="a21_bgc_tasks_shifts" style="margin-bottom: 5px;">
			    <tbody>
			        <tr class="title_columns">
			            <th>Task item</th>
			            <th class="a21_dinam_th_coll"> time 1
			                <input type="text" name="new_event_tasks[time][0]" placeholder="Eg. 10:00am-11:30am">
			            </th>
			            <th class="a21_dinam_th_coll"> time 2
			                <input type="text" name="new_event_tasks[time][1]" placeholder="Eg. 2pm-4pm">
			            </th>
			        </tr>
			        <tr class="a21_dinam_row">
			            <td class="a21_dinam_coll">
			                <input type="text" name="new_event_tasks[0][task]" placeholder="Eg. Title task 1"> </td>
			            <td class="a21_dinam_coll vol_cnt">
			                <input type="text" name="new_event_tasks[0][time_1]" placeholder="2">
			            </td>
			            <td class="a21_dinam_coll vol_cnt">
			                <input type="text" name="new_event_tasks[0][time_2]" placeholder="2">
			            </td>
			        </tr>
			        <tr class="a21_dinam_row">
			            <td class="a21_dinam_coll">
			                <input type="text" name="new_event_tasks[1][task]" placeholder="Eg. Title task 2"> </td>
			            <td class="a21_dinam_coll vol_cnt">
			                <input type="text" name="new_event_tasks[1][time_1]" placeholder="2"> </td>
			            <td class="a21_dinam_coll vol_cnt">
			                <input type="text" name="new_event_tasks[1][time_2]" placeholder="2"> </td>
			        </tr>
			    </tbody>
			</table>

			<div class="wrap_btns_for_event_tasks">
				<div class="d_btn_left">
					<div id="a21_bgc_add_new_row">+ Add New Row</div>
					<div id="a21_bgc_add_new_column">+ Add New Column</div>
				</div>
				<div class="d_btn_right">
					<div id="a21_bgc_del_row">[x] Delete Row</div>
					<div id="a21_bgc_del_column">[x] Delete Column</div>
				</div>
			</div>
			<br>
			<label for="thank_you"><?php _e( 'Event Completion Thank-you Message', 'groupcalendar' ); ?></label>
			<!-- <input name="thank_you" id="thank_you" value="" type="text"> -->
			<textarea name="thank_you" id="thank_you" cols="30" rows="10"></textarea>
			<?php /**** a21 ******/?>

				
			<input name="create-event" id="create-event" value="1" type="hidden">
			<input name="group-id" id="group-id" value="<?php echo $bp->groups->current_group->id; ?>" type="hidden">
			<?php wp_nonce_field( 'bp_group_calendar' ); ?>

			<p><input value="<?php _e( 'Create Event', 'groupcalendar' ); ?> &raquo;" id="save" name="save"
			          type="submit"></p>

		</form>

	</div>
	<?php
}


function bp_group_calendar_widget_edit_event( $event_id = false ) {
	global $wpdb, $current_user, $bp, $bgc_locale;

	$url = bp_get_group_permalink( $bp->groups->current_group ) . 'callout/';

	$group_id = $bp->groups->current_group->id;

	$calendar_capabilities = bp_group_calendar_get_capabilities();

	//don't display widget if no capabilities
	if ( $calendar_capabilities == 'none' ) {
		bp_core_add_message( __( "You don't have permission to edit events", 'groupcalendar' ), 'error' );

		return false;
	}

	/**** a21 ******/
	// echo 'bp_group_calendar_widget_edit_event()===id '.$event_id;
	/**** a21 ******/

	if ( $event_id ) { //load from DB

		// $url .= 'event/' . $event_id . '/';
		$url .= '/' . $event_id . '/';

		$event = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->base_prefix . "bp_groups_calendars WHERE group_id = %d AND id = %d", $group_id, $event_id ) );

		if ( ! $event ) {
			return false;
		}

		//check limited capability users
		if ( $calendar_capabilities == 'limited' && $current_user->ID != $event->user_id ) {
			bp_core_add_message( __( "You don't have permission to edit that event", 'groupcalendar' ), 'error' );

			return false;
		}

		$event_title = stripslashes( $event->event_title );

		//check for valid date/time
		$tmp_date = strtotime( $event->event_time ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		if ( $tmp_date ) {
			$event_date = date( 'Y-m-d', $tmp_date );

			if ( $bgc_locale['time_format'] == 12 ) {
				$event_hour = date( 'g', $tmp_date );
			} else {
				$event_hour = date( 'G', $tmp_date );
			}

			$event_minute = date( 'i', $tmp_date );
			$event_ampm   = date( 'a', $tmp_date );
		} else {
			//default to current hour for new events
			if ( $bgc_locale['time_format'] == 12 ) {
				$event_hour = date( 'g' );
			} else {
				$event_hour = date( 'G' );
			}
		}

		$event_description = stripslashes( $event->event_description );
		$event_location    = stripslashes( $event->event_location );
		$event_map         = ( $event->event_map == 1 ) ? ' checked="checked"' : '';

		$event_created_by    = bp_core_get_userlink( $event->user_id );
		$event_created       = bgc_date_display( $event->created_stamp, get_option( 'date_format' ) . __( ' \a\t ', 'groupcalendar' ) . get_option( 'time_format' ) );
		$event_modified_by   = bp_core_get_userlink( $event->last_edited_id );
		$event_last_modified = bgc_date_display( $event->last_edited_stamp, get_option( 'date_format' ) . __( ' \a\t ', 'groupcalendar' ) . get_option( 'time_format' ) );

		$event_meta = '<span class="event-meta">' . sprintf( __( 'Created by %1$s on %2$s. Last modified by %3$s on %4$s.', 'groupcalendar' ), $event_created_by, $event_created, $event_modified_by, $event_last_modified ) . '</span>';

		$delete_url  = $url . 'delete/';
		$delete_url  = wp_nonce_url( $delete_url, 'event-delete-link' );
		$delete_link = '<span><a id="event-delete-link" href="' . $delete_url . '" title="' . __( 'Delete Event', 'groupcalendar' ) . '">' . __( 'Delete', 'groupcalendar' ) . ' &rarr;</a></span>';

		/**** a21 ******/
		$event_image = $wpdb->get_var( "SELECT meta_value FROM {$wpdb->prefix}bp_groups_groupmeta WHERE group_id='{$event_id}' AND meta_key='a21_bgc_event_image'");
		 /**** a21 ******/ 


	} else { //load POST variables

		if ( ! isset( $_POST['create-event'] ) ) {
			return false;
		}

		$event_title = esc_attr( strip_tags( trim( stripslashes( $_POST['event-title'] ) ) ) );
		$tmp_date    = $_POST['event-date'] . ' ' . $_POST['event-hour'] . ':' . $_POST['event-minute'] . $_POST['event-ampm'];
		$tmp_date    = strtotime( $tmp_date );

		//check for valid date/time
		if ( $tmp_date && $tmp_date >= current_time( 'timestamp' ) ) {
			$event_date = date( 'Y-m-d', $tmp_date );

			if ( $bgc_locale['time_format'] == 12 ) {
				$event_hour = date( 'g', $tmp_date );
			} else {
				$event_hour = date( 'G', $tmp_date );
			}

			$event_minute = date( 'i', $tmp_date );
			$event_ampm   = date( 'a', $tmp_date );
		} else {
			$event_hour = 7;
		}

		$event_description = stripslashes( wp_filter_post_kses( $_POST['event-desc'] ) );
		$event_location    = esc_attr( strip_tags( trim( stripslashes( $_POST['event-loc'] ) ) ) );
		$event_map         = ( $_POST['event-map'] == 1 ) ? ' checked="checked"' : '';

	}

	?>
	<div class="bp-widget">
		<h4>
			<?php _e( 'Edit Event', 'groupcalendar' ); ?>
			<?php echo $delete_link; ?>
		</h4>

		<form action="<?php echo $url; ?>" name="add-event-form" id="add-event-form" class="standard-form" method="post"
		      enctype="multipart/form-data">
			<label for="event-title"><?php _e( 'Title', 'groupcalendar' ); ?> *</label>
			<input name="event-title" id="event-title" value="<?php echo $event_title; ?>" type="text">

			<label for="event-date"><?php _e( 'Date', 'groupcalendar' ); ?> *
				<input name="event-date" id="event-date" value="<?php echo $event_date; ?>" type="text"></label>

			<label for="event-time"><?php _e( 'Time', 'groupcalendar' ); ?> *
				<select name="event-hour" id="event-hour">
					<?php
					if ( $bgc_locale['time_format'] == 24 ) {
						$bgc_locale['time_format'] = 23;
					}
					for ( $i = 1; $i <= $bgc_locale['time_format']; $i ++ ) {
						$hour_check = ( $i == $event_hour ) ? ' selected="selected"' : '';
						echo '<option value="' . $i . '"' . $hour_check . '>' . $i . "</option>\n";
					}
					?>
				</select>
				<select name="event-minute" id="event-minute">
					<option value="00"<?php echo ( $event_minute == '00' ) ? ' selected="selected"' : ''; ?>>:00
					</option>
					<option value="15"<?php echo ( $event_minute == '15' ) ? ' selected="selected"' : ''; ?>>:15
					</option>
					<option value="30"<?php echo ( $event_minute == '30' ) ? ' selected="selected"' : ''; ?>>:30
					</option>
					<option value="45"<?php echo ( $event_minute == '45' ) ? ' selected="selected"' : ''; ?>>:45
					</option>
				</select>
				<?php if ( $bgc_locale['time_format'] == 12 ) : ?>
					<select name="event-ampm" id="event-ampm">
						<option value="am"<?php echo ( $event_ampm == 'am' ) ? ' selected="selected"' : ''; ?>>am
						</option>
						<option value="pm"<?php echo ( $event_ampm == 'pm' ) ? ' selected="selected"' : ''; ?>>pm
						</option>
					</select>
				<?php endif; ?>
			</label>

			<label for="event-desc"><?php _e( 'Description', 'groupcalendar' ); ?></label>
			<?php if ( function_exists( 'wp_editor' ) ) {
				wp_editor( $event_description, 'event-desc', array( 'media_buttons' => false, 'dfw' => false ) );
			} else {
				the_editor( $event_description, 'event-desc', 'event-desc', false );
			}
			?>

			<label for="event-loc"><?php _e( 'Location', 'groupcalendar' ); ?></label>
			<input name="event-loc" id="event-loc" value="<?php echo $event_location; ?>" type="text">

			<?php
			/**** a21 ******/
			if($event_image) {?> <img src="<?php echo $event_image;?>" alt=""><?php }
			 /**** a21 ******/ 
			 ?>
			<label for="a21_image_upload"><?php _e( 'Event image', 'groupcalendar' ); ?> </label>
			<input type="file" name="a21_image_upload" id="a21_image_upload" />


<!--
<label for="event-map"><?php _e( 'Show Map Link?', 'groupcalendar' ); ?>
				<input name="event-map" id="event-map" value="1" type="checkbox"<?php echo $event_map; ?> />
				<small><?php _e( '(Note: Location must be an address)', 'groupcalendar' ); ?></small>
			</label>
-->

			<label for="total-volunteers"><?php _e( 'Total Event Volunteers Needed', 'groupcalendar' ); ?></label>
			<input name="total-volunteers" id="total-volunteers" value="<?php echo $event->total_vols;?>" type="text">

			<?php
			/* **** as21  for EDIT EVENT **** */

			$event_tasks = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bp_groups_bgc_tasks WHERE event_id='{$event_id}'");
			$event_times = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bp_groups_bgc_time WHERE event_id='{$event_id}'");

			/* **** as21 parse ids_vols & count vols**** */

			// echo "as21 parse ids_vols & count vols<hr>";

			// alex_debug(0,1,"orig arr tasks",$event_tasks);
			// alex_debug(0,1,"",$event_times);

			foreach ($event_tasks as $k => $task ) {
				$cnt_vols = explode(",",$task->cnt_vols);
				$event_tasks[$k]->cnt_vols = $cnt_vols;

				// $ids_vols = explode(":",$task->ids_vols);
				// $event_tasks[$k]->ids_vols = $ids_vols;
				// echo "==== inside loop as21 parse ids_vols & count vols ====<br>";
				// print_r($task);

				if( !empty($task->ids_vols) ) {

					$ids_vols = explode(":",$task->ids_vols);
					$event_tasks[$k]->ids_vols = $ids_vols;
					// print_r($cnt_vols);
					// alex_debug(0,1,"ids_vols=",$ids_vols);
					foreach ($ids_vols as $k2 => $v) {
						// if(!$v) break;  // comment today (когда 1 ряд и много колонок ids_vols не парсятся)
						// echo "v==".$v;
						$ids_arr = explode(",",$v);
						$event_tasks[$k]->ids_vols[$k2] = $ids_arr;
						// print_r($event_tasks[$k]->cnt_vols);
						// $event_tasks[$k]->ids_cnt[$k2] = $ids_arr;
						// $event_tasks[$k]->ids_cnt[$k2]['cnt'] = $event_tasks[$k]->cnt_vols[$k2];
						// $event_tasks[$k]->ids_cnt[$k2]['cnt'] = $event_tasks[$k]->cnt_vols[$k2];
						// echo "<br>";
						// echo "====k2-".$k2." v-".$v."<br>";
					}

				}else { unset($task->ids_vols);}

			}
			// alex_debug(0,1,"arr tasks after parse",$event_tasks);

			/* **** as21 parse ids_vols & count vols**** */

			?>
			<table id="a21_bgc_tasks_shifts" data-event-id="<?php echo $event_id;?>" style="margin-bottom: 5px;">

				<tr class="title_columns">
					<th class="a21_dinam_th_coll"> HELP TASK </th>
					<?php foreach ($event_times as $k => $v):?>
						<th class="a21_dinam_th_coll"> 
							  <input type="text" name="new_event_tasks[time][<?php echo $v->id;?>]" value="<?php echo $v->time;?>"/>
 							 <!-- <input type="text" name="new_event_tasks[time][<?php echo $k;?>]" value="<?php echo $v->time;?>"/> -->
						 </th>
					<?php endforeach;?>
				</tr>

				<?php foreach ($event_tasks as $k => $task):?>

					<tr class="a21_dinam_row" data-task_id="<?php echo $task->id;?>">
						<td class="a21_dinam_coll"> 								
						 <!-- <input type="text" name="new_event_tasks[<?php echo $k;?>][task]" value="<?php echo $task->task_title;?>"/> -->
						 <input type="text" name="new_event_tasks[<?php echo $task->id; ?>][task]" value="<?php echo $task->task_title;?>"/>
					   </td>

						<?php foreach ($task->cnt_vols as $k2 => $cnt):?>
							<?php
							?>
							<td class="a21_dinam_coll vol_cnt">
								<!-- <input type="text" name="new_event_tasks[<?php echo $k;?>][<?php echo $k2;?>]" value="<?php echo $cnt;?>" /></td> -->
								<input type="text" name="new_event_tasks[<?php echo $task->id;?>][<?php echo $k2;?>]" value="<?php echo $cnt;?>" /></td>
						<?php endforeach;?>
					</tr>
				<?php endforeach;?>
			</table>
			<?php if( empty($event_tasks)):?>
			<div class="wrap_btns_for_event_tasks">
				<div class="d_btn_left">
					<div id="a21_bgc_add_new_row">+ Add New Row</div>
					<div id="a21_bgc_add_new_column" >+ Add New Column</div>
				</div>
				<div class="d_btn_right">
					<div id="a21_bgc_del_row">[x] Delete Row</div>
					<div id="a21_bgc_del_column">[x] Delete Column</div>
				</div>
			</div>
			<?php endif;?>


			<?php /* **** as21  for EDIT EVENT **** */ ?>


			<label for="thank_you"><?php _e( 'Event Completion Thank-you Message', 'groupcalendar' ); ?></label>
			<!-- <input name="thank_you" id="thank_you" value="" type="text"> -->
			<textarea name="thank_you" id="thank_you" cols="30" rows="10"><?php echo stripslashes($event->thank_you);?></textarea>
			
			<p class="a21-system-box">Additional custom fields is still under development. Coming soon. Now you can only add or edit the image</p>

			<?php if ( $event_id ) : ?>
				<input name="event-id" id="event-id" value="<?php echo $event_id; ?>" type="hidden">
			<?php endif; ?>

			<input name="create-event" id="create-event" value="1" type="hidden">
			<input name="group-id" id="group-id" value="<?php echo $bp->groups->current_group->id; ?>" type="hidden">
			<?php wp_nonce_field( 'bp_group_calendar' ); ?>

			<?php echo $event_meta; ?>

			<p><input value="<?php _e( 'Save Event', 'groupcalendar' ); ?> &raquo;" id="save" name="save" type="submit">
			</p>

		</form>

	</div>
	<?php

	//return true if all is well
	return true;
}


class BP_Group_Calendar_Widget extends WP_Widget {

	function BP_Group_Calendar_Widget() {
		$widget_ops = array(
			'classname'   => 'bp_group_calendar',
			'description' => __( 'Displays upcoming public group events.', 'groupcalendar' )
		);
		parent::__construct( 'bp_group_calendar', __( 'Group Events', 'groupcalendar' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		global $wpdb, $current_user, $bp;

		extract( $args );

		echo $before_widget;
		$title = $instance['title'];
		if ( ! empty( $title ) ) {
			echo $before_title . apply_filters( 'widget_title', $title ) . $after_title;
		};

		$event_date = gmdate( 'Y-m-d H:i:s', ( strtotime( current_time( 'mysql' ) ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) );
		$events     = $wpdb->get_results( $wpdb->prepare( "SELECT gc.id, gc.user_id, gc.event_title, gc.event_time, gp.name, gc.group_id FROM " . $wpdb->base_prefix . "bp_groups_calendars gc JOIN " . $wpdb->base_prefix . "bp_groups gp ON gc.group_id=gp.id WHERE gc.event_time >= %s AND gp.status = 'public' ORDER BY gc.event_time ASC LIMIT %d", $event_date, (int) $instance['num_events'] ) );

		if ( $events ) {

			echo '<ul class="events-list">';
			//loop through events
			$events_list = '';
			foreach ( $events as $event ) {
				$class = ( $event->user_id == $current_user->ID ) ? ' class="my_event"' : '';
				$events_list .= "\n<li" . $class . ">";
				$group = groups_get_group( array( 'group_id' => $event->group_id ) );
				$url   = bp_get_group_permalink( $group ) . 'callout/' . $event->id . '/';
				$events_list .= stripslashes( $event->name ) . '<br /><a href="' . $url . '" title="' . __( 'View Event', 'groupcalendar' ) . '">' . bgc_date_display( $event->event_time ) . ': ' . stripslashes( $event->event_title ) . '</a>';
				$events_list .= "</li>";
			}
			echo $events_list;
			echo "\n</ul>";

		} else {
			?>
			<div class="widget-error">
				<?php _e( 'There are no upcoming group events.', 'groupcalendar' ) ?>
			</div>
		<?php } ?>

		<?php echo $after_widget; ?>
		<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance               = $old_instance;
		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['num_events'] = strip_tags( $new_instance['num_events'] );

		return $instance;
	}

	function form( $instance ) {
		$instance   = wp_parse_args( (array) $instance, array(
			'title'      => __( 'Upcoming Group Events', 'groupcalendar' ),
			'num_events' => 10
		) );
		$title      = strip_tags( $instance['title'] );
		$num_events = strip_tags( $instance['num_events'] );
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'groupcalendar' ) ?> <input
					class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
					name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
					value="<?php echo esc_attr( $title ); ?>"/></label></p>
		<p><label
				for="<?php echo $this->get_field_id( 'num_events' ); ?>"><?php _e( 'Number of Events:', 'groupcalendar' ) ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'num_events' ); ?>"
				       name="<?php echo $this->get_field_name( 'num_events' ); ?>" type="text"
				       value="<?php echo esc_attr( $num_events ); ?>" style="width: 30%"/></label></p>
		<?php
	}
}

class BP_Group_Calendar_Widget_Single extends WP_Widget {

	function BP_Group_Calendar_Widget_Single() {
		$widget_ops = array(
			'classname'   => 'bp_group_calendar_single',
			'description' => __( 'Displays upcoming group events for a single group.', 'groupcalendar' )
		);
		parent::__construct( 'bp_group_calendar_single', __( 'Single Group Events', 'groupcalendar' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		global $wpdb, $current_user, $bp;

		extract( $args );

		echo $before_widget;
		$title = $instance['title'];
		if ( ! empty( $title ) ) {
			echo $before_title . apply_filters( 'widget_title', $title ) . $after_title;
		};

		$event_date = gmdate( 'Y-m-d H:i:s', ( strtotime( current_time( 'mysql' ) ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) );
		$events     = $wpdb->get_results( $wpdb->prepare( "SELECT gc.id, gc.user_id, gc.event_title, gc.event_time, gp.name, gc.group_id FROM " . $wpdb->base_prefix . "bp_groups_calendars gc JOIN " . $wpdb->base_prefix . "bp_groups gp ON gc.group_id=gp.id WHERE gc.event_time >= %s AND gp.id = %d ORDER BY gc.event_time ASC LIMIT %d", $event_date, (int) $instance['group_id'], (int) $instance['num_events'] ) );	 		 	    			 				 

		if ( $events ) {

			echo '<ul class="events-list">';
			//loop through events
			$events_list = '';
			foreach ( $events as $event ) {
				$class = ( $event->user_id == $current_user->ID ) ? ' class="my_event"' : '';
				$events_list .= "\n<li" . $class . ">";
				$group = groups_get_group( array( 'group_id' => $event->group_id ) );
				$url   = bp_get_group_permalink( $group ) . 'callout/' . $event->id . '/';
				$events_list .= stripslashes( $event->name ) . '<br /><a href="' . $url . '" title="' . __( 'View Event', 'groupcalendar' ) . '">' . bgc_date_display( $event->event_time ) . ': ' . stripslashes( $event->event_title ) . '</a>';
				$events_list .= "</li>";
			}
			echo $events_list;
			echo "\n</ul>";

		} else {
			?>
			<div class="widget-error">
				<?php _e( 'There are no upcoming event callouts for this group.', 'groupcalendar' ) ?>
			</div>
		<?php } ?>

		<?php echo $after_widget; ?>
		<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance               = $old_instance;
		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['num_events'] = strip_tags( $new_instance['num_events'] );
		$instance['group_id']   = (int) $new_instance['group_id'];

		return $instance;
	}

	function form( $instance ) {
		global $wpdb;
		$instance   = wp_parse_args( (array) $instance, array(
			'title'      => __( 'Upcoming Group Events', 'groupcalendar' ),
			'num_events' => 10
		) );
		$title      = strip_tags( $instance['title'] );
		$num_events = strip_tags( $instance['num_events'] );
		$group_id   = $instance['group_id'];
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'groupcalendar' ) ?> <input
					class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
					name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
					value="<?php echo esc_attr( $title ); ?>"/></label></p>
		<p><label
				for="<?php echo $this->get_field_id( 'num_events' ); ?>"><?php _e( 'Number of Events:', 'groupcalendar' ) ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'num_events' ); ?>"
				       name="<?php echo $this->get_field_name( 'num_events' ); ?>" type="text"
				       value="<?php echo esc_attr( $num_events ); ?>" style="width: 30%"/></label></p>

		<?php
		$groups = $wpdb->get_results( "SELECT id, name FROM {$wpdb->base_prefix}bp_groups ORDER BY name LIMIT 999" ); //we don't want thousands of groups in the dropdown.
		if ( $groups ) {
			echo '<p><label for="' . $this->get_field_id( 'group_id' ) . '">' . __( 'Group:', 'groupcalendar' ) . ' <select class="widefat" id="' . $this->get_field_id( 'group_id' ) . '" name="' . $this->get_field_name( 'group_id' ) . '">';

			foreach ( $groups as $group ) {
				echo '<option value="' . $group->id . '"' . ( ( $group_id == $group->id ) ? ' selected="selected"' : '' ) . '>' . esc_attr( $group->name ) . '</option>';
			}

			echo '</select></label></p>';
		}
	}
}

class BP_Group_Calendar_Widget_User_Groups extends WP_Widget {

	function BP_Group_Calendar_Widget_User_Groups() {
		$widget_ops = array(
			'classname'   => 'bp_group_calendar_user_groups',
			'description' => __( 'Displays upcoming group event callouts for a logged in user\'s groups.', 'groupcalendar' )
		);
		parent::__construct( 'bp_group_calendar_user_groups', __( 'User\'s Group Events', 'groupcalendar' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		global $wpdb, $current_user, $bp;

		//only show widget to logged in users
		if ( ! is_user_logged_in() ) {
			return;
		}

		//get the groups the user is part of
		$results = groups_get_user_groups( $current_user->ID );

		//don't show widget if user doesn't have any groups
		if ( $results['total'] == 0 ) {
			return;
		}

		extract( $args );

		echo $before_widget;
		$title = $instance['title'];
		if ( ! empty( $title ) ) {
			echo $before_title . apply_filters( 'widget_title', $title ) . $after_title;
		};

		$group_ids  = implode( ',', $results['groups'] );
		$event_date = gmdate( 'Y-m-d H:i:s', ( strtotime( current_time( 'mysql' ) ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) );
		$events     = $wpdb->get_results( $wpdb->prepare( "SELECT gc.id, gc.user_id, gc.event_title, gc.event_time, gp.name, gc.group_id FROM " . $wpdb->base_prefix . "bp_groups_calendars gc JOIN " . $wpdb->base_prefix . "bp_groups gp ON gc.group_id=gp.id WHERE gc.event_time >= %s AND gp.id IN ($group_ids) ORDER BY gc.event_time ASC LIMIT %d", $event_date, (int) $instance['num_events'] ) );

		if ( $events ) {
			echo '<ul class="events-list">';
			//loop through events
			$events_list = '';
			foreach ( $events as $event ) {
				$class = ( $event->user_id == $current_user->ID ) ? ' class="my_event"' : '';
				$events_list .= "\n<li" . $class . ">";
				$group = groups_get_group( array( 'group_id' => $event->group_id ) );
				$url   = bp_get_group_permalink( $group ) . 'callout/' . $event->id . '/';
				$events_list .= stripslashes( $event->name ) . '<br /><a href="' . $url . '" title="' . __( 'View Event', 'groupcalendar' ) . '">' . bgc_date_display( $event->event_time ) . ': ' . stripslashes( $event->event_title ) . '</a>';
				$events_list .= "</li>";
			}
			echo $events_list;
			echo "\n</ul>";

		} else {
			?>
			<div class="widget-error">
				<?php _e( 'There are no upcoming event callouts for your groups.', 'groupcalendar' ) ?>
			</div>
		<?php } ?>

		<?php echo $after_widget; ?>
		<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance               = $old_instance;
		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['num_events'] = strip_tags( $new_instance['num_events'] );

		return $instance;
	}

	function form( $instance ) {
		$instance   = wp_parse_args( (array) $instance, array(
			'title'      => __( 'Your Upcoming Group Events', 'groupcalendar' ),
			'num_events' => 10
		) );
		$title      = strip_tags( $instance['title'] );
		$num_events = strip_tags( $instance['num_events'] );
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'groupcalendar' ) ?> <input
					class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
					name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
					value="<?php echo esc_attr( $title ); ?>"/></label></p>
		<p><label
				for="<?php echo $this->get_field_id( 'num_events' ); ?>"><?php _e( 'Number of Events:', 'groupcalendar' ) ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'num_events' ); ?>"
				       name="<?php echo $this->get_field_name( 'num_events' ); ?>" type="text"
				       value="<?php echo esc_attr( $num_events ); ?>" style="width: 30%"/></label></p>
		<?php
	}
}