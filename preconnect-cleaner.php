<?php

/**
 * Plugin Name:     Preconnect Cleaner
 * Plugin URI:      https://github.com/ItinerisLtd/preconnect-cleaner/
 * Description:     Preconnect Cleaner.
 * Version:         0.1.1
 * Author:          Itineris Limited
 * Author URI:      https://www.itineris.co.uk/
 * Text Domain:     preconnect-cleaner
 */

declare(strict_types=1);

namespace Itineris\PreconnectCleaner;

use WP_Dependencies;

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

add_filter('wp_resource_hints', function (array $urls, string $relation_type): array {
    $wp_scripts = $GLOBALS['wp_scripts'];
    $wp_styles = $GLOBALS['wp_styles'];

    $unique_urls = [];

    foreach ([$wp_scripts, $wp_styles] as $dependencies) {
        if ($dependencies instanceof WP_Dependencies && ! empty($dependencies->queue)) {
            foreach ($dependencies->queue as $handle) {
                if (!isset($dependencies->registered[$handle])) {
                    continue;
                }

                $dependency = $dependencies->registered[$handle];
                $parsed = wp_parse_url($dependency->src);

                if (
                    ! empty($parsed['host'])
                    && ! in_array($parsed['host'], $unique_urls, true)
                    && $parsed['host'] !== $_SERVER['SERVER_NAME'] //phpcs:ignore
                ) {
                    $unique_urls[] = ($parsed['scheme'] ?? 'https') . '://' . $parsed['host'];
                }
            }
        }
    }

    if ('dns-prefetch' === $relation_type) {
        $urls = [];
    }

    if ('preconnect' === $relation_type) {
        $urls = $unique_urls;
    }

    return $urls;
}, 0, 2);
