<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors','1');
$dir = sys_get_temp_dir() . '/amino-llms-tests-' . getmypid() . '/';
if (!mkdir($dir, 0700, true) && !is_dir($dir)) { throw new RuntimeException('mkdir failed'); }
define('ABSPATH', $dir);
define('HOUR_IN_SECONDS', 3600);
$GLOBALS['options'] = ['amino_llms_highlighted_ids'=>'90539,90377,90499'];
$GLOBALS['schedule'] = [];
$GLOBALS['hooks'] = [];
class WP_Post {
    public function __construct(public int $ID, public string $post_type, public string $post_status, public string $post_title, public string $post_name, public string $post_date, public string $post_modified) {}
}
$GLOBALS['posts'] = [
    new WP_Post(90539, 'post', 'publish', 'Nvidia : derrière l’empire', 'nvidia-test', '2026-11-15', '2026-11-15'),
    new WP_Post(90377, 'post', 'publish', 'Cyberpunk d’Asma Mhalla', 'cyberpunk', '2026-11-14', '2026-11-14'),
    new WP_Post(90499, 'post', 'publish', 'Minab : enfants de la guerre', 'minab', '2026-04-15', '2026-04-15'),
    new WP_Post(90441, 'post', 'publish', 'Quand Internet s’éteint', 'coupures-internet', '2026-11-06', '2026-11-06'),
    new WP_Post(90379, 'post', 'publish', 'Hamza Bendelladj', 'hamza', '2026-10-30', '2026-10-30'),
    new WP_Post(90440, 'post', 'publish', 'Les routes physiques d’Internet', 'cables', '2026-10-23', '2026-10-23'),
    new WP_Post(90384, 'post', 'publish', 'DGSI et Palantir', 'dgsi', '2026-10-16', '2026-10-16'),
    new WP_Post(90376, 'post', 'publish', 'Asma Mhalla, Technopolitique', 'techno', '2026-10-09', '2026-10-09'),
    new WP_Post(90021, 'post', 'publish', 'AI Act', 'ai-act', '2026-08-18', '2026-08-18'),
    new WP_Post(90418, 'amino_project', 'publish', 'Ziryab 2.0', 'project/ziryab-2-0', '2026-11-01', '2026-11-15'),
    new WP_Post(90317, 'amino_project', 'publish', 'Ziryab 1.0', 'project/ziryab-1-0', '2026-11-01', '2026-11-15'),
    new WP_Post(1220, 'amino_project', 'publish', 'Regala una Foto', 'project/regala-una-foto', '2026-11-01', '2026-11-13'),
];
function add_action(string $name, callable $callback, int $priority = 10, int $accepted_args = 1): void { $GLOBALS['hooks'][$name][] = $callback; }
function register_activation_hook(string $file, callable $callback): void {}
function register_deactivation_hook(string $file, callable $callback): void {}
function wp_next_scheduled(string $hook): bool { return isset($GLOBALS['schedule'][$hook]); }
function wp_schedule_event(int $when, string $recurrence, string $hook): void { $GLOBALS['schedule'][$hook] = [$when,$recurrence]; }
function wp_clear_scheduled_hook(string $hook): void { unset($GLOBALS['schedule'][$hook]); }
function get_option(string $name, mixed $default = false): mixed { return $GLOBALS['options'][$name] ?? $default; }
function add_option(string $name, mixed $value, string $deprecated='', bool $autoload=true): bool { $GLOBALS['options'][$name]=$value;return true; }
function update_option(string $name, mixed $value, bool $autoload=true): bool { $GLOBALS['options'][$name]=$value;return true; }
function delete_option(string $name): bool { unset($GLOBALS['options'][$name]);return true; }
function wp_is_post_revision(int $id): bool { return false; }
function wp_is_post_autosave(int $id): bool { return false; }
function absint(mixed $id): int { return abs((int)$id); }
function get_post(int $id): ?WP_Post { foreach ($GLOBALS['posts'] as $post) if ($post->ID === $id) return $post;return null; }
function get_posts(array $args): array {
    $posts=array_filter($GLOBALS['posts'],function(WP_Post $p) use($args):bool {
        return $p->post_type === ($args['post_type']??'post') && $p->post_status === ($args['post_status']??'publish') && !in_array($p->ID,$args['post__not_in']??[],true);
    });
    $prop=($args['orderby']??'date')==='modified'?'post_modified':'post_date';
    usort($posts,fn($a,$b) => strcmp($b->$prop,$a->$prop));
    return array_slice($posts,0,$args['posts_per_page']??$args['numberposts']??5);
}
function get_permalink(WP_Post $post): string {return 'https://aminostudio.fr/'.$post->post_name.'/';}
function get_the_title(WP_Post $post): string {return $post->post_title;}
function get_the_date(string $format, WP_Post $post): string {return date($format,strtotime($post->post_date));}
function home_url(string $path = '/'): string {return 'https://aminostudio.fr'.$path;}
function esc_url_raw(string $value): string {return $value;}
function wp_strip_all_tags(string $text): string {return strip_tags($text);}
function trailingslashit(string $text): string {return rtrim($text,'/').'/';}
function current_time(string $format): string {return date('Y-m-d H:i:s');}
// Required for incidental admin path; not called by these tests.
require dirname(__DIR__).'/plugin/amino-llms-dynamic/amino-llms-dynamic.php';
function yes(bool $cond,string $message): void { if(!$cond) throw new RuntimeException($message); echo "PASS: $message\n"; }
function count_matches(string $text, string $needle): int { return substr_count($text,$needle); }
$base=<<<'TXT'
# Amino Studio

