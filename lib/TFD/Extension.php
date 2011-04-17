<?php
/* register the drupal specific tags and filters within a
* proper declared twig extension
*
* Part of the Drupal twig extension distribution
* http://renebakx.nl/twig-for-drupal
*/

class TFD_Extension extends Twig_Extension {

    /* registers the drupal specific tags */
    public function getTokenParsers() {
        $parsers = array();
        $parsers[] = new TFD_TokenParser_FunctionCall('theme');
        $parsers[] = new TFD_TokenParser_FunctionCall('render');
        $parsers[] = new TFD_TokenParser_FunctionCall('hide');
        $parsers[] = new TFD_TokenParser_FunctionCall('path_to_theme', 'tfd_path_to_theme');
        $parsers[] = new TFD_TokenParser_With();
        $parsers[] = new TFD_TokenParser_Switch();
        $parsers[] = new TFD_TokenParser_Unset();
        return $parsers;
    }

    /* registers the drupal specific filters */
    public function getFilters() {
        $filters = array();
        $filters['replace'] = new Twig_Filter_Function('tfd_str_replace');
        $filters['re_replace'] = new Twig_Filter_Function('tfd_re_replace');
        $filters['dump'] = new Twig_Filter_Function('tfd_dump', array('needs_environment' => true));
        $filters['defaults'] = new Twig_Filter_Function('tfd_defaults_filter');

        $filters['size'] = new Twig_Filter_Function('format_size');
        $filters['url'] = new Twig_Filter_Function('tfd_url');
        $filters['t'] = new Twig_Filter_Function('t');
        $filters['ucfirst'] = new Twig_Filter_Function('ucfirst');

        // Drupal 7 render'n'hide
        $filters['render'] = new Twig_Filter_Function('tfd_render');
        $filters['hide'] = new Twig_Filter_Function('tfd_hide');

        $filters['image_url'] = new Twig_Filter_Function('tfd_image_url');
        $filters['image_size'] = new Twig_Filter_Function('tfd_image_size');
        $filters['date_format'] = new Twig_Filter_Function('tfd_date_format_filter');

        // RB Backwards compatible with old twig for drupal templates
        $filters['imagecache_url'] = $filters['image_url'];
        $filters['imagecache_size'] = $filters['image_size'];

        $filters = array_merge($filters, module_invoke_all('twig_filters', $filters));
        return $filters;
    }


    public function getTests() {
        $ret = array();
        $ret['property'] = new Twig_Test_Function('tfd_property_test');
        return $ret;
    }

    public function getName() {
        return 'drupal';
    }
}

// ------------------------------------------------------------------------------------------------
// the above declared filter implementations go here

/**
 * Twig filter for str_replace, switches needle and arguments to provide sensible
 * filter arguments order
 *
 * {{ haystack|replace("needle", "replacement") }}
 *
 * @param  $haystack
 * @param  $needle
 * @param  $repl
 * @return mixed
 */
function TFD_str_replace($haystack, $needle, $repl) {
    return str_replace($needle, $repl, $haystack);
}

function TFD_re_replace($haystack, $needle, $repl) {
    return preg_replace($needle, $repl, $haystack);
}


function tfd_render($var) {
    if (is_array($var) || is_object($var)) {
        return render($var);
    }
}

function tfd_hide($var) {
    if (is_array($var) || is_object($var)) {
        return hide($var);
    }
}

function tfd_path_to_theme() {
    return base_path() . path_to_theme();
}


function tfd_date_format_filter($timestamp, $format = '%d-%m-%Y %H:%M', $mode = 'strftime') {
    switch ($mode) {
        case 'strftime':
        case 'date':
            return $mode($format, $timestamp);
            break;
        default:
            throw new InvalidArgumentException($mode . ' is not a valid date_format mode');
    }
}


function tfd_defaults_filter($value, $defaults = null) {
    $args = func_get_args();
    $args = array_filter($args);
    if (count($args)) {
        return array_shift($args);
    } else {
        return null;
    }
}


function TFD_dump($env, $var, $function = null) {
    static $functions = array('dpr' => null, 'dpm' => null, 'print_r' => 'p', 'var_dump' => 'v');
    if (empty($function)) {
        if (module_exists('devel')) {
            $function = array_shift(array_keys($functions));
        } else {
            $function = array_pop(array_keys($functions));
        }
    }

    if (array_key_exists($function, $functions) && is_callable($function)) {
        call_user_func($function, $var);
    } else {
        $found = false;
        foreach ($functions as $name => $alias) {
            if (in_array($function, (array)$alias)) {
                $found = true;
                call_user_func($name, $var);
                break;
            }
        }
        if (!$found) {
            throw new InvalidArgumentException("Invalid mode '$function' for TFD_dump()");
        }
    }
}


function tfd_image_url($filepath, $preset = null) {
    if (is_array($filepath)) {
        $filepath = $filepath['filepath'];
    }
    if ($preset) {
        return image_style_url($preset, $filepath);
    } else {
        return $filepath;
    }
}


function tfd_image_size($filepath, $preset, $asHtml = true) {
    if (is_array($filepath)) {
        $filepath = $filepath['filepath'];
    }
    $info = image_get_info(image_style_url($preset, $filepath));
    $attr = array('width' => (string)$info['width'], 'height' => (string)$info['height']);
    if ($asHtml) {
        return drupal_attributes($attr);
    } else {
        return $attr;
    }
}


function tfd_url($item, $options = array()) {
    if (is_numeric($item)) {
        $ret = url('node/' . $item, (array)$options);
    } else {
        $ret = url($item, (array)$options);
    }
    return check_url($ret);
}


function tfd_property_test($element, $propertyName, $value = true) {
    return array_key_exists("#$propertyName", $element) && $element["#$propertyName"] == $value;
}

?>