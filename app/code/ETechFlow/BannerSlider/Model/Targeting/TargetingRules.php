<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Targeting;

use Magento\Framework\Serialize\Serializer\Json;

/**
 * Translates the banner form's targeting fields to/from the JSON stored in
 * etechflow_bannerslider_banner.conditions_serialized.
 *
 * Targeting is evaluated client-side (see view/frontend/web/js/targeting.js) so
 * the slider markup stays identical for every visitor and remains Full Page
 * Cache / Varnish safe; this class only handles persistence + admin form glue.
 */
class TargetingRules
{
    /** Admin form field => JSON key (plain scalar values). */
    private const SCALAR_MAP = [
        'tgt_login' => 'login',
        'tgt_cart_qty_min' => 'cart_qty_min',
        'tgt_cart_qty_max' => 'cart_qty_max',
        'tgt_cart_subtotal_min' => 'cart_subtotal_min',
        'tgt_cart_subtotal_max' => 'cart_subtotal_max',
        'tgt_hour_from' => 'hour_from',
        'tgt_hour_to' => 'hour_to',
    ];

    /** Admin form field => JSON key (multiselect arrays). */
    private const ARRAY_MAP = [
        'tgt_devices' => 'devices',
        'tgt_countries' => 'countries',
        'tgt_days' => 'days',
    ];

    /** Admin form field => key inside the nested "utm" object. */
    private const UTM_MAP = [
        'tgt_utm_source' => 'source',
        'tgt_utm_medium' => 'medium',
        'tgt_utm_campaign' => 'campaign',
    ];

    private const INT_KEYS = ['cart_qty_min', 'cart_qty_max', 'hour_from', 'hour_to'];
    private const FLOAT_KEYS = ['cart_subtotal_min', 'cart_subtotal_max'];

    public function __construct(
        private readonly Json $json
    ) {
    }

    /**
     * All admin form field names this class owns (so the Save controller can
     * strip them before persisting — they are not table columns).
     *
     * @return string[]
     */
    public function fieldNames(): array
    {
        return array_merge(
            array_keys(self::SCALAR_MAP),
            array_keys(self::ARRAY_MAP),
            array_keys(self::UTM_MAP)
        );
    }

    /**
     * Collapse the submitted targeting fields into a compact JSON string, or
     * null when nothing meaningful was set.
     *
     * @param array $formData
     * @return string|null
     */
    public function toJson(array $formData): ?string
    {
        $rules = [];

        foreach (self::SCALAR_MAP as $field => $key) {
            $value = $formData[$field] ?? null;
            if ($value === null || $value === '' || $value === 'any') {
                continue;
            }
            if (in_array($key, self::INT_KEYS, true)) {
                $rules[$key] = (int)$value;
            } elseif (in_array($key, self::FLOAT_KEYS, true)) {
                $rules[$key] = (float)$value;
            } else {
                $rules[$key] = (string)$value;
            }
        }

        foreach (self::ARRAY_MAP as $field => $key) {
            $value = $formData[$field] ?? null;
            if (is_array($value)) {
                $value = array_values(array_filter($value, static fn ($v) => $v !== '' && $v !== null));
                if ($value) {
                    $rules[$key] = $value;
                }
            }
        }

        $utm = [];
        foreach (self::UTM_MAP as $field => $key) {
            $value = trim((string)($formData[$field] ?? ''));
            if ($value !== '') {
                $utm[$key] = $value;
            }
        }
        if ($utm) {
            $rules['utm'] = $utm;
        }

        return $rules ? $this->json->serialize($rules) : null;
    }

    /**
     * Expand stored JSON back into the flat tgt_* fields the form binds to.
     *
     * @param string|null $json
     * @return array<string, mixed>
     */
    public function toFormFields(?string $json): array
    {
        $rules = $this->decode($json);

        $fields = [];
        foreach (self::SCALAR_MAP as $field => $key) {
            $fields[$field] = $rules[$key] ?? '';
        }
        foreach (self::ARRAY_MAP as $field => $key) {
            $fields[$field] = isset($rules[$key]) && is_array($rules[$key]) ? $rules[$key] : [];
        }
        $utm = isset($rules['utm']) && is_array($rules['utm']) ? $rules['utm'] : [];
        foreach (self::UTM_MAP as $field => $key) {
            $fields[$field] = $utm[$key] ?? '';
        }
        return $fields;
    }

    /**
     * Decode stored JSON into an array, tolerating empty/invalid input.
     *
     * @param string|null $json
     * @return array
     */
    public function decode(?string $json): array
    {
        if (!$json) {
            return [];
        }
        try {
            $decoded = $this->json->unserialize($json);
        } catch (\InvalidArgumentException $e) {
            return [];
        }
        return is_array($decoded) ? $decoded : [];
    }
}
