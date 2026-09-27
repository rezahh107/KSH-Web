<?php
/**
 * Deterministic contract validation for the source-controlled Elementor homepage body template.
 */

declare(strict_types=1);

$repoRoot = dirname(__DIR__);
$templatePath = $repoRoot . '/elementor/homepage/ksh-public-homepage-body-v1.json';

function fail(string $message): never
{
    fwrite(STDERR, "ELEMENTOR_HOMEPAGE_VERIFY_FAIL: {$message}\n");
    exit(1);
}

function assertCondition(bool $condition, string $message): void
{
    if (! $condition) {
        fail($message);
    }
}

function flattenScalars(mixed $value, array &$scalars): void
{
    if (is_array($value)) {
        foreach ($value as $nested) {
            flattenScalars($nested, $scalars);
        }
        return;
    }

    if (is_string($value)) {
        $scalars[] = $value;
    }
}

function walkElements(array $elements, array &$state): void
{
    foreach ($elements as $element) {
        assertCondition(is_array($element), 'each Elementor element must be an object');
        foreach (array('id', 'elType', 'isInner', 'settings', 'elements') as $requiredKey) {
            assertCondition(array_key_exists($requiredKey, $element), "element missing required key: {$requiredKey}");
        }

        assertCondition(is_string($element['id']) && 1 === preg_match('/^[a-f0-9]{8}$/', $element['id']), 'element id must be unique 8-char lowercase hex');
        assertCondition(! isset($state['ids'][$element['id']]), 'duplicate Elementor element id: ' . $element['id']);
        $state['ids'][$element['id']] = true;

        assertCondition(in_array($element['elType'], array('container', 'widget'), true), 'unsupported element type: ' . (string) $element['elType']);
        assertCondition(is_array($element['settings']), 'element settings must be an object/array');
        assertCondition(is_array($element['elements']), 'element elements must be an array');

        $settings = $element['settings'];
        foreach (array_keys($settings) as $settingKey) {
            if (str_ends_with((string) $settingKey, '_tablet')) {
                $state['responsive_tablet'] = true;
            }
            if (str_ends_with((string) $settingKey, '_mobile')) {
                $state['responsive_mobile'] = true;
            }
        }

        if ('container' === $element['elType']) {
            ++$state['containers'];
            assertCondition(! isset($settings['custom_css']), 'custom CSS is not allowed in the homepage body artifact');
            assertCondition(! isset($settings['background_video_link']), 'video backgrounds are not allowed');
            assertCondition(! isset($settings['motion_fx_motion_fx_scrolling']), 'motion effects are not allowed');
            walkElements($element['elements'], $state);
            continue;
        }

        assertCondition(isset($element['widgetType']) && is_string($element['widgetType']), 'widget must declare widgetType');
        assertCondition(in_array($element['widgetType'], array('heading', 'text-editor', 'button', 'shortcode'), true), 'unsupported widget type: ' . (string) $element['widgetType']);
        ++$state['widgets'];

        if ('heading' === $element['widgetType']) {
            $tag = $settings['header_size'] ?? 'h2';
            assertCondition(in_array($tag, array('h1', 'h2', 'h3'), true), 'homepage heading hierarchy must use h1/h2/h3 only');
            ++$state['headings'][$tag];
            assertCondition('right' === ($settings['align'] ?? null), 'Persian heading widgets must be right aligned');
        }

        if ('text-editor' === $element['widgetType']) {
            assertCondition('right' === ($settings['align'] ?? null), 'Persian text widgets must be right aligned');
        }

        if ('button' === $element['widgetType']) {
            ++$state['buttons'];
            assertCondition('right' === ($settings['align'] ?? null), 'Persian button widgets must be right aligned');
            assertCondition('ورود مدیران' === ($settings['text'] ?? null), 'only the confirmed manager action may be rendered in v1');
            $url = $settings['link']['url'] ?? null;
            assertCondition('/plato-user-panel/' === $url, 'manager action must target /plato-user-panel/ exactly');
            ++$state['manager_links'];
        }

        if ('shortcode' === $element['widgetType']) {
            assertCondition('[ksh_kanoon_articles]' === ($settings['shortcode'] ?? null), 'shortcode widget must contain the accepted Kanoon shortcode exactly');
            ++$state['shortcode_widgets'];
        }

        assertCondition([] === $element['elements'], 'widgets must not carry nested elements in this artifact');
    }
}

