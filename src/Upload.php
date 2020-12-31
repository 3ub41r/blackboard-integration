<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

class Upload
{
    protected $source;
    protected $endpointUrl;
    protected $client;
    protected $queue;

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
        $this->queue = [
            'person' => [],
            'course' => [],
            'membership' => [],
        ];
    }

    /**
     * Get feed type of file
     * TODO: Uploads need to be done in the proper order.
     *
     * @param string $file
     * @return void
     */
    protected function getFeedType($file)
    {
        $feedMap = [
            'lecturers.txt' => 'person',
            'students.txt' => 'person',
            'courses.txt' => 'course',
            'enrollments.txt' => 'membership',
            'courselecturers.txt' => 'membership',
        ];

        if (! array_key_exists($file, $feedMap)) return null;

        return $feedMap[$file];
    }

    protected function pushToQueue($file)
    {
        $feedType = $this->getFeedType($file);

        if (! $feedType) return;

        $this->queue[$feedType][] = $file;
    }

    protected function buildPath($file)
    {
        return join('/', [
            trim($this->source, '/'),
            trim($file, '/'),
        ]);
    }

    protected function uploadFiles($debug = false)
    {
        // Traverse queue
        foreach ($this->queue as $feedType => $files) {
            foreach ($files as $file) {
                // Build URL
                $url = $this->endpointUrl . "/$feedType/store";

                // Append path and file
                $filePath = $this->buildPath($file);

                echo "Uploading $filePath to $url...\n";
        
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

                echo $response->getBody();
            }
        }
    }

    public function process($debug = false)
    {
        // Traverse files in directory
        $files = scandir($this->source);

        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) continue;

            $this->pushToQueue($file);
        }

        $this->uploadFiles($debug);
    }
}