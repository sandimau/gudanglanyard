<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\MarketplaceFormat;

trait ShopeeApi
{
    protected function ambilApi($marketplace, $path, $param = [])
    {
        try {
            $accessToken = $this->ambilToken($marketplace);

            if ($accessToken) {

                $path = "/api/v2/" . $path;

                $format = MarketplaceFormat::shopee();

                $curl = curl_init();

                $timest = time();

                $baseString = sprintf("%s%s%s%s%s", $format->partnerId, $path, $timest, $accessToken, $marketplace->shop_id);
                $param['sign'] = hash_hmac('sha256', $baseString, $format->partnerKey);
                $param['partner_id'] = $format->partnerId;
                $param['timestamp'] = $timest;
                $param['access_token'] = $this->ambilToken($marketplace);
                $param['shop_id'] = $marketplace->shop_id;


                $url = $format->host . $path . "?" . http_build_query($param);


                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json'
                    ),
                ));
                $response = curl_exec($curl);

                if (curl_errno($curl)) {
                    $curlError = curl_error($curl);
                    curl_close($curl);
                    Log::error('ambilApi - cURL Error: ' . $curlError, [
                        'path' => $path,
                        'shop_id' => $marketplace->shop_id
                    ]);
                    return ['error' => 'cURL Error: ' . $curlError];
                }

                curl_close($curl);

                return json_decode($response, true);
            } else {
                Log::error('ambilApi - Token gagal diambil', [
                    'marketplace_id' => $marketplace->id ?? null,
                    'shop_id' => $marketplace->shop_id ?? null,
                    'path' => $path
                ]);
                return ['error' => 'token gagal diambil'];
            }
        } catch (\Exception $e) {
            Log::error('ambilApi - Exception: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['error' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Kirim request POST ber-autentikasi ke Shopee Open API (butuh access_token + shop_id).
     * Dipakai untuk endpoint write seperti product/update_price.
     */
    protected function kirimApi($marketplace, $path, $body = [])
    {
        try {
            $accessToken = $this->ambilToken($marketplace);

            if (!$accessToken) {
                Log::error('kirimApi - Token gagal diambil', [
                    'marketplace_id' => $marketplace->id ?? null,
                    'shop_id' => $marketplace->shop_id ?? null,
                    'path' => $path,
                ]);
                return ['error' => 'token gagal diambil'];
            }

            $path = "/api/v2/" . $path;

            $format = MarketplaceFormat::shopee();

            $timest = time();

            $baseString = sprintf("%s%s%s%s%s", $format->partnerId, $path, $timest, $accessToken, $marketplace->shop_id);
            $sign = hash_hmac('sha256', $baseString, $format->partnerKey);

            $query = [
                'partner_id' => $format->partnerId,
                'timestamp' => $timest,
                'access_token' => $accessToken,
                'shop_id' => $marketplace->shop_id,
                'sign' => $sign,
            ];

            $url = $format->host . $path . "?" . http_build_query($query);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($body),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);

            if (curl_errno($curl)) {
                $curlError = curl_error($curl);
                curl_close($curl);
                Log::error('kirimApi - cURL Error: ' . $curlError, [
                    'path' => $path,
                    'shop_id' => $marketplace->shop_id,
                ]);
                return ['error' => 'cURL Error: ' . $curlError];
            }

            curl_close($curl);

            return json_decode($response, true);
        } catch (\Exception $e) {
            Log::error('kirimApi - Exception: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['error' => 'Exception: ' . $e->getMessage()];
        }
    }

    protected function ambilTokenPertama($code, $shopId)
    {

        $format = MarketplaceFormat::shopee();

        $path = "auth/token/get";
        $body = array("code" => $code,  "shop_id" => (int)$shopId, "partner_id" => $format->partnerId);

        $ret = $this->curlPost($path, $body);

        if ($ret)
            return [
                'access_token' => $ret['access_token'],
                'refresh_token' => $ret['refresh_token'],
                'access_expired' => ($ret['expire_in'] + time()),
            ];
        else return false;
    }

    public function ambilToken($marketplace)
    {
        try {
            $marketplaceId = is_object($marketplace) ? $marketplace->id : (int) $marketplace;
            $record = DB::table('marketplaces')->find($marketplaceId);

            if (!$record) {
                return false;
            }

            if ($record->access_expired >= (time() + 10)) {
                return $record->access_token;
            }

            return $this->refreshMarketplaceToken($marketplaceId) ?: false;
        } catch (\Exception $e) {
            Log::error('ambilToken - Exception: ' . $e->getMessage(), [
                'marketplace_id' => is_object($marketplace) ? ($marketplace->id ?? null) : $marketplace,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Refresh token Shopee untuk marketplace tertentu.
     * Lock via Cache (bukan kolom DB) agar refresh paralel tidak bentrok.
     */
    public function refreshMarketplaceToken($marketplaceId): ?string
    {
        $lockKey = 'shopee_token_refresh_' . $marketplaceId;
        $lock = Cache::lock($lockKey, 30);

        if (!$lock->block(10)) {
            Log::error('refreshMarketplaceToken - tidak dapat lock', [
                'marketplace_id' => $marketplaceId,
            ]);
            return null;
        }

        try {
            $marketplace = DB::table('marketplaces')->find($marketplaceId);

            if (!$marketplace || !$marketplace->refresh_token || !$marketplace->shop_id) {
                return null;
            }

            if ($marketplace->access_expired >= (time() + 10)) {
                return $marketplace->access_token;
            }

            $path = "auth/access_token/get";
            $format = MarketplaceFormat::shopee();
            $body = [
                'partner_id' => $format->partnerId,
                'shop_id' => (int) $marketplace->shop_id,
                'refresh_token' => $marketplace->refresh_token,
            ];

            $ret = $this->curlPost($path, $body);

            if ($ret && !empty($ret['access_token'])) {
                DB::table('marketplaces')->where('id', $marketplaceId)->update([
                    'access_token' => $ret['access_token'],
                    'refresh_token' => $ret['refresh_token'],
                    'access_expired' => ($ret['expire_in'] + time()),
                ]);

                return $ret['access_token'];
            }

            Log::error('refreshMarketplaceToken - Gagal refresh token', [
                'marketplace_id' => $marketplaceId,
                'shop_id' => $marketplace->shop_id,
            ]);

            return null;
        } finally {
            $lock->release();
        }
    }

    protected function isTokenApiError($api): bool
    {
        if (empty($api['error'])) {
            return false;
        }

        $tokenString = is_array($api['error']) ? json_encode($api['error']) : (string) $api['error'];

        return stripos($tokenString, 'access_token') !== false
            || stripos($tokenString, 'refresh_token') !== false
            || stripos($tokenString, 'invalid') !== false
            || stripos($tokenString, 'expired') !== false
            || stripos($tokenString, 'token gagal') !== false;
    }

    public function curlPost($path, $body)
    {

        $path = "/api/v2/" . $path;
        $format = MarketplaceFormat::shopee();

        $timest = time();
        $baseString = sprintf("%s%s%s", $format->partnerId, $path, $timest);
        $sign = hash_hmac('sha256', $baseString, $format->partnerKey);
        $url = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s", $format->host, $path, $format->partnerId, $timest, $sign);


        $c = curl_init($url);
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($c);

        $ret = json_decode($result, true);


        if (!empty($ret["error"])) {
            Log::error('curlPost path:' . $path . ' body:' . json_encode($body), $ret);
            return false;
        } else
            return $ret;
    }
}