assertCondition(is_file($templatePath), 'homepage template artifact is missing');
$raw = file_get_contents($templatePath);
assertCondition(false !== $raw && '' !== trim($raw), 'homepage template artifact is empty');

try {
    $template = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    fail('invalid JSON: ' . $exception->getMessage());
}

assertCondition(is_array($template), 'template root must be an object');
assertCondition('page' === ($template['type'] ?? null), 'template type must be page');
assertCondition('0.4' === ($template['version'] ?? null), 'Elementor page-template data version must be 0.4');
assertCondition(is_string($template['title'] ?? null) && '' !== trim($template['title']), 'template title is required');
assertCondition(isset($template['page_settings']) && is_array($template['page_settings']), 'page_settings must be present');
assertCondition('main' === ($template['page_settings']['content_wrapper_html_tag'] ?? null), 'page body wrapper must use semantic main');
assertCondition(isset($template['content']) && is_array($template['content']) && [] !== $template['content'], 'template content must be non-empty');

$state = array(
    'ids' => array(),
    'containers' => 0,
    'widgets' => 0,
    'headings' => array('h1' => 0, 'h2' => 0, 'h3' => 0),
    'buttons' => 0,
    'manager_links' => 0,
    'shortcode_widgets' => 0,
    'responsive_tablet' => false,
    'responsive_mobile' => false,
);
walkElements($template['content'], $state);

assertCondition(1 === $state['headings']['h1'], 'template must contain exactly one h1');
assertCondition($state['headings']['h2'] >= 1, 'template must contain at least one h2');
assertCondition($state['headings']['h3'] >= 1, 'manager card must preserve h3 hierarchy');
assertCondition(1 === $state['buttons'] && 1 === $state['manager_links'], 'template must contain exactly one manager portal action');
assertCondition(1 === $state['shortcode_widgets'], 'template must contain exactly one shortcode widget');
assertCondition($state['responsive_tablet'], 'tablet responsive metadata is required');
assertCondition($state['responsive_mobile'], 'mobile responsive metadata is required');

$scalars = array();
flattenScalars($template, $scalars);
$scalarText = implode("\n", $scalars);
$serialized = $raw;
$lower = strtolower($raw);

assertCondition(1 === substr_count($serialized, '[ksh_kanoon_articles]'), 'accepted article shortcode must appear exactly once');
assertCondition(1 === substr_count($serialized, '/plato-user-panel/'), 'manager portal path must appear exactly once');

$forbiddenNeedles = array(
    'header01',
    'tpl-user-panel.php',
    '<form',
    '<script',
    'javascript:',
    'wp_remote_',
    'curl_',
    'fetch(',
    'kanoon.ir',
    'fonts.googleapis.com',
    'fonts.gstatic.com',
    'elementor canvas',
    'site_settings',
    'plugin_inventory',
    'wp_users',
    'lorem ipsum',
    'placeholder',
    'example.com',
    'تلفن',
    'آدرس',
);
foreach ($forbiddenNeedles as $needle) {
    assertCondition(false === strpos($lower, strtolower($needle)), 'forbidden content found: ' . $needle);
}

assertCondition(0 === preg_match('#https?://#i', $scalarText), 'artifact must not contain external URLs');
assertCondition(0 === preg_match('/(?:\\+?98|0)?9\\d{9}|\\d{7,}/', $scalarText), 'artifact must not contain phone-like factual numbers');
assertCondition(false === strpos($serialized, '"type":"header"'), 'header template data must not be bundled');
assertCondition(false === strpos($serialized, '"type":"footer"'), 'footer template data must not be bundled');

foreach ($template['content'] as $topLevel) {
    assertCondition('container' === ($topLevel['elType'] ?? null), 'top-level homepage sections must be containers');
    assertCondition('section' === (($topLevel['settings']['html_tag'] ?? null)), 'top-level homepage containers must render as section elements');
}

assertCondition(3 === count($template['content']), 'v1 homepage body must contain exactly hero, quick-access, and article sections');

echo 'ELEMENTOR_HOMEPAGE_CONTRACT_PASS' . PHP_EOL;
