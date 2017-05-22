<?php

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
			<h6 class="event-label"><?php _e( 'Date/Time:', 'groupcalendar' ); ?></h6><?php echo $event_time; ?>
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
		$event_full_link = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		?>
		<div class="share"
			<h4>Helping recruit Volunteers? If so, please share link:</h4>
			<ul>
			<li><a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $event_full_link;?>" title="Share this post on Facebook" target="_blank" class="facebook">Facebook</a></li>
			<li><a href="https://plus.google.com/share?url=<?php echo $event_full_link;?>" title="Share this on Google+" target="_blank" class="googleplus">Google+</a></li>
			<li><a href="http://twitter.com/share?url=<?php echo $event_full_link;?>&text=<?php echo stripslashes( $event->event_title ); ?>&via=dugoodr" title="Share this post on Twitter" target="_blank" class="twitter">Twitter</a></li>
			<!-- https://www.linkedin.com/shareArticle?mini=true&url=http%3A//dugoodr2.dev/causes/ottawa-mission/callout/new-event-3x3-19-april--ottawa-mission-s4/&title=dfsdfdf&summary=&source= -->
			<li><a href="http://www.linkedin.com/shareArticle?mini=true&url=<?php echo $event_full_link;?>&title=<?php echo stripslashes( $event->event_title ); ?>" title="Share this post on LinkedIn" target="_blank" class="linkedin">LinkedIn</a></li>
			<!--<li><a href="http://www.pinterest.com/pin/create/button/?url=<?php if($event_image) echo $event_image;?>&media=<?php if($event_image) echo $event_image;?>&description=<?php echo stripslashes( $event->event_title ); ?>" target="_blank" class="pinterest" data-pin-do='buttonPin' data-pin-config='above'>Pinterest</a></li>-->
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

		     <?php if( !empty($event->total_vols) ) { ?> <h6 class="event-label">Total DuGoodrs Needed:</h6> <?php echo $event->total_vols; } ?>
		    <!-- <h6 class="event-label">Thank-you Message:</h6>  -->
		    <!-- <p class='a21-system-message'>for testing</p> -->
		    <?php //if( !empty($event->thank_you) ) echo stripslashes($event->thank_you); ?>
			<!--<h6 class="event-label">Event Tasks & Shifts:</h6><p class="a21-system-box">*NOTE* Event Callout/Shifts in Test mode, still under development</p>-->
			<p>

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



			<?php /* **** as21 for to print content of event details **** */ ?>

			<span class="event-map" id="show-signup-report">
			<!-- <a href="#"title="Get Signup Report"> Get Signup Report &raquo;</a> -->
			<a href="/" title="Get Signup Report"> Get Signup Report &raquo;</a>
			<!-- <button> Get Signup Report &raquo;</button> -->
			</span>
			<div id="show-signup-report-modal" class="white-popup mfp-hide">
					<h5 class="events-title"><?php echo stripslashes( $event->event_title ); ?></h5>
					<div class="print"><span>PRINT PAGE</span> <img src="<?php echo get_stylesheet_directory_uri();?>/images/print.png" alt=""></div>
					<div class="clearfix"></div>
					<!-- <h6 class="event-label"><?php _e( 'Date/Time:', 'groupcalendar' ); ?></h6><?php echo $event_time; ?> -->
					<span class="print_row"><?php _e( 'Date/Time: ', 'groupcalendar' ); ?> <?php echo $event_time; ?></span>
					 <?php if( !empty($event->total_vols) ) { ?> 
					 <!-- <h6 class="event-label">Total Volunteers Requested:</h6>  -->
					 <span class="print_row">Total Volunteers Requested: <?php echo $event->total_vols."</span>"; } ?>
					<!-- <h6 class="event-label">Url Link to event:</h6>	<a href="<?php echo $event_full_link; ?>"><?php echo $event_full_link; ?></a> -->
					<!-- <h6 class="event-label">Url Link to event:</h6>	<a href="<?php echo $event_full_link; ?>"><?php echo $event_full_link; ?></a> -->
					<span class="print_row">Url Link to event: <a href="<?php echo $event_full_link; ?>"><?php echo $event_full_link; ?></a></span>

			<?php if ( $event->event_description ) : ?>
				<h6 class="event-label"><?php _e( 'Description:', 'groupcalendar' ); ?></h6>
				<div class="event-description">
					<?php echo stripslashes( $event->event_description ); ?>
				</div>
			<?php endif; ?>

		<?php if ( $event->event_location ) : ?>
			<!-- <h6 class="event-label"><?php _e( 'Location:', 'groupcalendar' ); ?></h6> -->
			<?php _e( 'Location: ', 'groupcalendar' ); ?>
			<!-- <div class="event-location">	<?php echo stripslashes( $event->event_location ); ?></div> -->
			<?php echo stripslashes( $event->event_location ); ?>
		<?php endif; ?>

				<?php if( !empty( $event_tasks[0]->task_title) ): // show tasks table if it isn't empty ?>
				<table id="a21_bgc_tasks_shifts" class="for-printer" data-event-id="<?php echo $event_id;?>">
		        	<tr class="title_columns">
		        	   <th class="a21_dinam_th_coll"> Task item </th>
		        	<?php foreach ($event_times as $k => $v):?>
		        		<th class="a21_dinam_th_coll"> <?php echo $v->time;?> </th>
			        <?php endforeach;?>
			       </tr>

	        	<?php foreach ($event_tasks as $k => $task): if( !empty ($task->task_title) ): // show item task if cur task is not empty ?>

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
			        		 <td class="a21_dinam_coll">
			        		 <?php
			        		 // var_dump($arr_vols_cur_task);

			        		 // echo "k2=".$k2."<br>";
			        		 // echo " cnt ".$cnt." == task->ids_vols[k2] $k2, ".count($task->ids_vols[$k2]);
			        		 // echo "<br>";

							 if($cur_count >= $cnt) echo "<p>Awesome! this task is FULL</p>";
	 		        		 if( $still_need_count != 0 ) { echo "<p class='still_need'><span class='vol_cnt'>".$still_need_count."</span> "; echo $need."</p>"; }


							/* **** hide/show btn signup **** */

							 $vol_hide_btn = false;


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





			        	
			        		  // alex_debug(0,1,"array vols cur task",$arr_vols_cur_task);

							/* important for debug
			        		  echo "<p class='a21-system-message'>current count vols=".$cur_count."</p>";
			        		  if($cur_count >= $cnt) echo "<p class='a21-system-message'>full -</p>";
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

			        		  $member = '';
			        		  $i = 1;
			        		  foreach ($arr_vols_cur_task as $member_id) {
		  	        		      if(!empty($member_id) ) {

		  	        		      	if($i == 1) echo "<p class='attendees'>DuGoodrs: ";
									$member_link = bp_core_get_userlink($member_id,false,true);
									$member_avatar = bp_core_fetch_avatar( array('item_id'=>$member_id, 'width'=>"30", 'height'=>'30','html'=>true));

		  	        		      	$member .= "<a class='wrap_member link-user-id-".$member_id."' href='".$member_link."' >".$member_avatar."</a>";
		  	        		      	// $member_name .= "<p class='a21-system-message'>id ".$member_id."@".bp_core_get_username($member_id)."-".bp_core_get_user_displayname($member_id)."</p>";
		  	        		      	$member .= "<span class='member_info'>@".bp_core_get_username($member_id).", ".bp_core_get_user_displayname($member_id)."</span><br>";
									if($i == count($arr_vols_cur_task)) echo "</p>";
									$i++;
								 }
			        		  }
			        		  echo $member;
			        		  ?>
			        		 </td>
			      		 <?php endforeach;?>
	    	        </tr>
			        <?php endif; endforeach;?>
				</table>
				<?php endif;?>
			</div>

			<script>
			jQuery( document ).ready(function() {
		    jQuery('#show-signup-report').magnificPopup({
		        items: {
		            src: '#show-signup-report-modal',
		            type: 'inline',
		            focus: '.username'
		        },
		        preloader: false,
		        callbacks: {
				    open: function() {
				    	console.log("open---");
				    	// jQuery("#show-signup-report-modal").show();
				    	// var orig = jQuery("body").html();
				    	// console.log("open===="+orig);
				    },
				    close: function() {
				    	console.log("close---");
				    	// console.log("close===="+orig);
				    }
				}
		    });

		    /* **** as21 prints the contents of the current window**** */
		    jQuery(".print img").click(function (){

		    	var styles = "<style>@media print{.mfp-close,.print{display:none}.clearfix{clear:both}body{background:#000}table{border-collapse:collapse;width:100%}td,th{font-size:13px;border:1px solid #bdbdbd;padding:5px}#show-signup-report-modal .for-printer{margin-top:20px!important}span.print_row{padding-bottom:10px;display:block}#show-signup-report-modal{padding:0;background:#fff;width:100%;margin:0 auto}#show-signup-report-modal .events-title{margin:0 0 15px;padding:0;line-height:1;font-size:2em}#show-signup-report-modal .print{float:right;padding:0 30px 0 10px;width:22%;text-align:right}#show-signup-report-modal .print img{height:32px;cursor:pointer;vertical-align:bottom}#show-signup-report-modal .member_info{line-height:1.3;width:80%;float:left;display:block;padding-left:5px}#show-signup-report-modal .a21_dinam_coll .wrap_member{float:left;clear:left;width:15%;margin-bottom:5px}}</style>";
				// var printContents = document.getElementById("show-signup-report-modal").innerHTML;
				var printContents = jQuery("#show-signup-report-modal").html();
				printContents = styles+'<div id="show-signup-report-modal">'+printContents+'</div>';
				// console.log(printContents);
				// way 1
		    	w=window.open();
				w.document.write(printContents);
				w.print();
				w.close();

				// way2
				/*
				var originalContents = document.body.innerHTML;
				document.body.innerHTML = printContents;
		    	window.print();
				body.innerHTML = originalContents;
				*/
		    });
		    /* **** as21 prints the contents of the current window**** */

			// jQuery(document).on('click', '.mfp-wrap', function () {
			jQuery(document).on('click', '.mfp-close', function () {
				jQuery.magnificPopup.close();
		  		// console.log( jQuery(this).closest("body") );
				// console.log("mfp-close");
				// jQuery(this).closest("body").find(".mfp-bg").remove();
				// jQuery(".mfp-wrap").remove();
				// jQuery("html").css({"overflow":"auto","margin-right":"0"});
				// jQuery(".s-wrap").html(report);
			});
			});
			</script>
			
			<?php // echo alex_debug(0,1,"",$event_tasks); ?>
			<?php /* **** as21 for to print content of event details **** */ ?>

			<?php if( !empty( $event_tasks[0]->task_title) ): // show tasks table if it isn't empty ?>
			<table id="a21_bgc_tasks_shifts" data-event-id="<?php echo $event_id;?>">
	        	<tr class="title_columns">
	        	   <th class="a21_dinam_th_coll"> Task item </th>
	        	<?php foreach ($event_times as $k => $v):?>
	        		<th class="a21_dinam_th_coll"> <?php echo $v->time;?> </th>
		        <?php endforeach;?>
		       </tr>

	        	<?php foreach ($event_tasks as $k => $task): if( !empty ($task->task_title) ): // show item task if cur task is not empty ?>

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

						 if($cur_count >= $cnt) echo "<p>Awesome! this task is FULL</p>";
 		        		 if( $still_need_count != 0 ) { echo "<p class='still_need'><span class='vol_cnt'>".$still_need_count."</span> "; echo $need."</p>"; }


						/* **** hide/show btn signup **** */

						 $vol_hide_btn = false;
						 $show_cancel_my_attandance = false;
   				 		 $cancel_my_attandance_html = "<button class='a21_cancel_my_attandance' data-s-need-cnt='".$still_need_count."'' data-i='".$k2."' data-task-id='".$task->id."' data-user-id='".$cur_user->ID."'>cancel my volunteering</button>";


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
		        		  if($cur_count >= $cnt) echo "<p class='a21-system-message'>full -</p>";
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
	  	        		      	if($i == 1) echo "<p class='attendees'>DuGoodrs: ";
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
		        <?php  endif; endforeach;?>
			</table>
			<?php endif;?>

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


	<!--<p class="a21-system-box">Additional custom fields is still under development. Coming soon. Now you can only add or edit the image</p>-->

		<?php echo $event_meta; ?>

	</div>
	<?php
}