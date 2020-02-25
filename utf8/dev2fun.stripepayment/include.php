<?php
/**
 * @author darkfriend <hi@darkfriend.ru>
 * @copyright (c) 2020, darkfriend
 * @version 1.3.2
 */
if (class_exists('Dev2funModuleStripeClass')) return;

class Dev2funModuleStripeClass
{
    public static $module_id = 'dev2fun.stripepayment';
    public static $arScanDir = [
        '/local/php_interface/include/sale_payment/stripe/templates',
        '/bitrix/php_interface/include/sale_payment/stripe/templates',
        '/bitrix/modules/dev2fun.stripepayment/sale_payment/stripe/templates',
    ];

    /**
     * Get modes for stripe
     * @return array
     */
    public static function GetSupportModes()
    {
        return [
            'card' => 'Card',
            'sepa' => 'Sepa Debit',
            'sofort' => 'Sofort',
            'giropay' => 'Giropay',
        ];
    }

    /**
     * Get modes for stripe from string
     * @param string $strModes
     * @return array
     */
    public static function GetModesByString($strModes)
    {
        if (!$strModes) return [];
        return explode(', ', $strModes);
    }

    /**
     * Get all templates
     * @return array
     */
    public static function GetSupportTemplates()
    {
        $arScanDir = self::$arScanDir;
        $arTemplates = [];
        if ($arScanDir) {
            foreach ($arScanDir as $sdir) {
                $dirPath = $_SERVER['DOCUMENT_ROOT'] . $sdir;
                $d = dir($dirPath);
                if (!$d) continue;
                while ($dir = $d->read()) {
                    $dir = mb_strtoupper($dir);
                    if (!in_array($dir, ['.', '..']) && !in_array($dir, $arTemplates)) {
                        $arTemplates[$dir] = $dir;
                    }
                }
            }
        }
        return $arTemplates;
    }

    /**
     * Get path to template
     * @param $template - template name
     * @return bool|string
     */
    public static function GetPathTemplate($template)
    {
        if (!$template) return false;
        $template = mb_strtolower($template);
        $arScanDir = self::$arScanDir;
        if (!$arScanDir) return false;
        foreach ($arScanDir as $sdir) {
            $dirPath = $_SERVER['DOCUMENT_ROOT'] . $sdir;
            $d = dir($dirPath);
            if (!$d) continue;
            while ($dir = $d->read()) {
                $dir = mb_strtolower($dir);
                if (!in_array($dir, ['.', '..']) && $dir == $template) {
                    return $dirPath . '/' . $dir;
                }
            }
        }
        return false;
    }

    /**
     * Get redirect url
     * @param string $url
     * @param integer $orderId
     * @param string $status
     * @return string
     * @deprecated
     */
    public static function GetRedurectUrl($url, $orderId, $status = 'fail')
    {
        return self::GetRedirectUrl($url, $orderId, $status);
    }

    /**
     * Get redirect url
     * @param string $url
     * @param integer $orderId
     * @param string $status
     * @return string
     */
    public static function GetRedirectUrl($url, $orderId, $status = 'fail')
    {
        $arUrl = parse_url($url);
        if (!empty($arUrl['query'])) {
            $arUrl['query'] .= '&';
        } else {
            $arUrl['query'] = '';
        }
        $arUrl['query'] .= 'pay=' . $status . '&ORDER_ID=' . $orderId;
        return $arUrl['path'] . '?' . $arUrl['query'];
    }

    /**
     * @return array
     */
    public static function getSupportCurrencies()
    {
        return [
            'USD', 'RUB', 'EUR', 'GBP', 'UAH',
        ];
    }

    /**
     * @param string $currency
     * @return bool
     */
    public static function isSupportCurrency($currency)
    {
        $currency = \mb_strtoupper($currency);
        return \in_array($currency, self::getSupportCurrencies());
    }
}