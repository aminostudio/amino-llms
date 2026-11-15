<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
define('HOUR_IN_SECONDS', 3600);
$root = sys_get_temp_dir() . '/amino-llms-portable-' . getmypid() . '/';
if (!mkdir($root, 0700, true) && !is_dir($root)) { throw new RuntimeException('Cannot create fixture root'); }
define('ABSPATH', $root);

class WP_Post {
    public function __construct(
        public int $ID,
        public string $post_type,
        public string $post_status,
        public string $post_title,
        public string $post_name,
        public string $post_date,
        public string $post_password = ''
    ) {}
}
class WP_Error {
    public function __construct(public string $code, public string $message) {}
    public function get_error_message(): string { return $this->message; }
}
function is_wp_error($x): bool {return $x instanceof WP_Error;}
$GLOBALS['options'] = ['blogname'=>'Example Studio','blogdescription'=>'An independent digital studio','page_on_front'=>10];
$GLOBALS['post_meta'] = [
    4=>['_yoast_wpseo_meta-robots-noindex'=>'1'],
    6=>['rank_math_robots'=>['noindex']],
];
$GLOBALS['schedule'] = [];
$GLOBALS['posts'] = [
    new WP_Post(1, 'post', 'publish', 'First article', 'first', '2026-11-15'),
    new WP_Post(2, 'post', 'publish', 'Second article', 'second', '2026-11-14'),
    new WP_Post(3, 'post', 'publish', 'Password story', 'secret', '2026-11-13','topsecret'),
    new WP_Post(4, 'post', 'publish', 'Yoast hidden', 'yoast-hidden', '2026-11-12'),
    new WP_Post(5, 'post', 'private', 'Private story', 'private', '2026-11-11'),
    new WP_Post(6, 'post', 'publish', 'Rank Math hidden', 'rank-hidden', '2026-11-10'),
    new WP_Post(7, 'book', 'publish', 'Book on tech', 'books/tech', '2026-11-15'),
    new WP_Post(8, 'book', 'publish', 'Book about culture', 'books/culture', '2026-11-11'),
    new WP_Post(10, 'page', 'publish', 'Home page', 'home', '2026-10-01'),
    new WP_Post(11, 'page', 'publish', 'About us', 'about', '2026-10-02'),
];
$GLOBALS['types'] = [
    'post' => (object) ['name'=>'post','public'=>true,'labels'=>(object)['name'=>'Posts']],
    'page' => (object) ['name'=>'page','public'=>true,'labels'=>(object)['name'=>'Pages']],
    'book' => (object) ['name'=>'book','public'=>true,'labels'=>(object)['name'=>'Books']],
    'attachment' => (object) ['name'=>'attachment','public'=>true,'labels'=>(object)['name'=>'Media']],
    'private_cpt' => (object) ['name'=>'private_cpt','public'=>false,'labels'=>(object)['name'=>'Private']],
];
function add_action(...$args): void {}
function register_activation_hook(...$args): void {}
function register_deactivation_hook(...$args): void {}
function wp_next_scheduled($hook) { return $GLOBALS['schedule'][$hook] ?? false;}
function wp_schedule_event($when,$frequency,$hook): void {$GLOBALS['schedule'][$hook]=[$when,$frequency];}
function wp_clear_scheduled_hook($hook): void {unset($GLOBALS['schedule'][$hook]);}
function get_bloginfo($name): string {return (string)($GLOBALS['options'][$name === 'name' ? 'blogname' : 'blogdescription'] ?? '');}
function get_option($name,$default=false): mixed{return $GLOBALS['options'][$name] ?? $default;}
function update_option($name,$value,$autoload=false): bool{$GLOBALS['options'][$name]=$value;return true;}
function delete_option($name): bool{unset($GLOBALS['options'][$name]);return true;}
function get_site_option($name,$default=false): mixed{return $default;}
function get_post_types($args=[],$mode='names'): array {
    return array_filter($GLOBALS['types'], static fn($type)=>$type->public === ($args['public']??true));
}
function get_post_type_object($name): ?object {return $GLOBALS['types'][$name]??null;}
function get_post($id): ?WP_Post {foreach($GLOBALS['posts'] as $p){if($p->ID===$id)return $p;}return null;}
function get_post_meta($id,$key,$single=true): mixed{return $GLOBALS['post_meta'][$id][$key]??'';}
function get_posts($args): array {
    $posts=array_values(array_filter($GLOBALS['posts'], fn($p)=>$p->post_type===$args['post_type']&&$p->post_status==='publish'));
    usort($posts,fn($a,$b)=>strcmp($b->post_date,$a->post_date));
    return array_slice($posts,0,$args['posts_per_page']??6);
}
function get_permalink(WP_Post $post): string {return 'https://example.test/'.$post->post_name.'/';}
function get_the_title(WP_Post $post): string {return $post->post_title;}
function home_url($path='/'): string {return 'https://example.test'.$path;}
function site_url($path='/'): string {return 'https://example.test'.$path;}
function wp_parse_url($url,$component=null): mixed{return parse_url($url,$component);}
function esc_url_raw($url): string {return $url;}
function wp_strip_all_tags($text): string {return strip_tags($text);}
function apply_filters($hook,$value,...$args): mixed{return $value;}
function sanitize_key($v): string {return preg_replace('/[^a-z0-9_-]/','',strtolower($v));}
function sanitize_text_field($v): string {return trim(strip_tags((string)$v));}
function sanitize_textarea_field($v): string {return trim(strip_tags((string)$v));}
function absint($v): int {return abs((int)$v);}
function trailingslashit($v): string {return rtrim($v,'/').'/';}
function current_time($v): string {return '2026-11-15 20:00:00';}
function wp_is_post_revision($id): bool{return false;}
function wp_is_post_autosave($id): bool{return false;}

