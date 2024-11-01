<?php 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
    <?php if (!empty($this->summaryHistoryData)): ?>
    <h1 class="history-heading"><?php esc_html_e('Text Summarizer History', 'summarize-content'); ?></h1>
    <div class="alert-summary-history">
        <h4 class="alert-heading"><?php esc_html_e('Your Most Recent Summarizing Activity', 'summarize-content'); ?></h4>
        <p><?php echo sprintf( wp_kses( __( 'This page displays the 10 most recent summary records from your Text Summarizer usage. If you require access to older records or need any additional information, please don\'t hesitate to <a href="mailto:%s">contact us.</a>', 'summarize-content' ), array(  'a' => array( 'href' => array() ) ) ), esc_attr('admin@toptech.news') ); ?></p>
    </div>
    <table class="wp-list-table widefat fixed striped table-summary-history">
        <thead>
            <tr>
                <th class="cell-small"><?php esc_html_e('S.No', 'summarize-content'); ?></th>
                <th><?php esc_html_e('Content', 'summarize-content'); ?></th>
                <th><?php esc_html_e('Summary', 'summarize-content'); ?></th>
                <th class="cell-small"><?php esc_html_e('Tokens Used', 'summarize-content'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
                $count = 1;
                foreach ($this->summaryHistoryData as $row): 
            ?>
                <tr>
                    <td class="cell-small"><?php echo esc_html($count++); ?></td>
                    <td class="cell-content"><div class="content-text"><?php echo esc_html($row['content']); ?></div></td>
                    <td class="cell-summary"><div class="summary-text"><?php echo esc_html($row['summary']); ?></div></td>
                    <td class="cell-small"><?php echo esc_html($row['tokens_used']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p><?php esc_html_e('No history data found.', 'summarize-content'); ?></p>
    <?php endif; ?>
</div>
