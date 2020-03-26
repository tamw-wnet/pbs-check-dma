<?php
/* this endpoint is just to return the current status of the feed */
show_admin_bar(false);
remove_all_actions('wp_footer',1);
remove_all_actions('wp_header',1);

$api = new PBS_Check_DMA();
$defaults = get_option($api->token);
$station_common_name = !empty($defaults['station_common_name']) ? $defaults['station_common_name'] : "";
$blackout_schedule = array();
if (!empty($_POST['postid'])) {
  $postmeta_data = get_post_meta($_POST['postid']);
  $blackouts_weekly = !empty($postmeta_data['dma_restricted_video_blackouts_weekly'][0]) ? stripslashes($postmeta_data['dma_restricted_video_blackouts_weekly'][0]) : '';
  $blackouts_dates = !empty($postmeta_data['dma_restricted_video_blackouts_dates'][0]) ? stripslashes($postmeta_data['dma_restricted_video_blackouts_dates'][0]) : '';
  $blackout_schedule["weekly"] = json_decode($blackouts_weekly, true);
  $blackout_schedule["dates"] = json_decode($blackouts_dates, true);
}
$tz = !empty(get_option('timezone_string')) ? get_option('timezone_string') : 'America/New York';
$date = new DateTime('now', new DateTimeZone($tz));

$dayname = $date->format('D');
$ymd = $date->format('Y-m-d');
$miltime = $date->format('Hi');

$blackout_status = array(
  "blackout_status" => false
);

foreach ($blackout_schedule as $type => $data) {
  foreach($data as $schedule => $times) {
    if ($type == 'weekly') {
      if (strtolower($schedule) != strtolower($dayname)) {
        continue;
      }
    } else {
      if ($schedule != $ymd) {
        continue;
      }
    }
    if (!empty($times)) {
      foreach ($times as $time => $pair) {
        if ($miltime > (int) $pair['start'] && $miltime < (int) $pair['end']) {
          $blackout_status["blackout_status"] = true;
          $hours = substr($pair['end'], 0, 2);
          $minutes = substr($pair['end'], 2, 2);
          $end_time_str = date( 'g:i A', strtotime( $hours . ":" . $minutes ) );
          $blackout_status["end"] = $end_time_str;
          break 2;
        }
      }
    }
  }
}

$return = $blackout_status;


// extra slash stripping in case we're using PHP 5.3
$json = stripslashes(json_encode($return));
header('content-type: application/json; charset=utf-8');
exit($json);