require dirname(__DIR__).'/plugin/amino-llms/amino-llms.php';
$checks=0;
function assert_that(bool $value,string $label): void {
    global $checks;
    if(!$value){throw new RuntimeException("FAILED: ".$label);}
    $checks++;
    echo "PASS $checks: $label\n";
}
$path=ABSPATH.'llms.txt';
Amino_LLMS::activate();
assert_that(!is_file($path),'Activation never creates or overwrites llms.txt');
assert_that(isset($GLOBALS['schedule']['amino_llms_v12_refresh']),'Activation schedules hourly check');

$defaults=Amino_LLMS::defaults();
assert_that($defaults['mode']==='hybrid','Default document mode is hybrid');
assert_that($defaults['post_types']===['page','post'],'Default types are pages and posts');
$settings=Amino_LLMS::clean_settings([
    'mode'=>'manual','site_name'=>'Custom Studio','summary'=>'<b>Useful</b> content',
    'post_types'=>['page','book','private_cpt','attachment','book'],
    'items_per_type'=>500,'highlight_ids'=>'1,2,1,-3,abc,0',
    'manual_markdown'=>'# Manual guide'."\n\n".'## Pages'."\n".'- [Example](https://example.test/)'
]);
assert_that($settings['mode']==='manual','Manual mode accepted');
assert_that($settings['post_types']===['page','book'],'Unknown and private types removed');
assert_that($settings['items_per_type']===25,'Per-type count limited');
assert_that($settings['highlight_ids']==='1,2','Highlighted IDs sanitized and deduplicated');
assert_that($settings['summary']==='Useful content','Description sanitized');
$manual=Amino_LLMS::render_document($settings);
assert_that(str_starts_with($manual,'# Manual guide'),'Manual mode renders user-authored Markdown');

$settings=Amino_LLMS::clean_settings(['mode'=>'hybrid','site_name'=>'Example Studio','summary'=>'Custom explanatory summary','introduction'=>'Our priorities are editorial and independent.','post_types'=>['page','post','book'],'items_per_type'=>5,'highlight_ids'=>'2,4,3,5,7,1']);
$doc=Amino_LLMS::render_document($settings);
assert_that(str_contains($doc,'# Example Studio'),'Main title included');
assert_that(str_contains($doc,'> Custom explanatory summary'),'Summary included');
assert_that(str_contains($doc,'Our priorities are editorial and independent.'),'Hybrid introduction included');
assert_that(str_contains($doc,'## Highlighted'),'Highlights generated');
assert_that(str_contains($doc,'[Second article](https://example.test/second/)'),'Published highlighted article included');
assert_that(!str_contains($doc,'Yoast hidden')&&!str_contains($doc,'Password story')&&!str_contains($doc,'Private story')&&!str_contains($doc,'Rank Math hidden'),'Protected/private/noindex content excluded');
assert_that(str_contains($doc,'[Book on tech]('),'Public CPT included');
assert_that(!str_contains($doc,'[Home page]('),'Static homepage not duplicated');
assert_that(substr_count($doc,'[First article](')===1,'Featured item not duplicated across sections');
assert_that(str_contains($doc,'## Books'),'Custom post type has human-readable heading');
assert_that(substr_count($doc,'- [Home](https://example.test/)')===1,'Main home link present exactly once');

$GLOBALS['options']['amino_llms_v12_settings']=$settings;
Amino_LLMS::automatic_refresh();
assert_that(!file_exists($path),'Automatic refresh does nothing before first deliberate publication');

file_put_contents($path,'# Existing editorial document'."\n");
assert_that(!Amino_LLMS::write_document($doc),'Existing unmanaged file protected');
assert_that(file_get_contents($path)==="# Existing editorial document\n",'Original file preserved without adoption');
assert_that(Amino_LLMS::write_document($doc,true),'Explicit adoption writes a valid generated document');
assert_that(file_get_contents($path)===$doc,'Published content verified');
assert_that(get_option('amino_llms_v12_file_hash')===hash('sha256',$doc),'Managed file is tracked by checksum');
assert_that(get_option('amino_llms_v12_last_backup')['content']==="# Existing editorial document\n",'Previous file stored as a restore point');
assert_that(Amino_LLMS::write_document($doc,false),'Idempotent repeat accepted');
assert_that(file_get_contents($path)===$doc,'Repeat did not alter file content');

file_put_contents($path,"# Outside revision\n");
assert_that(!Amino_LLMS::write_document($doc),'Externally modified file blocked');
assert_that(file_get_contents($path)==="# Outside revision\n",'External edit preserved');
assert_that(Amino_LLMS::write_document($doc,true),'Explicit re-adoption accepted');

$GLOBALS['options']['active_plugins']=['amino-llms-dynamic/amino-llms-dynamic.php'];
assert_that(!Amino_LLMS::write_document($doc,true),'Legacy plugin conflict blocks write even with adoption');
unset($GLOBALS['options']['active_plugins']);

assert_that(!Amino_LLMS::write_document('No top level title',true),'Invalid Markdown without H1 refused');
assert_that(!Amino_LLMS::write_document('# Test'."\n".str_repeat('x',140000),true),'Oversized Markdown refused');

@unlink($path);file_put_contents($root.'target.txt','# Symlink original'."\n");symlink($root.'target.txt',$path);
assert_that(!Amino_LLMS::write_document($doc,true),'Symbolic links refused');
assert_that(file_get_contents($root.'target.txt')==="# Symlink original\n",'Symlink target unchanged');

@unlink($path);@unlink($root.'target.txt');@rmdir($root);
echo "ALL $checks PORTABLE TESTS PASSED\n";
