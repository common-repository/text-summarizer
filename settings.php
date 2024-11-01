<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap-sum">
  <div class="logo-container">
      <img src="<?php echo esc_url(plugin_dir_url(__FILE__).'icon/Top-Tech-News-Logo.png'); ?>" alt="<?php esc_attr_e('Logo', 'summarize-content'); ?>" />
  </div>
  <h1 class="h1-sum"><?php esc_html_e('Text Summarizer Settings', 'summarize-content'); ?></h1>
  <form name="ttnTStokenForm" id="ttnTStokenForm" method="post" action="options.php">
    <?php settings_fields('summarize_content_options'); ?>
    <div class="form-group">
      <label for="api_token_ttn"><?php esc_html_e('Enter Your API Key', 'summarize-content'); ?></label>
      <input type="password" id="api_token_ttn" name="api_token" value="<?php echo esc_attr(get_option('TopTechNewsTextSumm_api_token')); ?>" class="regular-text" required />
    </div>
    <div class="form-group">
      <?php submit_button(); ?>
    </div>
    <p><?php echo sprintf(esc_html__('To get your API Token, Please %sClick Here.%s', 'summarize-content'), '<a href="' . esc_url('https://textsummarizer.io/') . '" target="_blank">', '</a>'); ?></p>
  </form>
  <div id="message"></div>
</div>
