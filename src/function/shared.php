<?php
function in_array_r($needle, $haystack, $strict = false)
{
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
            return true;
        }
    }

    return false;
}

function ob_in_array_r($needle, $haystack, $strict = false)
{
    foreach ($haystack as $key => $item) {
        if ($item['id_processopredicado'] === $needle)
            return $item;
    }

    return false;
}