> Independent studio in Marseille.

## Main Pages

- [Home](https://aminostudio.fr/): Studio introduction.
- [Work](https://aminostudio.fr/work/): Selected work.
- [Story](https://aminostudio.fr/story/): History.
- [Lab](https://aminostudio.fr/lab/): Technical lab.

## Selected Web Projects

- [Amino Studio](https://aminostudio.fr/project/amino-studio/): Studio.

## Selected Film and Audiovisual Projects

- [Festival NDG X](https://aminostudio.fr/project/festival-ndg-x/): Documentary work.

## Selected Photography and Visual Projects

- [Cuba Acere](https://aminostudio.fr/project/cuba-acere/): Documentary photography.

## Project Categories

- [Web Projects](https://aminostudio.fr/project-category/web/)
- [Film Projects](https://aminostudio.fr/project-category/film/)
- [Photo Projects](https://aminostudio.fr/project-category/photo/)

For the full published portfolio, use the [Work page](https://aminostudio.fr/work/) and XML sitemap.

## Marseille, France and International Work

Amino Studio is based in Marseille.

## Contact and Further Information

- [Studio Website](https://aminostudio.fr/)

## Additional Resources

- [XML Sitemap Index](https://aminostudio.fr/sitemap_index.xml)
TXT;
// Existing v1.0 marker block, to check forward migrations on real existing installs.
$with_old=str_replace('## Marseille, France and International Work', '<!-- AMINO-DYNAMIC:START -->'."\n\n## Other Site Resources\n\n- [Contact](https://aminostudio.fr/contact/)\n- [Complete XML Sitemap](https://aminostudio.fr/sitemap_index.xml)\n\n".'<!-- AMINO-DYNAMIC:END -->'."\n\n## Marseille, France and International Work",$base);
$path=$dir.'llms.txt';file_put_contents($path,$with_old);
yes(Amino_Dynamic_LLMS::refresh(),'First refresh from v1.0 succeeds');
$doc=file_get_contents($path);
yes(count_matches($doc,'[ACTA — Editorial Journal](https://aminostudio.fr/chroniques/)')===1,'ACTA appears once in Main Pages');
yes(count_matches($doc,'[Contact](https://aminostudio.fr/contact/)')===1,'Contact is linked exactly once');
yes(count_matches($doc,'[Spaces Projects](https://aminostudio.fr/project-category/spaces/)')===1,'Spaces category added once');
yes(count_matches($doc,'## Selected Spaces Projects')===1,'Spaces projects section added once');
yes(str_contains($doc,'[Ziryab 1.0 — Al-Ándalus CS]') && str_contains($doc,'[Ziryab 2.0 — Al-Ándalus CS]'),'Both spatial projects linked with their client');
yes(count_matches($doc,'## Main Pages')===1 && str_contains($doc,'- [Lab]('),'Original editorial content preserved');
yes(!str_contains($doc,'## Other Site Resources'),'Redundant resources section removed');
yes(count_matches($doc,'[Nvidia : derrière l’empire]')===1,'Highlights do not duplicate Latest');
yes(str_contains($doc,'[Hamza Bendelladj]') && str_contains($doc,'[DGSI et Palantir]'),'Non-highlighted recent articles included');
yes(str_contains($doc,'[Ziryab 2.0]') && str_contains($doc,'[Regala una Foto]'),'Recent portfolio generated from projects');
// Repeated output must be byte-identical, apart from a DB timestamp stored separately.
yes(Amino_Dynamic_LLMS::refresh(),'Second refresh succeeds');
yes(file_get_contents($path)===$doc,'Refresh is idempotent, byte for byte');
// Highlight selection edits are dynamic, not hardcoded in PHP.
$GLOBALS['options']['amino_llms_highlighted_ids']='90379,90384,90379,0';
yes(Amino_Dynamic_LLMS::refresh(),'Updated highlight option refreshes document');
$doc2=file_get_contents($path);
yes(count_matches($doc2,'[Hamza Bendelladj]')===1 && count_matches($doc2,'[DGSI et Palantir]')===1,'Changed highlight IDs have no duplicate entries');
// Malformed markers must leave the file untouched and give an error.
$bad=$doc2."\n<!-- AMINO-DYNAMIC:START -->\n";file_put_contents($path,$bad);
yes(!Amino_Dynamic_LLMS::refresh(),'Duplicate markers rejected');
yes(file_get_contents($path)===$bad,'Marker error never changes the file');
// Invalid structure also fail closed.
$bad2=str_replace('## Main Pages','## WRONG MAIN PAGES',$doc2);file_put_contents($path,$bad2);
yes(!Amino_Dynamic_LLMS::refresh(),'Missing required heading rejected');
yes(file_get_contents($path)===$bad2,'Missing heading error never changes the file');
// Upgrade behavior (ordinary ZIP replacement) preserves existing highlighted IDs.
file_put_contents($path,$with_old);
$GLOBALS['options']['amino_llms_plugin_version']='1.0.0';
Amino_Dynamic_LLMS::maybe_upgrade();
yes(get_option('amino_llms_plugin_version')==='1.1.0','Automatic version migration on next WordPress init');
yes(get_option('amino_llms_highlighted_ids')==='90379,90384,90379,0','Editor-selected IDs preserved by upgrade');
yes(isset($GLOBALS['schedule']['amino_llms_refresh']),'Hourly cron scheduled');
yes(str_contains(file_get_contents($path),'## Selected Spaces Projects'),'Upgrade rewrites llms.txt with new sections');
yes(get_option('amino_llms_highlight_mode')==='manual','v1.0 curated highlights retain manual mode');
$GLOBALS['options']['amino_llms_highlight_mode']='auto';
yes(Amino_Dynamic_LLMS::refresh(),'Automatic highlight mode generates without configured IDs');
$auto=file_get_contents($path);
$start=strpos($auto, '### Highlighted ACTA Articles');
$end=strpos($auto, '### Latest ACTA Articles');
$featured=substr($auto,$start,$end-$start);
yes(str_contains($featured, '[Nvidia : derrière l’empire]') && str_contains($featured, '[Cyberpunk d’Asma Mhalla]') && str_contains($featured, '[Quand Internet s’éteint]'),'Auto mode selects the three newest published posts');
yes(count_matches($auto,'[Quand Internet s’éteint]')===1,'Auto highlighted articles excluded from recent list');
// No write when llms file is a symlink.
@unlink($path);file_put_contents($dir.'target.txt',$base);symlink($dir.'target.txt',$path);
yes(!Amino_Dynamic_LLMS::refresh(),'Symlink rejected');
yes(file_get_contents($dir.'target.txt')===$base,'Symlink target untouched');
@unlink($path);@unlink($dir.'target.txt');@rmdir($dir);
echo "ALL TESTS PASSED\n";
