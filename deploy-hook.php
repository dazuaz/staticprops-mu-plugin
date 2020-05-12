<?php

/**
*
* Trigger Build
*
* @since 1.1.2
**/
function staticprops_fire_n_webhook(){
  $webhook_url = get_option('staticprops_webhook_url');
  // $webhook_url = get_option('webhook_address');
  if($webhook_url){
    $options = array(
      'method'  => 'POST',
    );
    return wp_remote_post($webhook_url, $options);
  }
  return false;
}

/**
* Notify Admin on Successful Update
*
* @since 1.0.0
**/
function admin_notice() { ?>
  <div class="notice notice-success is-dismissible">
      <p><?php _e('Your settings have been updated!', 'webhook-netlify-deploy');?></p>
  </div>
<?php }

