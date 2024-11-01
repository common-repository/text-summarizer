<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

$plan_limit = get_option('TopTechNewsTextSumm_plan_limit');
$remaining_limit = get_option('TopTechNewsTextSumm_remaining_limit') == 0 ? $plan_limit : get_option('TopTechNewsTextSumm_remaining_limit');
?>
<div class="page-wrapper">
  <div class="header">
    <h1 class="h1sum animate__animated animate__fadeIn"><?php echo esc_html__('Summarize Your Text in a Click!', 'summarize-content'); ?></h1>
  </div>
  <div class="summary-info">
    <div class="info-box">
        <i class="fa fa-th" aria-hidden="true"></i>
        <span class="info-label"><?php echo esc_html__('Plan Limit:', 'summarize-content'); ?></span>
        <span id="plan-limit" class="info-value"><?php echo esc_html(number_format($plan_limit)); ?></span>
        <span class="tooltip"><?php echo esc_html__('Total tokens allocated for the current plan.', 'summarize-content'); ?></span>
    </div>
    <div class="info-box">
        <i class="fa fa-clock-o" aria-hidden="true"></i>
        <span class="info-label"><?php echo esc_html__('Tokens Used:', 'summarize-content'); ?></span>
        <span class="tokens_used info-value">0</span>
        <span class="tooltip"><?php echo esc_html__('Total tokens used in the current request.', 'summarize-content'); ?></span>
    </div>
    <div class="info-box">
        <i class="fa fa-tachometer" aria-hidden="true"></i>
        <span class="info-label"><?php echo esc_html__('Remaining Limit:', 'summarize-content'); ?></span>
        <span id="remaining-limit" class="info-value"><?php echo esc_html(number_format($remaining_limit)); ?></span>
        <span class="tooltip"><?php echo esc_html__('Remaining tokens available for use.', 'summarize-content'); ?></span>
    </div>
    <div class="info-box">
        <i class="fa fa-money"></i>
        <a href="<?php echo esc_url('https://textsummarizer.io/'); ?>" target="_blank" class="info-label"><?php echo esc_html__('Add Tokens', 'summarize-content'); ?></a>
        <span class="tooltip"><?php echo esc_html__('Click to Login into backend and Add Tokens', 'summarize-content'); ?></span>
    </div>
  </div>

  <form name="summarizeContent" id="summarizeContent" method="POST">
    <input type="hidden" id="remaining-limit-hdn" name="remaining-limit" value="<?php echo esc_attr($remaining_limit); ?>">
    <input type="hidden" id="plan-limit-hdn" name="plan-limit" value="<?php echo esc_attr($plan_limit); ?>">
    <?php wp_nonce_field('summarize_content_nc', 'summarize_content_nonce'); ?>
    <div class="form-group slider-container">
        <div class="slider-label">
            <label for="sum_length"><?php echo esc_html__('Summary Length (in words):', 'summarize-content'); ?></label>
            <span id="slider-value">50</span>
        </div>
        <input type="range" min="50" max="200" value="50" class="slider" id="sum_length">
        <span class="tooltipSum"><?php echo esc_html__('Move the slider to adjust the length of the summary', 'summarize-content'); ?></span>
    </div>
    <div class="form-group dropdownSum">
        <label for="summary-quality" class="qlty-lbl"><?php esc_html_e('Summary Quality', 'summarize-content'); ?></label>
        <select class="dropbtn" name="sum_quality" id="sum_quality">
          <option value="creative"><?php esc_html_e('Creative', 'summarize-content'); ?></option>
          <option value="balanced"><?php esc_html_e('Balanced', 'summarize-content'); ?></option>
          <option value="precise"><?php esc_html_e('Precise', 'summarize-content'); ?></option>
        </select>
        <span class="tooltipSum"><?php esc_html_e('Select the desired quality for the summary', 'summarize-content'); ?></span>
    </div>
    <div class="textarea-container">
        <textarea name="textarea" id="textarea" rows="20" cols="80" placeholder="<?php esc_attr_e('Please Enter or Paste your Text here..', 'summarize-content'); ?>"></textarea>
        <input type="hidden" id="token-count" name="token-count" value="0">
    </div>
    <a href="javascript:void(0)" id="copyText" style="display:none;" title="<?php esc_attr_e('Copy To Clipboard', 'summarize-content'); ?>" data-clipboard-target="#textarea"><?php esc_html_e('Copy text', 'summarize-content'); ?></a>
    <a href="javascript:void(0)" id="refresh-link" style="display:none;" title="<?php esc_attr_e('Reload Page to try again.', 'summarize-content'); ?>"><?php esc_html_e('Refresh Page', 'summarize-content'); ?></a>
    <br>
    <span class="success"></span>
    <span class="error"></span>
    <button id="sbmtText"><?php esc_html_e('Summarize', 'summarize-content'); ?></button>
  </form>
  <div id="cover-spin"></div>
  <div class="social-sidebar">
      <a target="_blank" href="<?php echo esc_url('https://www.facebook.com/toptechnews.media'); ?>" title="<?php esc_attr_e('Follow TopTech News', 'summarize-content'); ?>" class="social-icon"><i class="fa fa-facebook"></i></a>
      <a target="_blank" href="<?php echo esc_url('https://twitter.com/_toptechnews'); ?>" class="social-icon" title="<?php esc_attr_e('Follow TopTech News', 'summarize-content'); ?>"><i class="fa fa-twitter"></i></a>
      <a target="_blank" href="<?php echo esc_url('https://www.youtube.com/channel/UCjQp4KRNrEyajeTcnDw4DSQ'); ?>" class="social-icon" title="<?php esc_attr_e('Follow TopTech News', 'summarize-content'); ?>"><i class="fa fa-youtube"></i></a>
  </div>
  <div id="success-msg"><?php esc_html_e('Content Summarized! Please click the button below to copy text.', 'summarize-content'); ?></div>
</div>
