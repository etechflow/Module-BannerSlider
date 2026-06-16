<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Test\Unit\Model\Targeting;

use ETechFlow\BannerSlider\Model\Targeting\TargetingRules;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;

class TargetingRulesTest extends TestCase
{
    private TargetingRules $rules;

    protected function setUp(): void
    {
        // Json has no dependencies; use the real serializer for a faithful test.
        $this->rules = new TargetingRules(new Json());
    }

    public function testToJsonBuildsCompactRules(): void
    {
        $json = $this->rules->toJson([
            'tgt_login' => 'logged_in',
            'tgt_devices' => ['mobile', 'tablet'],
            'tgt_countries' => ['US'],
            'tgt_cart_qty_min' => '2',
            'tgt_cart_subtotal_min' => '50',
            'tgt_days' => ['1', '2'],
            'tgt_hour_from' => '9',
            'tgt_hour_to' => '17',
            'tgt_utm_source' => 'newsletter',
        ]);

        $decoded = json_decode((string)$json, true);
        $this->assertSame('logged_in', $decoded['login']);
        $this->assertSame(['mobile', 'tablet'], $decoded['devices']);
        $this->assertSame(['US'], $decoded['countries']);
        $this->assertSame(2, $decoded['cart_qty_min']);
        // Stored as a float; JSON may render it without a decimal — compare numerically.
        $this->assertEqualsWithDelta(50.0, $decoded['cart_subtotal_min'], 0.0001);
        $this->assertSame(['1', '2'], $decoded['days']);
        $this->assertSame(9, $decoded['hour_from']);
        $this->assertSame(['source' => 'newsletter'], $decoded['utm']);
    }

    public function testEmptyOrAnyProducesNull(): void
    {
        $this->assertNull($this->rules->toJson([]));
        $this->assertNull($this->rules->toJson(['tgt_login' => 'any', 'tgt_devices' => [], 'tgt_utm_source' => '']));
    }

    public function testRoundTrip(): void
    {
        $form = [
            'tgt_login' => 'guest',
            'tgt_devices' => ['desktop'],
            'tgt_countries' => [],
            'tgt_cart_qty_min' => '',
            'tgt_cart_qty_max' => '',
            'tgt_cart_subtotal_min' => '',
            'tgt_cart_subtotal_max' => '',
            'tgt_days' => [],
            'tgt_hour_from' => '',
            'tgt_hour_to' => '',
            'tgt_utm_source' => 'fb',
            'tgt_utm_medium' => '',
            'tgt_utm_campaign' => '',
        ];
        $json = $this->rules->toJson($form);
        $back = $this->rules->toFormFields($json);

        $this->assertSame('guest', $back['tgt_login']);
        $this->assertSame(['desktop'], $back['tgt_devices']);
        $this->assertSame('fb', $back['tgt_utm_source']);
        // Unset scalar fields come back as empty strings, multiselects as arrays.
        $this->assertSame('', $back['tgt_hour_from']);
        $this->assertSame([], $back['tgt_days']);
    }

    public function testToFormFieldsToleratesInvalidJson(): void
    {
        $fields = $this->rules->toFormFields('not-json');
        $this->assertSame('', $fields['tgt_login']);
        $this->assertSame([], $fields['tgt_devices']);
    }

    public function testFieldNamesCoverEveryMappedField(): void
    {
        $names = $this->rules->fieldNames();
        $this->assertContains('tgt_login', $names);
        $this->assertContains('tgt_utm_campaign', $names);
        $this->assertContains('tgt_days', $names);
        // 7 scalar + 3 array + 3 utm = 13 owned form fields.
        $this->assertCount(13, $names);
    }
}
