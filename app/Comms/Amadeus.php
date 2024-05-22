<?php

namespace App\Comms;

use App\Exceptions\AmadeusException;
use App\Exceptions\CustomException;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class Amadeus
{
    private const TOKEN_CACHE_KEY = 'amadeus.token';

    private string $url;

    private string $token = '';

    private string $clientId;

    private string $secret;

    public function __construct(
        protected Connection $server
    ) {
        $this->url = config('vars.amadeus.url');
        $this->clientId = config('vars.amadeus.api_key');
        $this->secret = config('vars.amadeus.secret_key');
        // $this->setToken();
    }

    // private function client(): PendingRequest
    // {
    //     return Http::withToken($this->token)->retry(2, 0, function (Exception $exception, PendingRequest $request) {
    //         if (! $exception instanceof RequestException || $exception->response->status() !== 403) {
    //             return false;
    //         }

    //         $this->setToken(true);
    //         $request->withToken($this->token);

    //         return true;
    //     })->beforeSending(function ($request) {
    //         Log::debug('Amadeus API Request:', [
    //             'method' => $request->method(),
    //             'uri' => $request->url(),
    //         ]);
    //     })->baseUrl($this->url);
    // }

    private function setToken(bool $renew = false): void
    {
        if (! $renew && cache()->has(self::TOKEN_CACHE_KEY)) {
            $this->token = cache()->get(self::TOKEN_CACHE_KEY);

            return;
        }

        try {
            $response = Http::asForm()->post($this->url.'/v1/security/oauth2/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->secret,
            ])->json();

            $this->token = $response['access_token'];

            cache()->put(self::TOKEN_CACHE_KEY, $this->token, now()->addMinutes(29));
        } catch (Throwable $exception) {
            cache()->delete(self::TOKEN_CACHE_KEY);
            Log::error($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array <string,mixed>
     */
    public function searchOffers(array $data): array
    {
        $this->setToken();
        $endPoint = config('vars.amadeus.flight_offers');
        $headers = ['content-type' => 'application/json', 'Authorization' => 'Bearer '.$this->token];
        try {
            $response = $this->server->sendGet(
                url: $endPoint,
                headers: $headers,
                query: $data
            );
            Log::info($response);
            if (array_key_exists('errors', $response) || ! array_key_exists('data', $response)) {
                throw new AmadeusException(
                    $response['errors'][0]['detail'],
                    $response['errors'][0]['code']
                );
            }
        } catch (\Throwable $th) {
            Log::error($th);
            throw $th;
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function validatePrice(array $data): mixed
    {
        $this->setToken();
        $endPoint = config('vars.amadeus.flight_offers_price');
        $headers = ['content-type' => 'application/vnd.amadeus+json', 'Authorization' => 'Bearer '.$this->token, 'X-HTTP-Method-Override' => 'GET'];
        try {
            $response = $this->server->sendPost(
                url: $endPoint,
                headers: $headers,
                data: $data
            );
            logger($response); //@phpstan-ignore-line
            if (array_key_exists('errors', $response) || ! array_key_exists('data', $response)) {
                throw new AmadeusException(
                    $response['errors'][0]['detail'],
                    $response['errors'][0]['code']
                );
            }
        } catch (\Throwable $th) {
            logger($th);
            throw $th;
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function bookFlight(array $data): mixed
    {
        $this->setToken();
        $endPoint = config('vars.amadeus.flight_book');
        $headers = ['Accept' => 'application/vnd.amadeus+json', 'content-type' => 'application/vnd.amadeus+json', 'Authorization' => 'Bearer '.$this->token];
        try {
            $response = $this->server->sendPost(
                url: $endPoint,
                headers: $headers,
                data: $data
            );
            logger($response); //@phpstan-ignore-line
            if (array_key_exists('errors', $response) || ! array_key_exists('data', $response)) {
                throw new AmadeusException(
                    $response['errors'][0]['detail'],
                    $response['errors'][0]['code']
                );
            }
        } catch (\Throwable $th) {
            logger($th);
            if (! $th instanceof CustomException) {
                throw $th;
            }
            throw new CustomException('An error occured, while booking');
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array <string,mixed>
     */
    public function flightDeals(array $data): array
    {
        $this->setToken();
        $endPoint = config('vars.amadeus.flight_deals');
        $headers = ['content-type' => 'application/json', 'Authorization' => 'Bearer '.$this->token];
        try {
            $response = $this->server->sendGet(
                url: $endPoint,
                headers: $headers,
                query: $data
            );
            Log::info($response);
            if (array_key_exists('errors', $response) || ! array_key_exists('data', $response)) {
                throw new AmadeusException(
                    $response['errors'][0]['detail'],
                    $response['errors'][0]['code']
                );
            }
        } catch (\Throwable $th) {
            Log::error($th);
            throw $th;
        }

        return $response;
    }
}
