<?php
/**
 * Uninstall LLMs.txt Composer settings without affecting any physical llms.txt file.
 */
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}
delete_option('llms_txt_composer_settings');
delete_option('llms_txt_composer_previous_settings');
delete_transient('llms_txt_composer_document_cache');
