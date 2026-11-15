<?php
/**
 * Uninstall Amino LLMS settings without affecting any physical llms.txt file.
 */
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}
delete_option('amino_llms_settings');
delete_option('amino_llms_previous_settings');
delete_transient('amino_llms_document_cache');
