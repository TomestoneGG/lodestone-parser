<?php

namespace Lodestone\Http;

use Lodestone\Exceptions\LodestoneException;
use Lodestone\Exceptions\LodestoneMaintenanceException;
use Lodestone\Exceptions\LodestoneNotFoundException;
use Lodestone\Exceptions\LodestonePrivateException;
use Lodestone\Parser\Parser;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpClient\CurlHttpClient;

class Http
{
    const BASE_URI = 'https://na.finalfantasyxiv.com/';
    const TIMEOUT  = 30;

    /**
     * Get Symfony Client
     */
    private function getClient(string $baseUri = null)
    {
        return new CurlHttpClient([
            'base_uri' => $baseUri ?: self::BASE_URI,
            'timeout'  => self::TIMEOUT
        ]);
    }

    /**
     * Perform a request
     * @throws
     */
    public function request(string $parser, Request $request, int $tryIndex = 0)
    {
        // get client
        $client = $this->getClient($request->baseUri);

        // set some custom user data
        $request->userData['request_url'] = $request->baseUri . $request->endpoint;
        $request->userData['request_id']  = AsyncHandler::$requestId ?: Uuid::uuid4()->toString();
        $request->userData['parser']      = $parser;

        // perform request
        $response = $client->request($request->method, $request->endpoint, [
            'query'     => $request->query,
            'headers'   => $request->headers,
            'json'      => $request->json,
            'user_data' => $request->userData
        ]);

        // Asynchronous: Pop the response into the async handler, this returns the number
        // it was assigned to
        if (RequestConfig::$isAsync) {
            AsyncHandler::add($response);
            return null;
        }

        if ($response->getStatusCode() != 200 && $tryIndex < 3) {
            sleep(2);
            return $this->request($parser, $request, $tryIndex + 1);
        }

        if ($response->getStatusCode() == 503) {
            throw new LodestoneMaintenanceException(
                'Lodestone is currently down for maintenance.',
                $response->getStatusCode()
            );
        }

        if ($response->getStatusCode() == 404) {
            throw new LodestoneNotFoundException(
                'Could not find: ' . $request->userData['request_url'],
                $response->getStatusCode()
            );
        }

        if ($response->getStatusCode() == 403) {
            throw new LodestonePrivateException(
                'This page is private: ' . $request->userData['request_url'],
                $response->getStatusCode()
            );
        }

        if ($response->getStatusCode() != 200) {
            throw new LodestoneException(
                'Unknown exception status code (' . $response->getStatusCode() . ') for: ' . $request->userData['request_url'],
                $response->getStatusCode()
            );
        }

        /** @var Parser $parser */
        $parser = new $parser($request->userData);

        // Synchronous: Get the content
        return $parser->handle($response->getContent());
    }

    /**
     * Settle any async requests
     * @throws
     */
    public function settle()
    {
        if (RequestConfig::$isAsync === false) {
            throw new \Exception("Request API is not in async mode. There will be no async requests to settle.");
        }

        $content   = [];
        $client    = $this->getClient();
        $responses = AsyncHandler::get();

        foreach ($client->stream($responses) as $response => $chunk) {
            // grab the user data
            $userdata = $response->getInfo('user_data');

            // grab request id
            $requestId = $userdata['request_id'];

            // if it wasn't a 200, return error
            if ($response->getStatusCode() != 200) {
                $content[$requestId] = (object)[
                    'Error' => true,
                    'StatusCode' => $response->getStatusCode()
                ];
                continue;
            }

            if ($chunk->isLast()) {
                // grab the parser class name
                /** @var Parser $parser */
                $parser = new $userdata['parser']($userdata);

                // handle response
                $content[$requestId] = $parser->handle(
                    $response->getContent()
                );
            }
        }

        return $content;
    }
}
