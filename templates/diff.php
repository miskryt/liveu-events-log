<?php

use LiveuEventsLog\Helpers\DiffParser;

$page_title = $data['title'];
unset ($data['title']);
$event = $data['event'];
$diff_data = $data['diff_data'];
//var_dump(substr("prev#post_title", strpos("prev#post_title", "#")+1));

?>

<br/>
<br/>

Post type: <?=$event['post_type']?>
<br/>
User: <?=$event['user']?>
<br/>
Date: <?=$event['date']?>
<br/>
<br/>

<?php if(isset($event['post_id'])):?>
<a target="_blank" href="<?=get_edit_post_link($event['post_id'])?>">
	<?=get_post($event['post_id'])->post_title?>
</a>
<?php endif;?>

<br/>
<br/>

<?php
echo( $diff_data);
?>


