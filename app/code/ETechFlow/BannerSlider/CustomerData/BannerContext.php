<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\CustomerData;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http as HttpRequest;
use Psr\Log\LoggerInterface;

/**
 * Per-visitor context for client-side banner targeting.
 *
 * Delivered through Magento's private-content (customer-data) channel, so it is
 * fetched outside Full Page Cache and is safe to vary per visitor. The slider
 * markup itself stays cacheable; the browser combines this section with device,
 * UTM and time to decide which banners to show.
 */
class BannerContext implements SectionSourceInterface
{
    /** Request headers a CDN / GeoIP module may set, in order of preference. */
    private const COUNTRY_HEADERS = [
        'HTTP_CF_IPCOUNTRY',       // Cloudflare
        'HTTP_X_COUNTRY_CODE',
        'HTTP_GEOIP_COUNTRY_CODE', // nginx/Varnish GeoIP
    ];

    public function __construct(
        private readonly CustomerSession $customerSession,
        private readonly CheckoutSession $checkoutSession,
        private readonly HttpRequest $request,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array{group_id:int,logged_in:bool,cart_qty:float,cart_subtotal:float,country:?string}
     */
    public function getSectionData(): array
    {
        $cartQty = 0.0;
        $cartSubtotal = 0.0;
        try {
            $quote = $this->checkoutSession->getQuote();
            if ($quote && $quote->getId()) {
                $cartQty = (float)$quote->getItemsQty();
                $cartSubtotal = (float)$quote->getSubtotal();
            }
        } catch (\Exception $e) {
            $this->logger->warning('ETechFlow BannerSlider: cart context unavailable: ' . $e->getMessage());
        }

        return [
            'group_id' => (int)$this->customerSession->getCustomerGroupId(),
            'logged_in' => $this->customerSession->isLoggedIn(),
            'cart_qty' => $cartQty,
            'cart_subtotal' => $cartSubtotal,
            'country' => $this->resolveCountry(),
        ];
    }

    /**
     * Best-effort visitor country from a CDN / GeoIP header, or null when none
     * is present (geo rules then simply do not match — documented behaviour).
     *
     * @return string|null
     */
    private function resolveCountry(): ?string
    {
        foreach (self::COUNTRY_HEADERS as $header) {
            $value = $this->request->getServer($header);
            if (is_string($value) && $value !== '' && strtoupper($value) !== 'XX') {
                return strtoupper(substr($value, 0, 2));
            }
        }
        return null;
    }
}
