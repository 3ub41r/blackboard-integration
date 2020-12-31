<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

class Upload
{
    protected $source;
    protected $endpointUrl;
    protected $client;

    /**
     * Upload exported flat files to Blackboard
     *
     * @param string $source Path to flat files
     * @param string $endpointUrl URL to Blackboard SIS
     */
    public function __construct($source, $endpointUrl)
    {
        $this->source = $source;
        $this->endpointUrl = $endpointUrl;
        $this->client = new Client();
    }

    /**
     * Get feed type of file
     *
     * @param string $file
     * @return void
     */
    protected function getFeedType($file)
    {
        $feedMap = [
            'external_person_key' => 'person',
            'external_course_key' => 'course',
        ];

        // Read first line of file
        $line = fgets(fopen($this->buildPath($file), 'r'));

        foreach ($feedMap as $key => $value) {
            if (strpos($line, $key) !== false) {
                return $value;
            }
        }

        return null;
    }

    protected function buildPath($file)
    {
        return join('/', [
            trim($this->source, '/'),
            trim($file, '/'),
        ]);
    }

    public function process($debug = false)
    {
        // Traverse files in directory
        $files = scandir($this->source);

        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) continue;

            $feedType = $this->getFeedType($file);

            if (! $feedType) continue;

            // Build URL
            $url = $this->endpointUrl . "/$feedType/store";

            // Append path and file
            $filePath = $this->buildPath($file);
    
            // POST file
            $response = $this->client->request('POST', $url, [
                'body' => fopen($filePath, 'r'),
                'auth' => [
                    'a2ae284a-e63b-4e50-bbb8-2256ac38a91a', 
                    '6N^ltz5@2@a0'
                ],
                'headers' => [
                    'Content-Type' => 'text/plain',
                ],
                'debug' => $debug,
            ]);

            if ($debug) {
                echo $response->getBody();
            }
        }
    }
}