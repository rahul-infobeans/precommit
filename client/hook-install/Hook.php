<?php

namespace CustomHook\PreCommitHook;

use Composer\Script\Event;

class Hook
{
    private static $rule_set_dir = '$PROJECT/precommit/hook-install/';

    public function __construct() {

    }

    public static function run(Event $event) {
        // Get Remote URL
        $remote_host       = $event->getComposer()->getConfig()->get('rule_set_info')['remote_url'] ?? '';
        // Get PHP CS ruleset
        $phpcs_rule_set    = $event->getComposer()->getConfig()->get('rule_set_info')['phpcs_rule_set'] ?? '';
        // Get PHP MD ruleset
        $phpmd_rule_set    = $event->getComposer()->getConfig()->get('rule_set_info')['phpmd_rule_set'] ?? '';
        // Get PHP MD exclude directory
        $phpmd_exclude_dir = $event->getComposer()->getConfig()->get('rule_set_info')['phpmd_exclude_dir'] ?? '';
        // Get PHP CS exclude directory
        $phpcs_exclude_dir = $event->getComposer()->getConfig()->get('rule_set_info')['phpcs_exclude_dir'] ?? '';
        // Get build version
        $build_ver = $event->getComposer()->getConfig()->get('rule_set_info')['build_ver'] ?? 'dev';
        // Get PHP version
        $php_ver = $event->getComposer()->getConfig()->get('rule_set_info')['php_ver'] ?? '8.2';
        // Check remote URL exists or not
        $file_headers      = @get_headers($remote_host.'phpcs.ruleset.xml'); //Updated with the new server directory ACL.
        if (!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
            exit();
        } else {
            if (!empty($remote_host) && !empty($phpcs_rule_set) && !empty($phpmd_rule_set)) {
                // Set rule set directory
                chdir('../');
                $target_dir      = getcwd().DIRECTORY_SEPARATOR.'.git'.DIRECTORY_SEPARATOR.'hooks';
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                // Get pre commit hook from server
                $pre_commit_hook = file_get_contents($remote_host.DIRECTORY_SEPARATOR.'git-hook'.DIRECTORY_SEPARATOR.'pre-commit');
                $find    = [ '[RULE-REMOTE-HOST]', '[RULE-SET-CS]', '[RULE-SET-MESS-DETECTOR]', '[RULE_SET_DIR]', '[PHPMD_EXCLUDE_DIR]', '[PHPCS_EXCLUDE_DIR]', '[BUILD_VER]', '[PHP_VER]' ];
                $replace = [ $remote_host, $phpcs_rule_set, $phpmd_rule_set, self::$rule_set_dir, $phpmd_exclude_dir, $phpcs_exclude_dir, $build_ver, $php_ver ];
                //Replace all the configs in the server precommit file.
                $pre_commit_hook = str_replace( $find, $replace, $pre_commit_hook );
                
                // Save pre-commit hook
                file_put_contents(
                    __DIR__.DIRECTORY_SEPARATOR.'pre-commit', $pre_commit_hook
                );
                // Save phpcs ruleset
                $phpcs_rule_file = file_get_contents($remote_host.'phpcs.ruleset.xml');
                file_put_contents(__DIR__.DIRECTORY_SEPARATOR.'phpcs.ruleset.xml',
                    $phpcs_rule_file);

                // Save phpmd ruleset
                $phpmd_rule_file = file_get_contents($remote_host.'phpmd.ruleset.xml');
                file_put_contents(__DIR__.DIRECTORY_SEPARATOR.'phpmd.ruleset.xml',
                    $phpmd_rule_file);

                // Copy pre-commit hook to git directory
                copy(
                    __DIR__.DIRECTORY_SEPARATOR.'pre-commit',
                    $target_dir.DIRECTORY_SEPARATOR.'pre-commit'
                );
                // Change pre-commit hook permission
                chmod($target_dir.DIRECTORY_SEPARATOR.'pre-commit', 0775);
            }
        }
    }
}
